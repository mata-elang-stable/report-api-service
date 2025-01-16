<?php

namespace Database\Factories;

use App\Models\AlertMessage;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertMessage>
 */
class AlertMetricFactory extends Factory
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
            'alert_id' => function () {
                if ($alertMessage = AlertMessage::inRandomOrder()->first()) {
                    return $alertMessage->id;
                }
                return AlertMessage::factory()->create()->id;
            },
            'count' => $this->faker->randomDigitNotNull(),
        ];
    }
}
