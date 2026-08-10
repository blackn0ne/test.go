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
            QuestionType::Double => [
                'groups' => [
                    'first' => $question->options
                        ->where('select_group', 'first')
                        ->firstWhere('is_correct', true)
                        ?->id,
                    'second' => $question->options
                        ->where('select_group', 'second')
                        ->firstWhere('is_correct', true)
                        ?->id,
                ],
            ],
        };
    }

    public function refresh(Question $question): Question
    {
        $question->update([
            'answer_key' => $this->build($question),
        ]);

        return $question->fresh();
    }
}
