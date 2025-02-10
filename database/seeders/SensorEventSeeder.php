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
use Faker\Factory as Faker;

class SensorEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Define the start and end timestamps
        $startTime = Carbon::now()->subMonths(3)->endOfMonth()->startOfDay()->timestamp;
        $endTime = Carbon::now()->timestamp;

        $sensorIds = ['sensor-edge.server.fadhilyori.my.id', 'personal-vps'];
        $priorities = ['Low', 'Medium', 'High'];
        $classifications = ['none', 'Web Application Attack'];
        $messages = ['(stream_tcp) reset outside window', '(arp_spoof) ARP Spoofing', 'Trojan AdWare.Win32.Agent'];
        $protocols = ['TCP', 'UDP'];

        $data = [];

        for ($i = 0; $i < 25; $i++) {
            $metrics = [];
            for ($j = 0; $j < $faker->numberBetween(1, 5); $j++) {
                $metrics[] = [
                    'count' => $faker->numberBetween(2, 10),
                    'snort_dst_address' => $faker->ipv4,
                    'snort_src_address' => $faker->ipv4,
                    'snort_dst_src_port' => [
                        $faker->numberBetween(1, 65535) . ':' . $faker->numberBetween(1, 65535) => $faker->numberBetween(1, 5),
                    ],
                ];
            }

            $data[] = [
                'event_metrics_count' => count($metrics),
                'sensor_id' => $faker->randomElement($sensorIds),
                'snort_priority' => $faker->randomElement($priorities),
                'snort_classification' => $faker->randomElement($classifications),
                'snort_message' => $faker->randomElement($messages),
                'snort_protocol' => $faker->randomElement($protocols),
                'metrics' => $metrics,
            ];
        }

        $currentTime = $startTime;

        foreach ($data as $item) {
            $item['snort_seconds'] = $currentTime;

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
                $item['event_metrics_count']
            );

            $sensorMetricAttributes = [
                'timestamp' => date('Y-m-d H:i:s', $item['snort_seconds']),
                'sensor_id' => $sensor->id,
            ];

            SensorMetric::incrementOrCreate(
                $sensorMetricAttributes,
                'count',
                $item['event_metrics_count']
            );

            foreach ($item['metrics'] as $metric) {
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
                     $count,
                    );
                }
            }

            $currentTime += rand(1, 12) * 24 * 60 * 60;

            if ($currentTime > $endTime) {
                $currentTime = $startTime;
            }
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
