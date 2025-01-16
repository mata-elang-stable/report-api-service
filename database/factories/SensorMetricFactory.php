<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Sensor;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SensorMetric>
 */
class SensorMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timestamp' => $this->faker->dateTime(),
            'sensor_id' => function () {
                if ($sensor = Sensor::inRandomOrder()->first()) {
                    return $sensor->id;
                }
                return Sensor::factory()->create()->id;
            },
            'count' => $this->faker->randomDigitNotNull(),
        ];
    }
}
