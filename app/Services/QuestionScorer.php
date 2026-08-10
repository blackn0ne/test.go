<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Question;

class QuestionScorer
{
    /**
     * @param  array<int|string>  $selectedOptionIds
     */
    public function score(Question $question, array $selectedOptionIds): float
    {
        if ($question->answer_key !== null) {
            return match ($question->type) {
                QuestionType::Single => $this->scoreSingleFromKey($question->answer_key, $selectedOptionIds),
                QuestionType::Multiple => $this->scoreMultipleFromKey($question->answer_key, $selectedOptionIds),
                QuestionType::Double => $this->scoreDoubleFromKey($question->answer_key, $selectedOptionIds),
            };
        }

        $question->loadMissing('options');

        return match ($question->type) {
            QuestionType::Single => $this->scoreSingle($question, $selectedOptionIds),
            QuestionType::Multiple => $this->scoreMultiple($question, $selectedOptionIds),
            QuestionType::Double => $this->scoreDouble($question, $selectedOptionIds),
        };
    }

    /**
     * @param  array<string, mixed>  $answerKey
     * @param  array<int|string>  $selectedOptionIds
     */
    private function scoreSingleFromKey(array $answerKey, array $selectedOptionIds): float
    {
        /** @var array<int> $correctIds */
        $correctIds = $answerKey['correct_ids'] ?? [];

        if ($correctIds === []) {
            return 0;
        }

        $selectedIds = collect($selectedOptionIds)->map(fn ($id) => (int) $id)->values();

        return $selectedIds->count() === 1 && $selectedIds->first() === $correctIds[0] ? 1 : 0;
    }

    /**
     * @param  array<string, mixed>  $answerKey
     * @param  array<int|string>  $selectedOptionIds
     */
    private function scoreMultipleFromKey(array $answerKey, array $selectedOptionIds): float
    {
        /** @var array<int> $correctIds */
        $correctIds = collect($answerKey['correct_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($correctIds->isEmpty()) {
            return 0;
        }

        $selectedIds = collect($selectedOptionIds)->map(fn ($id) => (int) $id)->unique()->values();

        $correctSelectedCount = $selectedIds->intersect($correctIds)->count();
        $incorrectSelectedCount = $selectedIds->diff($correctIds)->count();

        if ($incorrectSelectedCount > 0) {
            return 0;
        }

        if ($correctSelectedCount === $correctIds->count()) {
            return 2;
        }

        $ratio = $correctSelectedCount / $correctIds->count();

        return $ratio >= 0.5 ? 1 : 0;
    }

    /**
     * @param  array<string, mixed>  $answerKey
     * @param  array<int|string>  $selectedOptionIds
     */
    private function scoreDoubleFromKey(array $answerKey, array $selectedOptionIds): float
    {
        /** @var array{first?: int|null, second?: int|null} $groups */
        $groups = $answerKey['groups'] ?? [];
        $firstCorrectId = isset($groups['first']) ? (int) $groups['first'] : null;
        $secondCorrectId = isset($groups['second']) ? (int) $groups['second'] : null;

        if ($firstCorrectId === null || $secondCorrectId === null) {
            return 0;
        }

        $selectedIds = collect($selectedOptionIds)->map(fn ($id) => (int) $id);

        $firstCorrect = $selectedIds->contains($firstCorrectId);
        $secondCorrect = $selectedIds->contains($secondCorrectId);

        return match (true) {
            $firstCorrect && $secondCorrect => 2,
            $firstCorrect || $secondCorrect => 1,
            default => 0,
        };
    }

    /**
     * @param  array<int|string>  $selectedOptionIds
     */
    private function scoreSingle(Question $question, array $selectedOptionIds): float
    {
        $correctOptionId = $question->options->firstWhere('is_correct', true)?->id;

        if ($correctOptionId === null) {
            return 0;
        }

        return count($selectedOptionIds) === 1 && (int) $selectedOptionIds[0] === $correctOptionId ? 1 : 0;
    }

    /**
     * @param  array<int|string>  $selectedOptionIds
     */
    private function scoreMultiple(Question $question, array $selectedOptionIds): float
    {
        $correctIds = $question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($correctIds->isEmpty()) {
            return 0;
        }

        $selectedIds = collect($selectedOptionIds)->map(fn ($id) => (int) $id)->unique()->values();

        $correctSelectedCount = $selectedIds->intersect($correctIds)->count();
        $incorrectSelectedCount = $selectedIds->diff($correctIds)->count();

        if ($incorrectSelectedCount > 0) {
            return 0;
        }

        if ($correctSelectedCount === $correctIds->count()) {
            return 2;
        }

        $ratio = $correctSelectedCount / $correctIds->count();

        return $ratio >= 0.5 ? 1 : 0;
    }

    /**
     * @param  array<int|string>  $selectedOptionIds
     */
    private function scoreDouble(Question $question, array $selectedOptionIds): float
    {
        $selectedIds = collect($selectedOptionIds)->map(fn ($id) => (int) $id);

        $firstCorrectId = $question->options
            ->where('select_group', 'first')
            ->firstWhere('is_correct', true)
            ?->id;

        $secondCorrectId = $question->options
            ->where('select_group', 'second')
            ->firstWhere('is_correct', true)
            ?->id;

        if ($firstCorrectId === null || $secondCorrectId === null) {
            return 0;
        }

        $firstCorrect = $selectedIds->contains($firstCorrectId);
        $secondCorrect = $selectedIds->contains($secondCorrectId);

        return match (true) {
            $firstCorrect && $secondCorrect => 2,
            $firstCorrect || $secondCorrect => 1,
            default => 0,
        };
    }
}
