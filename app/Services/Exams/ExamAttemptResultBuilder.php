<?php

namespace App\Services\Exams;

use App\Enums\QuestionType;
use App\Http\Resources\Exam\ExamAttemptQuestionResource;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\Question;
use App\Services\Questions\QuestionAnswerKeyBuilder;
use App\Support\ExamSectionCatalog;

final class ExamAttemptResultBuilder
{
    public function __construct(
        private readonly QuestionAnswerKeyBuilder $answerKeyBuilder,
    ) {}

    /**
     * @return array{
     *     sections: list<array<string, mixed>>,
     *     questions: list<array<string, mixed>>
     * }
     */
    public function build(ExamAttempt $attempt): array
    {
        $attempt->loadMissing([
            'snapshotQuestions.options',
            'answers',
        ]);

        $sourceQuestions = Question::query()
            ->whereIn('id', $attempt->snapshotQuestions->pluck('question_id'))
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
            ->get()
            ->keyBy('id');

        $answersByQuestionId = $attempt->answers->keyBy('exam_attempt_question_id');

        $questions = $attempt->snapshotQuestions
            ->sortBy('sort_order')
            ->values()
            ->map(function (ExamAttemptQuestion $snapshot) use ($sourceQuestions, $answersByQuestionId): array {
                $sourceQuestion = $sourceQuestions->get($snapshot->question_id);
                $correctSourceIds = $this->resolveCorrectSourceOptionIds($sourceQuestion);
                $correctSnapshotIds = $snapshot->options
                    ->whereIn('question_option_id', $correctSourceIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                $answer = $answersByQuestionId->get($snapshot->id);

                return [
                    ...(new ExamAttemptQuestionResource($snapshot))->resolve(),
                    'max_score' => $snapshot->type->maxScore(),
                    'score_awarded' => $answer?->score_awarded !== null
                        ? (float) $answer->score_awarded
                        : 0.0,
                    'selected_option_ids' => $answer?->selected_option_ids ?? [],
                    'correct_option_ids' => $correctSnapshotIds,
                ];
            })
            ->all();

        $sections = ExamSectionCatalog::fromAttemptQuestions($attempt->snapshotQuestions);

        foreach ($sections as $index => $section) {
            $sectionQuestions = collect($questions)
                ->where('section_order', $section['order']);

            $sections[$index]['score'] = round((float) $sectionQuestions->sum('score_awarded'), 1);
            $sections[$index]['max_score'] = (int) $sectionQuestions->sum('max_score');
        }

        return [
            'sections' => $sections,
            'questions' => $questions,
        ];
    }

    /**
     * @return list<int>
     */
    private function resolveCorrectSourceOptionIds(?Question $question): array
    {
        if ($question === null) {
            return [];
        }

        $answerKey = $question->answer_key ?? $this->answerKeyBuilder->build($question);

        return match ($question->type) {
            QuestionType::Single, QuestionType::Multiple => collect($answerKey['correct_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            QuestionType::Double => array_values(array_filter([
                isset($answerKey['groups']['first']) ? (int) $answerKey['groups']['first'] : null,
                isset($answerKey['groups']['second']) ? (int) $answerKey['groups']['second'] : null,
            ])),
        };
    }
}
