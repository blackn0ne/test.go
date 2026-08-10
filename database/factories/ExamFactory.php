<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'group_id' => null,
            'created_by' => User::factory()->admin(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'status' => ExamStatus::Published,
            'duration_minutes' => 60,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'show_results_after_submit' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamStatus::Published,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamStatus::Draft,
        ]);
    }
}
