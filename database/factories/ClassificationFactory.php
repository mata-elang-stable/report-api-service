<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Priority;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classification>
 */
class ClassificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'priority_id' => function() {
                if ($priority = Priority::inRandomOrder()->first()) {
                    return $priority->id;
                }
                return Priority::factory()->create()->id;
            },
            'classification' => $this->faker->word(),
        ];
    }
}
