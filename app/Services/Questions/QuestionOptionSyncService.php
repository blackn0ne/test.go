<?php

namespace App\Services\Questions;

use App\Models\Question;

class QuestionOptionSyncService
{
    public function __construct(
        private readonly QuestionAnswerKeyBuilder $answerKeys,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    public function sync(Question $question, array $options): void
    {
        $question->options()->delete();

        foreach ($options as $index => $option) {
            $question->options()->create([
                'label' => $option['label'],
                'content' => $option['content'],
                'is_correct' => (bool) $option['is_correct'],
                'select_group' => $option['select_group'] ?? null,
                'sort_order' => $option['sort_order'] ?? $index,
            ]);
        }

        $this->answerKeys->refresh($question);
    }
}
