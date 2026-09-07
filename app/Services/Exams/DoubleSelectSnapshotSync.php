<?php

namespace App\Services\Exams;

use App\Enums\QuestionType;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamAttemptQuestionOption;
use App\Models\Question;
use App\Support\HtmlContent;
use Illuminate\Support\Facades\DB;

class DoubleSelectSnapshotSync
{
    public function syncIfNeeded(ExamAttemptQuestion $snapshot): bool
    {
        if ($snapshot->type !== QuestionType::Double) {
            return false;
        }

        $sourceQuestion = Question::query()
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
            ->find($snapshot->question_id);

        if ($sourceQuestion === null) {
            return false;
        }

        if (! $this->needsSync($snapshot, $sourceQuestion)) {
            return false;
        }

        $this->sync($snapshot, $sourceQuestion);

        return true;
    }

    /**
     * @return array{synced: int, skipped: int}
     */
    public function syncAll(bool $dryRun = false): array
    {
        $result = ['synced' => 0, 'skipped' => 0];

        ExamAttemptQuestion::query()
            ->where('type', QuestionType::Double)
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('id')
            ->each(function (ExamAttemptQuestion $snapshot) use ($dryRun, &$result): void {
                $sourceQuestion = Question::query()
                    ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
                    ->find($snapshot->question_id);

                if ($sourceQuestion === null || ! $this->needsSync($snapshot, $sourceQuestion)) {
                    $result['skipped']++;

                    return;
                }

                if (! $dryRun) {
                    $this->sync($snapshot, $sourceQuestion);
                }

                $result['synced']++;
            });

        return $result;
    }

    public function needsSync(ExamAttemptQuestion $snapshot, Question $sourceQuestion): bool
    {
        if (! HtmlContent::hasMeaningfulContent($sourceQuestion->double_first_prompt ?? '')
            || ! HtmlContent::hasMeaningfulContent($sourceQuestion->double_second_prompt ?? '')) {
            return false;
        }

        if ($snapshot->double_first_prompt !== $sourceQuestion->double_first_prompt
            || $snapshot->double_second_prompt !== $sourceQuestion->double_second_prompt) {
            return true;
        }

        $snapshotFirstA = $snapshot->options
            ->first(fn (ExamAttemptQuestionOption $option): bool => $option->select_group === 'first' && $option->label === 'A');

        $sourceFirstA = $sourceQuestion->options
            ->first(fn ($option): bool => $option->select_group === 'first' && $option->label === 'A');

        if ($snapshotFirstA === null || $sourceFirstA === null) {
            return true;
        }

        return $snapshotFirstA->content !== $sourceFirstA->content
            || $snapshotFirstA->question_option_id !== $sourceFirstA->id;
    }

    public function sync(ExamAttemptQuestion $snapshot, Question $sourceQuestion): void
    {
        DB::transaction(function () use ($snapshot, $sourceQuestion): void {
            $snapshot->update([
                'double_first_prompt' => $sourceQuestion->double_first_prompt,
                'double_second_prompt' => $sourceQuestion->double_second_prompt,
            ]);

            ExamAttemptQuestionOption::query()
                ->where('exam_attempt_question_id', $snapshot->id)
                ->delete();

            foreach ($sourceQuestion->options as $option) {
                ExamAttemptQuestionOption::query()->create([
                    'exam_attempt_question_id' => $snapshot->id,
                    'question_option_id' => $option->id,
                    'select_group' => $option->select_group,
                    'label' => $option->label,
                    'content' => $option->content,
                    'sort_order' => $option->sort_order,
                ]);
            }
        });
    }
}
