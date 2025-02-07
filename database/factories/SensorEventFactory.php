<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class SensorEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}

// {
//     "event_metrics_count": 52,
//     "sensor_id": "sensor-edge.server.fadhilyori.my.id",
//     "snort_priority": "Low",
//     "snort_classification": "none",
//     "snort_message": "(stream_tcp) reset outside window",
//     "snort_protocol": "TCP",
//     "snort_seconds": 1737428400,
//     "metrics": [
//         {
//             "count": 23,
//             "snort_dst_address": "2400:8901:e001:340::1",
//             "snort_src_address": "2600:3c01::f03c:95ff:fea8:f743",
//             "snort_dst_src_port": {
//                 "443:33070": 4,
//                 "443:33090": 3,
//                 "443:33098": 3,
//                 "443:33110": 2,
//                 "443:33120": 4,
//                 "443:33130": 4,
//                 "443:33138": 2,
//                 "443:33140": 1
//             }
//         },
//         {
//             "count": 1,
//             "snort_dst_address": "2400:8901:e001:340::1",
//             "snort_src_address": "2600:3c00::f03c:95ff:fe6e:f224",
//             "snort_dst_src_port": {
//                 "443:36528": 1
//             }
//         },
//         {
//             "count": 28,
//             "snort_dst_address": "2400:8901:e001:340::1",
//             "snort_src_address": "2604:a880:2:d1::2265:2001",
//             "snort_dst_src_port": {
//                 "443:59056": 2,
//                 "443:59072": 3,
//                 "443:59078": 3,
//                 "443:59090": 3,
//                 "443:59098": 3,
//                 "443:59116": 1,
//                 "443:59128": 3,
//                 "443:59148": 4,
//                 "443:59156": 3,
//                 "443:59166": 3
//             }
//         }
//     ]
// },
