<?php

namespace App\Jobs;

use App\Models\AlertMetric;
use App\Models\Priority;
use App\Models\Report;
use App\Models\Sensor;
use App\Models\SensorMetric;
use App\Models\Traffic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $startDate;
    public $endDate;
    protected $templateName;

    /**
     * Create a new job instance.
     */
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $alertMetrics = AlertMetric::with('alertMessage.classification.priority')
                ->whereBetween('timestamp', [$this->startDate, $this->endDate])
                ->get();

            $sensorMetrics = SensorMetric::with('sensor')
                ->whereBetween('timestamp', [$this->startDate, $this->endDate])
                ->get();

            $priorities = Priority::all()->pluck('id', 'name')->toArray();

            $traffics = Traffic::with('sourceIdentity', 'destinationIdentity')
                ->whereBetween('timestamp', [$this->startDate, $this->endDate])->get();

            // Convert numeric IPs to valid IP address strings
            $traffics = $traffics->map(function ($traffic) {
                if (is_numeric($traffic->source_ip)) {
                    $traffic->source_ip = long2ip($traffic->source_ip);
                }
                if (is_numeric($traffic->destination_ip)) {
                    $traffic->destination_ip = long2ip($traffic->destination_ip);
                }
                return $traffic;
            });

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
                // Ensure the key is a valid IP address string
                if (filter_var($key, FILTER_VALIDATE_IP)) {
                    return [
                        'sourceIp' => $key,
                        'count' => $items->sum('count')
                    ];
                }
                return null;
            })->filter(); // Remove invalid IPs
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
                // Ensure the key is a valid IP address string
                if (filter_var($key, FILTER_VALIDATE_IP)) {
                    return [
                        'destinationIp' => $key,
                        'count' => $items->sum('count')
                    ];
                }
                return null;
            })->filter(); // Remove invalid IPs
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
                'template_id' => $this->templateName,
                'data' => $data,
            ]);

            Log::info('Report generated');
        } catch (\Exception $e) {
            Log::error('Error generating report: ' . $e);
        }
    }
}
