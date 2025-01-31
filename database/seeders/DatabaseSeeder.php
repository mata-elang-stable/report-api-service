<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PrioritySeeder::class,
            // ClassificationSeeder::class,
            // IdentitySeeder::class,
            // SensorSeeder::class,
            // AlertMessageSeeder::class,
            // AlertMetricSeeder::class,
            // SensorMetricSeeder::class,
            // TrafficSeeder::class,
        ]);
    }
}
