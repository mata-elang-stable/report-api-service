<?php

namespace Database\Factories;

use App\Models\Classification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertMessage>
 */
class AlertMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classification_id' => function() {
                if ($classification = Classification::inRandomOrder()->first()) {
                    return $classification->id;
                }
                return Classification::factory()->create()->id;
            },
            'alert_message' => $this->faker->sentence(),
        ];
    }
}
