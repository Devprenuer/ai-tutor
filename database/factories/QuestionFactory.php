<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Question;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => fake()->sentence() .'?',
            'topic_id' => Topic::factory()->create()->id,
            'difficulty_level' => fake()->numberBetween(1, 10),
            'question_type' => Question::QUESTION_TYPES['CODING']['ID'],
        ];
    }
}
