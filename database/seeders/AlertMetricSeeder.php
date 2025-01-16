<?php

namespace Database\Seeders;

use App\Models\AlertMetric;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlertMetricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AlertMetric::factory()->count(10)->create();
    }
}
