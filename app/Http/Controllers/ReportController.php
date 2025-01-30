<?php

namespace App\Http\Controllers;

use App\Models\AlertMessage;
use App\Models\AlertMetric;
use App\Models\Classification;
use App\Models\Identity;
use App\Models\Priority;
use App\Models\Sensor;
use App\Models\SensorMetric;
use App\Models\Traffic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ReportController extends Controller
{
    /**
     * Get the complete report as a JSON object.
     *
     * @return JsonResponse
     */
    public function getReport(Request $request): JsonResponse
    {
        // Get the raw JSON from the request
        $rawJson = $request->getContent();
        $data = json_decode($rawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Decode Error: ', ['error' => json_last_error_msg()]);
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        $processedData = [];
        foreach ($data as $event) {
            if (!isset(
                $event['sensor_id'], 
                $event['snort_priority'], $event['snort_classification'], $event['snort_message'], $event['snort_seconds'], $event['metrics'])) {
                Log::error('Missing required event keys', ['event' => $event]);
                continue; // Skip this event if required keys are missing
            }

            Log::info('Processing Event:', ['event' => $event]);

            $sensor = Sensor::firstOrCreate(
                ['sensor_name' => $event['sensor_id']],
                [
                    'id' => Str::uuid()->toString(),
                    'sensor_name' => $event['sensor_id'],
                ]
            );

            $classification = Classification::firstOrCreate(
                [
                    'classification' => $event['snort_classification'],
                    'priority_id' => Priority::where('name', $event['snort_priority'])->first()->id,
                ]
            );

            $alertMessage = AlertMessage::firstOrCreate(
                [
                    'classification_id' => $classification->id,
                    'alert_message' => $event['snort_message']
                ]
            );

            $alertMetric = AlertMetric::firstOrCreate(
                [
                    'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                    'sensor_id' => $sensor->id,
                    'alert_id' => $alertMessage->id,
                ],
                [
                    'count' => $event['event_metrics_count'],
                ]
                );
            
            // Use incrementOrCreate to update the count
            $sensorMetricAttributes = [
                'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                'sensor_id' => $sensor->id,
            ];

            $sensorMetric = SensorMetric::incrementOrCreate($sensorMetricAttributes, 'count', 1, $event['event_metrics_count'], []);
            // $sensorMetric = SensorMetric::updateOrCreate(
            //     [
            //         'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
            //         'sensor_id' => $sensor->id,
            //     ],
            //     [
            //         'count' => $event['event_metrics_count'],
            //     ]
            // );

            foreach ($event['metrics'] as $metric) {
                Log::info('Processing Metric:', ['metric' => $metric]);

                foreach ($metric['snort_dst_src_port'] as $port => $count) {
                    list($dstPort, $srcPort) = explode(':', $port);
                    
                    $srcIpAddress = $this->createOrUpdateIdentity($metric['snort_src_address']);
                    $dstIpAddress = $this->createOrUpdateIdentity($metric['snort_dst_address']);

                    $traffic = Traffic::firstOrCreate([
                        'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                        'sensor_id' => $sensor->id,
                        'source_ip' => $srcIpAddress->ip_address,
                        'source_port' => $srcPort,
                        'destination_ip' => $dstIpAddress->ip_address,
                        'destination_port' => $dstPort,
                        'count' => $count
                    ]);
                }

                $processedData[] = [
                        'alert_metric' => [
                        'timestamp' => $alertMetric->timestamp,
                        'sensor' => [
                            'id' => $sensor->id,
                            'sensor_name' => $sensor->sensor_name,
                        ],
                        'alert_message' => [
                            'classification' => [
                                'classification' => $classification->classification,
                                'priority' => $classification->priority->name,
                            ],
                            'alert_message' => $alertMessage->alert_message,
                        ],
                        'count' => $alertMetric->count,
                    ],
                    // 'sensor_metric' => $sensorMetric,
                    'traffic' => $traffic,
                ];
            }
        }

        return response()->json([
            'message' => 'success',
            'data' => $processedData        
        ], 200);
    
        // foreach ($data as $event) {
    
        //     $alertMessage = AlertMessage::firstOrCreate(
        //         [
        //             'classification_id' => $classification->id,
        //             'alert_message' => $event['snort_message']
        //         ]
        //     );
    
        //     $sensorMetric = SensorMetric::firstOrCreate(
        //         [
        //             'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
        //             'sensor_id' => $sensor->id,
        //         ],
        //         [
        //             'count' => $event['event_metrics_count'],
        //         ]
        //     );
    
        //     $totalEventMetricsCount += $event['event_metrics_count'];
    
        //     foreach ($event['metrics'] as $metric) {
        //         Log::info('Processing Metric:', ['metric' => $metric]);
    
        //         $this->createOrUpdateIdentity($metric['snort_src_address'] ?? null, $metric['snort_src_country'] ?? null);
        //         $this->createOrUpdateIdentity($metric['snort_dst_address'] ?? null, $metric['snort_dst_country'] ?? null);
    
        //         $traffic = Traffic::create([
        //             'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
        //             'sensor_id' => $sensor->id,
        //             'source_ip' => !empty($metric['snort_src_address']) ? $metric['snort_src_address'] : null, // Use null instead of empty string
        //             'source_port' => $metric['snort_src_port'] ?? 0,
        //             'destination_ip' => !empty($metric['snort_dst_address']) ? $metric['snort_dst_address'] : null, // Use null instead of empty string
        //             'destination_port' => $metric['snort_dst_port'] ?? 0,
        //             'count' => $metric['count'],
        //         ]);
    
        //         $alertMetric = AlertMetric::where([
        //             'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
        //             'sensor_id' => $sensor->id,
        //             'alert_id' => $alertMessage->id,
        //         ])->first();
    
        //         if ($alertMetric) {
        //             $alertMetric->count += $metric['count'];
        //             $alertMetric->save();
        //         } else {
        //             $alertMetric = AlertMetric::create([
        //                 'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
        //                 'sensor_id' => $sensor->id,
        //                 'alert_id' => $alertMessage->id,
        //                 'count' => $metric['count'],
        //             ]);
        //         }
    
        //         $eventData = [
        //             'sensor' => $sensor,
        //             'priority' => $priority,
        //             'classification' => $classification,
        //             'alert_message' => $alertMessage,
        //             'sensor_metric' => $sensorMetric,
        //             'alert_metric' => $alertMetric,
        //             'traffic' => [
        //                 'timestamp' => $traffic->timestamp,
        //                 'sensor_id' => $traffic->sensor->sensor_name, 
        //                 'source' => [
        //                     'ip_address' => $traffic->source_ip ?? '',
        //                     'port' => $traffic->source_port,
        //                     'country_name' => $traffic->sourceIdentity->country_name ?? '',
        //                 ],
        //                 'destination' => [
        //                     'ip_address' => $traffic->destination_ip ?? '',
        //                     'port' => $traffic->destination_port,
        //                     'country_name' => $traffic->destinationIdentity->country_name ?? '',
        //                 ],
        //                 'count' => $traffic->count,
        //                 'updated_at' => $traffic->updated_at,
        //                 'created_at' => $traffic->created_at,
        //                 'id' => $traffic->id,
        //             ],
        //         ];
    
        //         $processedData[] = $eventData;
        //     }
        // }
    
        // return response()->json([
        //     'success' => true,
        //     'data' => $processedData,
        // ], 201);
    }
    
    public function generateReport(): JsonResponse 
    {
        $trafficData = Traffic::with([
            'sensor.alertMetrics.alertMessage.classification.priority',
            'sourceIdentity',
            'destinationIdentity'
        ])
        ->orderBy('timestamp', 'desc')
        ->get();
    
        Log::info('First Traffic Record:', [
            'traffic' => $trafficData->first(),
            'sensor' => $trafficData->first()->sensor,
            'alert_metrics' => $trafficData->first()->sensor->alertMetrics,
            'alert_message' => $trafficData->first()->sensor->alertMetrics->first()?->alertMessage,
            'classification' => $trafficData->first()->sensor->alertMetrics->first()?->alertMessage?->classification,
            'priority' => $trafficData->first()->sensor->alertMetrics->first()?->alertMessage?->classification?->priority
        ]);
    
        $processedData = $trafficData->map(function ($traffic) {
            $alertMetric = $traffic->sensor->alertMetrics->first();
            
            // For debugging individual records
            Log::info('Processing Traffic:', [
                'traffic_id' => $traffic->id,
                'sensor_name' => $traffic->sensor->sensor_name,
                'alert_metrics' => $traffic->sensor->alertMetrics,
                'alert_message' => $alertMetric?->alertMessage,
                'classification' => $alertMetric?->alertMessage?->classification,
                'priority' => $alertMetric?->alertMessage?->classification?->priority
            ]);
    
            return [
                'sensor' => $traffic->sensor->sensor_name,
                'priority' => $alertMetric?->alertMessage?->classification?->priority?->name ?? 'Unknown',
                'traffic' => [
                    'timestamp' => $traffic->timestamp,
                    'sensor_id' => $traffic->sensor->sensor_name,
                    'source' => [
                        'ip_address' => $traffic->source_ip === '0.0.0.0' ? '' : $traffic->source_ip,
                        'port' => $traffic->source_port,
                        'country_name' => $traffic->sourceIdentity?->country_name ?? '',
                    ],
                    'destination' => [
                        'ip_address' => $traffic->destination_ip === '0.0.0.0' ? '' : $traffic->destination_ip,
                        'port' => $traffic->destination_port,
                        'country_name' => $traffic->destinationIdentity?->country_name ?? '',
                    ],
                    'count' => $traffic->count,
                    'updated_at' => $traffic->updated_at,
                    'created_at' => $traffic->created_at,
                    'id' => $traffic->id,
                ]
            ];
        });
    
        return response()->json([
            'total_records' => $trafficData->count(),
            'data' => $processedData
        ], 200);
    }

    
    private function createOrUpdateIdentity($ipAddress): Identity
    {
        if (!empty($ipAddress)) {
            // var_dump($ipAddress);
            $identity = Identity::firstOrCreate(
                ['ip_address' => $ipAddress],
                [
                    'ip_address' => $ipAddress,
                    'country_name' => fake()->country,
                ]
            );
        }

        return $identity;
    }
}

