<?php

namespace App\Services\Exams;

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttemptQuestion;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentQuestionHistory
{
    /**
     * @return list<int>
     */
    public function seenQuestionIds(User $user): array
    {
        return ExamAttemptQuestion::query()
            ->whereHas('attempt', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', ExamAttemptStatus::Submitted))
            ->distinct()
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  Collection<int, int>|list<int>  $preferredExclude
     * @return list<int>
     */
    public function mergeExcluded(User $user, array $alreadySelected): array
    {
        return array_values(array_unique([
            ...$this->seenQuestionIds($user),
            ...$alreadySelected,
        ]));
    }
}
