<?php

namespace Database\Factories;

use App\Models\Sensor;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Traffic>
 */
class TrafficFactory extends Factory
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
            'source_ip' => function () {
                if ($identity = Identity::inRandomOrder()->first()) {
                    return $identity->ip_address;
                }
                return Identity::factory()->create()->ip_address;
            },
            'source_port' => $this->faker->numberBetween(1024, 65535),
            'destination_ip' => function () {
                if ($identity = Identity::inRandomOrder()->first()) {
                    return $identity->ip_address;
                }
                return Identity::factory()->create()->ip_address;
            },
            'destination_port' => $this->faker->numberBetween(1024, 65535),
            'count' => $this->faker->randomDigitNotNull(),
        ];
    }
}
