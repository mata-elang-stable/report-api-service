<?php

namespace App\Http\Controllers;

use App\Models\AlertMessage;
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

class ReportController extends Controller
{
    /**
     * Get the complete report as a JSON object.
     *
     * @return JsonResponse
     */
    public function getReport(Request $request): JsonResponse
    {
        $rawJson = $request->getContent();
        $data = json_decode($rawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Decode Error: ', ['error' => json_last_error_msg()]);
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }
    
        $processedData = [];
        $totalEventMetricsCount = 0;
    
        foreach ($data as $event) {
            $sensor = Sensor::firstOrCreate(
                ['sensor_name' => $event['sensor_id']],
                [
                    'id' => Str::uuid()->toString(),
                    'sensor_name' => $event['sensor_id'],
                ]
            );
    
            $priorityName = $this->convertPriorityToName($event['snort_priority']);
            $priority = Priority::firstOrCreate(
                ['name' => $priorityName]
            );
    
            $classification = Classification::firstOrCreate(
                [
                    'classification' => $event['snort_classification'],
                    'priority_id' => $priority->id,
                ]
            );
    
            $alertMessage = AlertMessage::firstOrCreate(
                [
                    'classification_id' => $classification->id,
                    'alert_message' => $event['snort_message']
                ]
            );
    
            $sensorMetric = SensorMetric::firstOrCreate(
                [
                    'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                    'sensor_id' => $sensor->id,
                ],
                [
                    'count' => $event['event_metrics_count'],
                ]
            );
    
            $totalEventMetricsCount += $event['event_metrics_count'];
    
            foreach ($event['metrics'] as $metric) {
                $this->createOrUpdateIdentity($metric['snort_src_address'] ?? null, $metric['snort_src_country'] ?? null);
                $this->createOrUpdateIdentity($metric['snort_dst_address'] ?? null, $metric['snort_dst_country'] ?? null);
    
                $traffic = Traffic::create([
                    'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                    'sensor_id' => $sensor->id,
                    'source_ip' => !empty($metric['snort_src_address']) ? $metric['snort_src_address'] : null, // Use null instead of empty string
                    'source_port' => $metric['snort_src_port'] ?? 0,
                    'destination_ip' => !empty($metric['snort_dst_address']) ? $metric['snort_dst_address'] : null, // Use null instead of empty string
                    'destination_port' => $metric['snort_dst_port'] ?? 0,
                    'count' => $metric['count'],
                ]);
    
                $eventData = [
                    'sensor' => $sensor,
                    'priority' => $priority,
                    'classification' => $classification,
                    'alert_message' => $alertMessage,
                    'sensor_metric' => $sensorMetric,
                    'traffic' => [
                        'timestamp' => $traffic->timestamp,
                        'sensor_id' => $sensor->sensor_name, 
                        'source' => [
                            'ip_address' => $traffic->source_ip ?? '',
                            'port' => $traffic->source_port,
                            'country_name' => $traffic->sourceIdentity->country_name ?? '',
                        ],
                        'destination' => [
                            'ip_address' => $traffic->destination_ip ?? '',
                            'port' => $traffic->destination_port,
                            'country_name' => $traffic->destinationIdentity->country_name ?? '',
                        ],
                        'count' => $traffic->count,
                        'updated_at' => $traffic->updated_at,
                        'created_at' => $traffic->created_at,
                        'id' => $traffic->id,
                    ],
                ];
    
                $processedData[] = $eventData;
            }
        }
    
        return response()->json([
            'total_event_metrics_count' => $totalEventMetricsCount,
            'data' => $processedData
        ], 201);
    }
    
    private function convertPriorityToName(int $priority): string
    {
        return match ($priority) {
            1 => 'High',
            2 => 'Medium',
            3 => 'Low',
            default => 'Informational'
        };
    }
    
    private function createOrUpdateIdentity($ipAddress, $countryName)
    {
        if (!empty($ipAddress)) {
            Identity::firstOrCreate(
                ['ip_address' => $ipAddress],
                [
                    'ip_address' => $ipAddress,
                    'country_name' => $countryName,
                ]
            );
        }
    }
}

