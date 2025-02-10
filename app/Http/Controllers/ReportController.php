<?php

namespace App\Http\Controllers;

use App\Models\AlertMessage;
use App\Models\AlertMetric;
use App\Models\Classification;
use App\Models\Identity;
use App\Models\Priority;
use App\Models\Report;
use App\Models\Sensor;
use App\Models\SensorMetric;
use App\Models\Traffic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDF;

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

            $alertMetricAttributes = [
                'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                'sensor_id' => $sensor->id,
                'alert_id' => $alertMessage->id,
            ];

            $alertMetric = AlertMetric::incrementOrCreate(
                $alertMetricAttributes,
                'count',
                1,
                $event['event_metrics_count'],
                []
                );

            $sensorMetricAttributes = [
                'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                'sensor_id' => $sensor->id,
            ];

            $sensorMetric = SensorMetric::incrementOrCreate(
                $sensorMetricAttributes,
                'count',
                1,
                $event['event_metrics_count'],
                []
            );

            foreach ($event['metrics'] as $metric) {
                Log::info('Processing Metric:', ['metric' => $metric]);

                foreach ($metric['snort_dst_src_port'] as $port => $count) {
                    list($dstPort, $srcPort) = explode(':', $port);

                    $srcIpAddress = $this->createOrUpdateIdentity($metric['snort_src_address']);
                    $dstIpAddress = $this->createOrUpdateIdentity($metric['snort_dst_address']);

                    $traffic = Traffic::incrementOrCreate(
                     [
                        'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                        'sensor_id' => $sensor->id,
                        'source_ip' => $srcIpAddress->ip_address,
                        'source_port' => $srcPort,
                        'destination_ip' => $dstIpAddress->ip_address,
                        'destination_port' => $dstPort,
                    ],
                     'count',
                     1,
                     $count,
                     []);
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
                    'sensor_metric' => [
                        'timestamp' => $sensorMetric->timestamp,
                        'sensor_id' => $sensorMetric->sensor_id,
                        'count' => $sensorMetric->count
                    ],
                    'traffic' => [
                        'id' => $traffic->id,
                        'timestamp' => $traffic->timestamp,
                        'sensor_id' => $traffic->sensor_id,
                        'source_ip' => $traffic->source_ip,
                        'source_port' => $traffic->source_port,
                        'destination_ip' => $traffic->destination_ip,
                        'destination_port' => $traffic->destination_port,
                        'count' => $traffic->count
                    ],
                ];
            }
        }

        return response()->json([
            'message' => 'success',
            'data' => $processedData
        ], 200);
    }

    public function generateReport()
    {
        $alertMetrics = AlertMetric::with('alertMessage.classification.priority')->get();
        $sensorMetrics = SensorMetric::with('sensor')->get();
        $priorities = Priority::all()->pluck('id', 'name')->toArray();
        $traffics = Traffic::with('sourceIdentity', 'destinationidentity')->get();
        $sensors = Sensor::all();
        $totalEvents = 0;

        // Top 10 Priority
        $priorityCounts = [];
        foreach($alertMetrics as $alertMetric) {
            $totalEvents += $alertMetric->count;
            $priorityName = $alertMetric->alertMessage->classification->priority->name;
            if (!isset($priorityCounts[$priorityName])) {
                $priorityCounts[$priorityName] = 0;
            }

            $priorityCounts[$priorityName] += $alertMetric->count;
        }

        // Sort priorities by count in descending order and take top 10
        $priorityCounts = collect($priorityCounts)->sortByDesc(function($count, $priority) {
            return $count;
        })->take(10)->toArray();

        // Top 10 Alert
        $groupedAlertMetrics = $alertMetrics->groupBy(function ($alertMetric) {
            return $alertMetric->alertMessage->classification->priority->name . '|' .
                   $alertMetric->alertMessage->classification->classification . '|' .
                   $alertMetric->alertMessage->alert_message;
        })->map(function ($group) {
            $first = $group->first();
            return [
                'priority' => $first->alertMessage->classification->priority->name,
                'classification' => $first->alertMessage->classification->classification,
                'message' => $first->alertMessage->alert_message,
                'count' => $group->sum('count'),
            ];
        });

        // Sort grouped alert metrics by priority and count
        $sortedAlertMetrics = $groupedAlertMetrics->sort(function($a, $b) use ($priorities) {
            $priorityA = $priorities[$a['priority']] ?? 999;
            $priorityB = $priorities[$b['priority']] ?? 999;
            if ($priorityA === $priorityB) {
                return $b['count'] <=> $a['count'];
            }
            return $priorityA <=> $priorityB;
        });

        $topAlertData = $sortedAlertMetrics->take(10)->values();

        // Top 10 Source IP
        $sourceIpCounts = collect($traffics)->groupBy('source_ip')->map(function ($items, $key) {
            return [
                'sourceIp' => $key,
                'count' => $items->sum('count')
            ];
        });
        $topSourceIps = $sourceIpCounts->sortByDesc('count')->take(10)->values();

        // Top 10 Source Country
        $sourceCountryCounts = collect($traffics)->groupBy(function ($item) {
            return $item->sourceIdentity->country_name ?? 'Unknown';
        })->map(function ($items, $key) {
            return [
                'country' => $key,
                'count' => $items->sum('count')
            ];
        });
        $topSourceCountries = $sourceCountryCounts->sortByDesc('count')->take(10)->values();

        //Top 10 Destination IP
        $destinationIpCounts = collect($traffics)->groupBy('destination_ip')->map(function ($items, $key) {
            return [
                'destinationIp' => $key,
                'count' => $items->sum('count')
            ];
        });
        $topDestinationIps = $destinationIpCounts->sortByDesc('count')->take(10)->values();

        // Top 10 Destination Country
        $destinationCountryCounts = collect($traffics)->groupBy(function ($item) {
            return $item->destinationIdentity->country_name ?? 'Unknown';
        })->map(function ($items, $key) {
            return [
                'country' => $key,
                'count' => $items->sum('count')
            ];
        });
        $topDestinationCountries = $destinationCountryCounts->sortByDesc('count')->take(10)->values();

        // Aggregate counts by sensor
        $sensorCounts = $sensorMetrics->groupBy('sensor_id')->map(function ($items, $key) {
            return [
                'sensor_id' => $key,
                'sensor_name' => $items->first()->sensor->sensor_name,
                'count' => $items->sum('count')
            ];
        });
        $topSensors = $sensorCounts->sortByDesc('count')->take(10)->values();

        // Top 10 Source Port
        $sourcePortCounts = collect($traffics)->groupBy('source_port')->map(function ($items, $key) {
            return [
                'sourcePort' => $key,
                'count' => $items->sum('count')
            ];
        });
        $topSourcePorts = $sourcePortCounts->sortByDesc('count')->take(10)->values();

        //Top 10 Destination Port
        $destinationPortCounts = collect($traffics)->groupBy('destination_port')->map(function ($items, $key) {
            return [
                'destinationPort' => $key,
                'count' => $items->sum('count')
            ];
        });
        $topDestinationPorts = $destinationPortCounts->sortByDesc('count')->take(10)->values();

        foreach ($sensors as $sensor) {
            $sensor->totalEvents = $sensorMetrics->where('sensor_id', $sensor->id)->sum('count');
            $priorityCountsSensor = [];
            foreach ($alertMetrics as $alertMetric) {
                if ($alertMetric->sensor_id === $sensor->id) {
                    $priorityName = $alertMetric->alertMessage->classification->priority->name;
                    if (!isset($priorityCountsSensor[$priorityName])) {
                        $priorityCountsSensor[$priorityName] = 0;
                    }
                    $priorityCountsSensor[$priorityName] += $alertMetric->count;
                }
            }
            $sensor->priorityCounts = $priorityCountsSensor;
            $groupedAlertMetrics = $alertMetrics->where('sensor_id', $sensor->id)->groupBy(function ($alertMetric) {
                return $alertMetric->alertMessage->classification->priority->name . '|' .
                       $alertMetric->alertMessage->classification->classification . '|' .
                       $alertMetric->alertMessage->alert_message;
            })->map(function ($group) {
                $first = $group->first();
                return [
                    'priority' => $first->alertMessage->classification->priority->name,
                    'classification' => $first->alertMessage->classification->classification,
                    'message' => $first->alertMessage->alert_message,
                    'count' => $group->sum('count'),
                ];
            });
            $sortedAlertMetrics = $groupedAlertMetrics->sort(function($a, $b) use ($priorities) {
                $priorityA = $priorities[$a['priority']] ?? 999;
                $priorityB = $priorities[$b['priority']] ?? 999;
                if ($priorityA === $priorityB) {
                    return $b['count'] <=> $a['count'];
                }
                return $priorityA <=> $priorityB;
            });
            $sensor->topAlertData = $sortedAlertMetrics->take(10)->values();
            $sourceIpCounts = collect($traffics)->where('sensor_id', $sensor->id)->groupBy('source_ip')->map(function ($items, $key) {
                return [
                    'sourceIp' => $key,
                    'count' => $items->sum('count')
                ];
            });
            $sensor->topSourceIps = $sourceIpCounts->sortByDesc('count')->take(10)->values();
            $sourceCountryCounts = collect($traffics)->where('sensor_id', $sensor->id)->groupBy(function ($item) {
                return $item->sourceIdentity->country_name ?? 'Unknown';
            })->map(function ($items, $key) {
                return [
                    'country' => $key,
                    'count' => $items->sum('count')
                ];
            });
            $sensor->topSourceCountries = $sourceCountryCounts->sortByDesc('count')->take(10)->values();
            $destinationIpCounts = collect($traffics)->where('sensor_id', $sensor->id)->groupBy('destination_ip')->map(function ($items, $key) {
                return [
                    'destinationIp' => $key,
                    'count' => $items->sum('count')
                ];
            });
            $sensor->topDestinationIps = $destinationIpCounts->sortByDesc('count')->take(10)->values();
            $destinationCountryCounts = collect($traffics)->where('sensor_id', $sensor->id)->groupBy(function ($item) {
                return $item->destinationIdentity->country_name ?? 'Unknown';
            })->map(function ($items, $key) {
                return [
                    'country' => $key,
                    'count' => $items->sum('count')
                ];
            });
            $sensor->topDestinationCountries = $destinationCountryCounts->sortByDesc('count')->take(10)->values();
            $sourcePortCounts = collect($traffics)->where('sensor_id', $sensor->id)->groupBy('source_port')->map(function ($items, $key) {
                return [
                    'sourcePort' => $key,
                    'count' => $items->sum('count')
                ];
            });
            $sensor->topSourcePorts = $sourcePortCounts->sortByDesc('count')->take(10)->values();
            $destinationPortCounts = collect($traffics)->where('sensor_id', $sensor->id)->groupBy('destination_port')->map(function ($items, $key) {
                return [
                    'destinationPort' => $key,
                    'count' => $items->sum('count')
                ];
            });
            $sensor->topDestinationPorts = $destinationPortCounts->sortByDesc('count')->take(10)->values();
        };

        $data = [
            'alertMetrics' => $alertMetrics,
            'totalEvents' => $totalEvents,
            'priorityCounts' => $priorityCounts,
            'topAlertData' => $topAlertData,
            'topSourceIps' => $topSourceIps,
            'topSourceCountries' => $topSourceCountries,
            'topDestinationIps' => $topDestinationIps,
            'topDestinationCountries' => $topDestinationCountries,
            'topSensors' => $topSensors,
            'topSourcePorts' => $topSourcePorts,
            'topDestinationPorts' => $topDestinationPorts,
            'sensors' => $sensors,
        ];

        Report::create([
            'template_id' => 'asdasdasd',
            'data' => $data,
        ]);

        // dd($sensors);

        // return view('reports.alert_report', [
        //     'alertMetrics' => $alertMetrics,
        //     'totalEvents' => $totalEvents,
        //     'priorityCounts' => $priorityCounts,
        //     'topAlertData' => $topAlertData,
        //     'topSourceIps' => $topSourceIps,
        //     'topSourceCountries' => $topSourceCountries,
        //     'topDestinationIps' => $topDestinationIps,
        //     'topDestinationCountries' => $topDestinationCountries,
        //     'topSensors' => $topSensors,
        //     'topSourcePorts' => $topSourcePorts,
        //     'topDestinationPorts' => $topDestinationPorts,
        //     'sensors' => $sensors,
        // ]);

        // $css = File::get(public_path('css/report-style.css'));

        // $pdf = PDF::loadView('reports.alert_report', [
        //     'css' => $css,
        //     'alertMetrics' => $alertMetrics,
        //     'totalEvents' => $totalEvents,
        //     'priorityCounts' => $priorityCounts,
        //     'topAlertData' => $topAlertData,
        //     'topSourceIps' => $topSourceIps,
        //     'topSourceCountries' => $topSourceCountries,
        //     'topDestinationIps' => $topDestinationIps,
        //     'topDestinationCountries' => $topDestinationCountries,
        //     'topSensors' => $topSensors,
        //     'topSourcePorts' => $topSourcePorts,
        //     'topDestinationPorts' => $topDestinationPorts,
        //     'sensors' => $sensors,
        // ]);

        // $pdf->setPaper('A4', 'portrait');
        // $pdf->setOptions([
        //     'isHtml5ParserEnabled' => true,
        //     'isPhpEnabled' => true,
        //     'isRemoteEnabled' => true,
        //     'dpi' => 150,
        //     'defaultFont' => 'sans-serif'
        // ]);

        // return $pdf->download('alert_report.pdf');

        // dd($priorityCounts);

        return response()->json([
            'message' => 'success',
            'data' => json_encode($data)
        ], 200);
    }

    public function downloadReport($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        // Check if $report->data is already an array
        $data = is_array($report->data) ? $report->data : json_decode($report->data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Invalid report data'], 400);
        }

        // dd($data);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.alert_report', $data);

        return $pdf->download('report_' . $report->id . '.pdf');
    }

    public function getAllReports()
    {
        $reports = Report::all();

        return view('dashboard', compact('reports'));
    }

    public function getReportTemplate($id) {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        return response()->json([
            'message' => 'success',
            'data' => [
                'template_id' => $report->template_id,
                'data' => $report->data
            ],
            'created_at' => $report->created_at,
        ], 200);
    }

    private function createOrUpdateIdentity($ipAddress): Identity
    {
        if (!empty($ipAddress)) {
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

