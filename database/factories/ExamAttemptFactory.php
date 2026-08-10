<?php

namespace Database\Factories;

use App\Enums\ExamAttemptStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory()->published(),
            'user_id' => User::factory(),
            'status' => ExamAttemptStatus::InProgress,
            'started_at' => now(),
            'submitted_at' => null,
            'total_score' => null,
            'max_score' => null,
        ];
    }
}
