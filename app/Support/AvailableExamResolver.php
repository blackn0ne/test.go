<?php

namespace App\Support;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;

final class AvailableExamResolver
{
    public static function forUser(User $user): ?Exam
    {
        $query = Exam::query()
            ->where('status', ExamStatus::Published)
            ->where(function ($builder): void {
                $now = now();
                $builder
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($builder): void {
                $now = now();
                $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });

        if ($user->direction_id !== null) {
            $query->where(function ($builder) use ($user): void {
                $builder
                    ->whereNull('direction_id')
                    ->orWhere('direction_id', $user->direction_id);
            });
        }

        return $query
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();
    }

    public static function inProgressAttempt(User $user, ?Exam $exam): ?ExamAttempt
    {
        if ($exam === null) {
            return null;
        }

        return ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', ExamAttemptStatus::InProgress)
            ->first();
    }
}
