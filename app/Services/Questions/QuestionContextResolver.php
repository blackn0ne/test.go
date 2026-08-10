<?php

namespace App\Services\Questions;

use App\Models\QuestionContext;
use App\Support\HtmlContent;
use Illuminate\Validation\ValidationException;

class QuestionContextResolver
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function resolveForSubject(array $input, int $subjectId): ?int
    {
        $mode = $input['context_mode'] ?? 'none';

        if ($mode === 'none' || $mode === '') {
            return null;
        }

        if ($mode === 'existing') {
            $contextId = (int) ($input['context_id'] ?? 0);

            if ($contextId <= 0) {
                throw ValidationException::withMessages([
                    'context_id' => 'Выберите контекст или создайте новый.',
                ]);
            }

            $exists = QuestionContext::query()
                ->whereKey($contextId)
                ->where('subject_id', $subjectId)
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'context_id' => 'Выбранный контекст недоступен для этого предмета.',
                ]);
            }

            return $contextId;
        }

        if ($mode === 'new') {
            $body = (string) ($input['context_body'] ?? '');

            if (! HtmlContent::hasMeaningfulContent($body)) {
                throw ValidationException::withMessages([
                    'context_body' => 'Заполните текст контекста.',
                ]);
            }

            return QuestionContext::query()->create([
                'subject_id' => $subjectId,
                'title' => filled($input['context_title'] ?? null)
                    ? (string) $input['context_title']
                    : null,
                'body' => $body,
            ])->id;
        }

        throw ValidationException::withMessages([
            'context_mode' => 'Некорректный режим контекста.',
        ]);
    }
}
