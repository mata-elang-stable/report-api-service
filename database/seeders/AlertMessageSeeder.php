<?php

namespace Database\Seeders;

use App\Models\AlertMessage;
use App\Models\AlertMetric;
use App\Models\Sensor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlertMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alertMessages = AlertMessage::factory()->count(10)->create();

        // Ensure there are sensors in the database
        $sensors = Sensor::all();
        if ($sensors->isEmpty()) {
            $this->command->info('No sensors found. Please seed sensors first.');
            return;
        }

        foreach ($alertMessages as $alertMessage) {
            $sensor = $sensors->random();

            AlertMetric::factory()->create([
                'timestamp' => now(),
                'sensor_id' => $sensor->id,
                'alert_id' => $alertMessage->id,
                'count' => rand(1, 10),
            ]);
        }
    }
}
