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
use Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

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
        $event = json_decode($rawJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Decode Error:', ['error' => json_last_error_msg(), 'raw_json' => $rawJson]);
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        if (!is_array($event)) {
            Log::error('Decoded JSON is not an array', ['decoded' => $event]);
            return response()->json(['error' => 'Invalid JSON structure'], 400);
        }

        $requiredKeys = ['sensor_id', 'snort_priority', 'snort_classification', 'snort_message', 'snort_seconds', 'metrics'];

        foreach ($requiredKeys as $key) {
            if (!isset($event[$key])) {
                Log::error("Missing required key: $key", ['event' => $event]);
                return response()->json(['error' => "Missing required key: $key"], 400);
            }
        }

        $sensor = Sensor::firstOrCreate(
            ['sensor_name' => $event['sensor_id']],
            [
                'id' => Str::uuid()->toString(),
                'sensor_name' => $event['sensor_id'],
            ]
        );

        $priority = Priority::where('name', $event['snort_priority'])->first();
        if (!$priority) {
            Log::error('Invalid snort priority', ['priority' => $event['snort_priority']]);
            return response()->json(['error' => 'Invalid snort priority'], 400);
        }

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

        $trafficData = [];

        foreach ($event['metrics'] as $metric) {
            // Log::info('Processing Metric:', ['metric' => $metric]);

            if (!isset($metric['snort_dst_src_port']) || !is_array($metric['snort_dst_src_port'])) {
                Log::error('Invalid metric format', ['metric' => $metric]);
                return response()->json(['error' => 'Invalid metric format'], 400);
            }

            foreach ($metric['snort_dst_src_port'] as $port => $count) {
                list($dstPort, $srcPort) = explode(':', $port);

                $srcIpAddress = $this->createOrUpdateIdentity($metric['snort_src_address']);
                $dstIpAddress = $this->createOrUpdateIdentity($metric['snort_dst_address']);

                $traffic = Traffic::incrementOrCreate(
                    [
                        'timestamp' => date('Y-m-d H:i:s', $event['snort_seconds']),
                        'sensor_id' => $sensor->id,
                        'source_ip' => $srcIpAddress->id,
                        'source_port' => $srcPort,
                        'destination_ip' => $dstIpAddress->id,
                        'destination_port' => $dstPort,
                    ],
                    'count',
                    1,
                    $count,
                    []
                );

                $trafficData[] = [
                    'id' => $traffic->id,
                    'timestamp' => $traffic->timestamp,
                    'sensor_id' => $traffic->sensor_id,
                    'source_ip' => $traffic->source_ip,
                    'source_port' => $traffic->source_port,
                    'destination_ip' => $traffic->destination_ip,
                    'destination_port' => $traffic->destination_port,
                    'count' => $traffic->count
                ];
            }
        }

        return response()->json([
            'message' => 'success',
            'data' => [
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
                'traffic' => $trafficData,
            ]
        ], 200);
    }

    public function generateReport()
    {
        try {
            $alertMetrics = AlertMetric::with('alertMessage.classification.priority')
                ->whereBetween('timestamp', [Carbon::now()->subMonth(), Carbon::now()])
                ->get();
            $sensorMetrics = SensorMetric::with('sensor')
                ->whereBetween('timestamp', [Carbon::now()->subMonth(), Carbon::now()])
                ->get();
            $priorities = Priority::all()->pluck('id', 'name')->toArray();
            $traffics = Traffic::with('sourceIdentity', 'destinationIdentity')
                ->whereBetween('timestamp', [Carbon::now()->subMonth(), Carbon::now()])
                ->get();
            $sensors = Sensor::all();
            $totalEvents = 0;

            Log::info('Generating report');

            // Top 10 Priority
            $priorityCounts = [];
            foreach ($alertMetrics as $alertMetric) {
                $totalEvents += $alertMetric->count;
                $priorityName = $alertMetric->alertMessage->classification->priority->name;
                if (!isset($priorityCounts[$priorityName])) {
                    $priorityCounts[$priorityName] = 0;
                }
                $priorityCounts[$priorityName] += $alertMetric->count;
            }

            // Sort priorities by count in descending order and take top 10
            $priorityCounts = collect($priorityCounts)->sortByDesc(function ($count, $priority) {
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
            $sortedAlertMetrics = $groupedAlertMetrics->sort(function ($a, $b) use ($priorities) {
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

            // Top 10 Destination IP
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

            // Top 10 Destination Port
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
                        $alertMetric->alertMessage->classification . '|' .
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
                $sortedAlertMetrics = $groupedAlertMetrics->sort(function ($a, $b) use ($priorities) {
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

            // Prepare the report data
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

            Report::query()->create([
                'template_id' => 'generate_report',
                'data' => $data,
            ]);

            Log::info('Report generated');
        } catch (\Exception $e) {
            Log::error('Error generating report: ' . $e);
        }
    }

    public function index()
    {
        $reports = Report::orderBy('created_at', 'desc')->get();

        return view('dashboard', compact('reports'));
    }

    public function downloadReport($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }
        $data = is_array($report->data) ? $report->data : json_decode($report->data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Invalid report data'], 400);
        }

        $reportsPath = storage_path('app/reports');
        if (!File::exists($reportsPath)) {
            File::makeDirectory($reportsPath, 0755, true);
        }

        $pdfPath = storage_path('app/reports/report_' . $id . '.pdf');
        $url = URL::signedRoute('reports.view', ['id' => $id]);
        $headerHtml = view('reports.alert_report_header')->render();
        $chromiumIpAddress = env('CHROMIUM_IP_ADDRESS');
        $chromiumPort = env('CHROMIUM_PORT');

        Browsershot::url($url)
            ->setRemoteInstance('192.168.0.100', '9222')
            ->waitUntilNetworkIdle()
            ->format('A4')
            ->showBackground()
            ->savePdf($pdfPath);

        return Response::download($pdfPath)->deleteFileAfterSend();
    }

    public function viewReport($id)
    {
        $report = Report::find($id);

        if (!$report) {
            abort(404, 'Report not found');
        }

        $data = is_array($report->data) ? $report->data : json_decode($report->data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(400, 'Invalid report data');
        }

        return view('reports.alert_report', ['data' => $data]);
    }

    public function destroy($id)
    {
        try {
            $report = Report::findOrFail($id);
            $report->delete();

            Log::info('Report deleted successfully', ['id' => $id]);

            return response()->json(['message' => 'Report deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting report', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Error deleting report'], 500);
        }
    }

    private function createOrUpdateIdentity($ipAddress): Identity
    {
        $lookupUrl = env('LOOKUP_URL');
        if (!empty($ipAddress)) {
            $response = Http::get($lookupUrl, ['ip' => $ipAddress]);

            if ($response->successful()) {
                $data = $response->json();
                $country = $data['region']['country']['names']['en'] ?? 'Unknown';
            } else {
                $country = 'Unknown';
            }

            $identity = Identity::firstOrCreate(
                [
                    'ip_address' => $ipAddress,
                    'country_name' => $country,
                ],
                [
                    'ip_address' => $ipAddress,
                    'country_name' => $country,
                ]
            );

        }

        return $identity;
    }
}

