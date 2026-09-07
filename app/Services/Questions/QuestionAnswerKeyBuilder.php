<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use App\Models\Question;

class QuestionAnswerKeyBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Question $question): array
    {
        $question->loadMissing('options');

        return match ($question->type) {
            QuestionType::Single, QuestionType::Multiple => [
                'correct_ids' => $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ],
            QuestionType::Double => $this->buildDouble($question),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDouble(Question $question): array
    {
        $firstOptions = $question->options
            ->where('select_group', 'first')
            ->sortBy('sort_order')
            ->values();

        $secondOptions = $question->options
            ->where('select_group', 'second')
            ->sortBy('sort_order')
            ->values();

        $rows = $firstOptions
            ->map(function ($firstOption) use ($secondOptions): ?array {
                $matchLabel = $firstOption->match_label ?? $firstOption->label;
                $secondOption = $secondOptions->firstWhere('label', $matchLabel);

                if ($secondOption === null) {
                    return null;
                }

                return [
                    'first_id' => (int) $firstOption->id,
                    'second_id' => (int) $secondOption->id,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($rows !== []) {
            return ['rows' => $rows];
        }

        return [
            'groups' => [
                'first' => $firstOptions->firstWhere('is_correct', true)?->id,
                'second' => $secondOptions->firstWhere('is_correct', true)?->id,
            ],
        ];
    }

    public function refresh(Question $question): Question
    {
        $question->update([
            'answer_key' => $this->build($question),
        ]);

        return $question->fresh();
    }
}
