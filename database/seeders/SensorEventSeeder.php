<?php

namespace Database\Seeders;

use App\Models\AlertMessage;
use App\Models\AlertMetric;
use App\Models\Classification;
use App\Models\Identity;
use App\Models\Priority;
use App\Models\Sensor;
use App\Models\SensorMetric;
use App\Models\Traffic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Log;

class SensorEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Initialize start time with current month
        $currentTime = Carbon::now()->startOfMonth()->timestamp;
        $intervalRangeStart = 0;
        $intervalRangeEnd = 3 * 60 * 60; // 1 hours
        $step = 1;

        $data = [
            [
                'event_metrics_count' => 2,
                'sensor_id' => 'sensor-edge.server.fadhilyori.my.id',
                'snort_priority' => 'Low',
                'snort_classification' => 'none',
                'snort_message' => '(stream_tcp) reset outside window',
                'snort_protocol' => 'TCP',
                'snort_seconds' => 1737428400,
                'metrics' => [
                    [
                        'count' => 1,
                        'snort_dst_address' => '192.168.0.100',
                        'snort_src_address' => '192.168.0.101',
                        'snort_dst_src_port' => [
                            '443:33070' => 1,
                        ],
                    ],
                    [
                        'count' => 1,
                        'snort_dst_address' => '192.168.0.102',
                        'snort_src_address' => '192.168.0.101',
                        'snort_dst_src_port' => [
                            '443:36528' => 1,
                        ],
                    ]
                ]
            ],
            [
                'event_metrics_count' => 2,
                'sensor_id' => 'personal-vps',
                'snort_priority' => 'Medium',
                'snort_classification' => 'none',
                'snort_message' => '(arp_spoof) ARP Spoofing',
                'snort_protocol' => 'TCP',
                'snort_seconds' => 1737428400,
                'metrics' => [
                    [
                        'count' => 1,
                        'snort_dst_address' => '192.168.0.103',
                        'snort_src_address' => '192.168.0.104',
                        'snort_dst_src_port' => [
                            '443:33071' => 1,
                        ],
                    ],
                    [
                        'count' => 1,
                        'snort_dst_address' => '192.168.0.104',
                        'snort_src_address' => '192.168.0.101',
                        'snort_dst_src_port' => [
                            '443:36529' => 1,
                        ],
                    ]
                ]
            ],
            [
                'event_metrics_count' => 5,
                'sensor_id' => 'personal-vps',
                'snort_priority' => 'Medium',
                'snort_classification' => 'none',
                'snort_message' => '(arp_spoof) ARP Spoofing',
                'snort_protocol' => 'TCP',
                'snort_seconds' => 1737428400,
                'metrics' => [
                    [
                        'count' => 3,
                        'snort_dst_address' => '192.168.0.100',
                        'snort_src_address' => '192.168.0.101',
                        'snort_dst_src_port' => [
                            '443:33071' => 3,
                        ],
                    ],
                    [
                        'count' => 2,
                        'snort_dst_address' => '192.168.0.103',
                        'snort_src_address' => '192.168.0.102',
                        'snort_dst_src_port' => [
                            '443:36529' => 2,
                        ],
                    ]
                ]
            ],
            [
                'event_metrics_count' => 4,
                'sensor_id' => 'sensor-edge.server.fadhilyori.my.id',
                'snort_priority' => 'High',
                'snort_classification' => 'Web Application Attack',
                'snort_message' => 'Trojan AdWare.Win32.Agent',
                'snort_protocol' => 'TCP',
                'snort_seconds' => 1737428400,
                'metrics' => [
                    [
                        'count' => 2,
                        'snort_dst_address' => '192.168.0.100',
                        'snort_src_address' => '192.168.0.101',
                        'snort_dst_src_port' => [
                            '443:33071' => 2,
                        ],
                    ],
                    [
                        'count' => 2,
                        'snort_dst_address' => '192.168.0.103',
                        'snort_src_address' => '192.168.0.102',
                        'snort_dst_src_port' => [
                            '443:36529' => 2,
                        ],
                    ]
                ]
            ],
        ];

        foreach ($data as $item) {
            $item['snort_seconds'] = $currentTime;

            // TODO: process the data
            $sensor = Sensor::firstOrCreate(
                ['sensor_name' => $item['sensor_id']],
                [
                    'id' => Str::uuid()->toString(),
                    'sensor_name' => $item['sensor_id'],
                ]
            );

            $classification = Classification::firstOrCreate(
                [
                    'classification' => $item['snort_classification'],
                    'priority_id' => Priority::where('name', $item['snort_priority'])->first()->id,
                ]
            );

            $alertMessage = AlertMessage::firstOrCreate(
                [
                    'classification_id' => $classification->id,
                    'alert_message' => $item['snort_message']
                ]
            );

            $alertMetricAttributes = [
                'timestamp' => date('Y-m-d H:i:s', $item['snort_seconds']),
                'sensor_id' => $sensor->id,
                'alert_id' => $alertMessage->id,
            ];

            AlertMetric::incrementOrCreate(
                $alertMetricAttributes,
                'count',
                1,
                $item['event_metrics_count'],
                []
            );

            $sensorMetricAttributes = [
                'timestamp' => date('Y-m-d H:i:s', $item['snort_seconds']),
                'sensor_id' => $sensor->id,
            ];

            SensorMetric::incrementOrCreate(
                $sensorMetricAttributes,
                'count',
                1,
                $item['event_metrics_count'],
                []
            );

            foreach ($item['metrics'] as $metric) {
                Log::info('Processing Metric:', ['metric' => $metric]);

                foreach ($metric['snort_dst_src_port'] as $port => $count) {
                    list($dstPort, $srcPort) = explode(':', $port);

                    $srcIpAddress = $this->createOrUpdateIdentity($metric['snort_src_address']);
                    $dstIpAddress = $this->createOrUpdateIdentity($metric['snort_dst_address']);

                    Traffic::incrementOrCreate(
                     [
                        'timestamp' => date('Y-m-d H:i:s', $item['snort_seconds']),
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
            }

            $currentTime += (rand(floor($intervalRangeStart / $step), floor($intervalRangeEnd / $step)) * $step) * 60 * 60;
        }
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
