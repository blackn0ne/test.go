<?php

namespace App\Services\Questions;

use App\Models\Question;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class QuestionRepository
{
    /**
     * @return LengthAwarePaginator<int, Question>
     */
    public function paginateForAdmin(
        ?int $subjectId = null,
        ?string $type = null,
        ?string $search = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        return Question::query()
            ->select(['id', 'subject_id', 'type', 'body', 'created_at'])
            ->with('subject:id,name')
            ->when($subjectId, fn (Builder $query) => $query->where('subject_id', $subjectId))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($search, function (Builder $query, string $search): void {
                $query->where('body', 'like', '%'.$search.'%');
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForGrading(int $questionId): Question
    {
        return Question::query()
            ->select(['id', 'type', 'answer_key'])
            ->findOrFail($questionId);
    }
}
