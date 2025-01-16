<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Priority;

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = ['High', 'Medium', 'Low', 'Informational'];

        foreach ($priorities as $priority) {
            Priority::create(['name' => $priority]);
        }
    }
}
