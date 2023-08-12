<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hint>
 */
class HintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hint' => fake()->sentence(),
            'question_id' => Question::factory()->create()->id,
            'helpfulness_level' => fake()->numberBetween(1, 10)
        ];
    }
}
