<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Traffic;

class TrafficSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Traffic::factory()->count(10)->create();
    }
}
