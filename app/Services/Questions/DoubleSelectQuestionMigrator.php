<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Support\HtmlContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoubleSelectQuestionMigrator
{
    public function __construct(
        private QuestionAnswerKeyBuilder $answerKeyBuilder,
    ) {}

    /**
     * @return array{migrated: int, skipped: int, failed: int, messages: list<string>}
     */
    public function migrate(bool $dryRun = false, bool $force = false): array
    {
        $result = [
            'migrated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'messages' => [],
        ];

        Question::query()
            ->where('type', QuestionType::Double)
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('id')
            ->each(function (Question $question) use ($dryRun, $force, &$result): void {
                $outcome = $this->migrateQuestion($question, $dryRun, $force);

                $result[$outcome['status']]++;
                $result['messages'][] = $outcome['message'];
            });

        return $result;
    }

    /**
     * @return array{status: 'migrated'|'skipped'|'failed', message: string}
     */
    public function migrateQuestion(Question $question, bool $dryRun = false, bool $force = false): array
    {
        $question->loadMissing(['options' => fn ($query) => $query->orderBy('sort_order')]);

        if (! $force && ! $this->needsMigration($question)) {
            return [
                'status' => 'skipped',
                'message' => "Question #{$question->id}: already migrated, skipped.",
            ];
        }

        $firstOptions = $question->options
            ->where('select_group', 'first')
            ->keyBy('label');

        $secondOptions = $question->options
            ->where('select_group', 'second')
            ->sortBy('sort_order')
            ->values();

        if ($secondOptions->isEmpty()) {
            return [
                'status' => 'failed',
                'message' => "Question #{$question->id}: second select group is empty.",
            ];
        }

        $headerA = $firstOptions->get('A');
        $headerB = $firstOptions->get('B');

        if ($headerA === null || $headerB === null) {
            return [
                'status' => 'failed',
                'message' => "Question #{$question->id}: first select group is missing options A or B.",
            ];
        }

        if (! HtmlContent::hasMeaningfulContent($headerA->content)
            || ! HtmlContent::hasMeaningfulContent($headerB->content)) {
            return [
                'status' => 'failed',
                'message' => "Question #{$question->id}: first select options A/B must contain header text.",
            ];
        }

        $firstCorrectLabel = $this->resolveCorrectLabel($headerA, $secondOptions);
        $secondCorrectLabel = $this->resolveCorrectLabel($headerB, $secondOptions);

        if ($firstCorrectLabel === null || $secondCorrectLabel === null) {
            return [
                'status' => 'failed',
                'message' => "Question #{$question->id}: could not determine correct answers for one or both selects.",
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'migrated',
                'message' => "Question #{$question->id}: would migrate (A → prompt, B → prompt, pool from second select).",
            ];
        }

        DB::transaction(function () use (
            $question,
            $headerA,
            $headerB,
            $firstOptions,
            $secondOptions,
            $firstCorrectLabel,
            $secondCorrectLabel,
        ): void {
            $question->update([
                'double_first_prompt' => $headerA->content,
                'double_second_prompt' => $headerB->content,
            ]);

            QuestionOption::query()
                ->where('question_id', $question->id)
                ->where('select_group', 'first')
                ->delete();

            foreach ($secondOptions as $index => $template) {
                QuestionOption::query()->create([
                    'question_id' => $question->id,
                    'select_group' => 'first',
                    'label' => $template->label,
                    'content' => $template->content,
                    'is_correct' => $template->label === $firstCorrectLabel,
                    'match_label' => null,
                    'sort_order' => $index,
                ]);
            }

            foreach ($secondOptions as $index => $option) {
                $option->update([
                    'is_correct' => $option->label === $secondCorrectLabel,
                    'match_label' => null,
                    'sort_order' => $index,
                ]);
            }

            $this->answerKeyBuilder->refresh($question->fresh(['options']));
        });

        return [
            'status' => 'migrated',
            'message' => "Question #{$question->id}: migrated successfully.",
        ];
    }

    public function needsMigration(Question $question): bool
    {
        $question->loadMissing(['options' => fn ($query) => $query->orderBy('sort_order')]);

        $firstA = $question->options
            ->first(fn (QuestionOption $option): bool => $option->select_group === 'first' && $option->label === 'A');

        $secondA = $question->options
            ->first(fn (QuestionOption $option): bool => $option->select_group === 'second' && $option->label === 'A');

        if ($firstA === null || $secondA === null) {
            return true;
        }

        if ($firstA->content !== $secondA->content) {
            return true;
        }

        return ! HtmlContent::hasMeaningfulContent($question->double_first_prompt ?? '')
            || ! HtmlContent::hasMeaningfulContent($question->double_second_prompt ?? '');
    }

    /**
     * @param  Collection<int, QuestionOption>  $secondOptions
     */
    private function resolveCorrectLabel(QuestionOption $headerOption, Collection $secondOptions): ?string
    {
        if ($headerOption->match_label !== null
            && $secondOptions->contains('label', $headerOption->match_label)) {
            return $headerOption->match_label;
        }

        $correctFromSecond = $secondOptions->firstWhere('is_correct', true);

        if ($correctFromSecond !== null) {
            return $correctFromSecond->label;
        }

        return null;
    }
}
