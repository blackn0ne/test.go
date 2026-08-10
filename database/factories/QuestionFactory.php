<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
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
            'subject_id' => Subject::factory(),
            'type' => QuestionType::Single,
            'body' => '<p>'.$this->faker->sentence().'</p>',
        ];
    }

    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Single,
        ]);
    }

    public function multiple(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Multiple,
        ]);
    }

    public function double(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Double,
        ]);
    }
}
