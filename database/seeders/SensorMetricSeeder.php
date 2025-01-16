<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sensor;
use App\Models\SensorMetric;

class SensorMetricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensors = Sensor::all();
        if ($sensors->isEmpty()) {
            $this->command->info('No sensors found. Please seed sensors first.');
            return;
        }

        SensorMetric::factory()->count(10)->create();
    }
}
