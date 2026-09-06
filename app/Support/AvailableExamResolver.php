<?php

namespace App\Support;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class AvailableExamResolver
{
    public static function forUser(User $user): ?Exam
    {
        return self::matchingQuery($user)
            ->where('status', ExamStatus::Published)
            ->where(function (Builder $builder): void {
                $now = now();
                $builder
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $builder): void {
                $now = now();
                $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();
    }

    public static function forLobby(User $user): ?Exam
    {
        return self::matchingQuery($user)
            ->where('status', '!=', ExamStatus::Archived)
            ->where(function (Builder $builder): void {
                $now = now();
                $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
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

    /**
     * @return Builder<Exam>
     */
    private static function matchingQuery(User $user): Builder
    {
        $query = Exam::query();

        if ($user->direction_id !== null) {
            $query->where(function (Builder $builder) use ($user): void {
                $builder
                    ->whereNull('direction_id')
                    ->orWhere('direction_id', $user->direction_id);
            });
        }

        return $query;
    }
}
