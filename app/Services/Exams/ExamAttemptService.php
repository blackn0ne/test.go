<?php

namespace App\Services\Exams;

use App\Enums\ExamAttemptStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamAttemptQuestionOption;
use App\Models\Question;
use App\Models\User;
use App\Services\PromoCodes\PromoCodeBatchService;
use App\Services\Questions\QuestionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function __construct(
        private readonly QuestionRepository $questions,
        private readonly ExamGrader $grader,
        private readonly ExamPaperGenerator $paperGenerator,
        private readonly PromoCodeBatchService $promoCodes,
    ) {}

    public function findAttempt(Exam $exam, User $user): ?ExamAttempt
    {
        $attempt = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', ExamAttemptStatus::InProgress)
            ->latest('id')
            ->first();

        if ($attempt === null) {
            return null;
        }

        return $attempt->load([
            'snapshotQuestions.options',
            'answers',
        ]);
    }

    public function startOrResume(Exam $exam, User $user, ?string $promoCode = null): ExamAttempt
    {
        if (! $exam->isAvailableNow()) {
            throw ValidationException::withMessages([
                'exam' => 'Экзамен недоступен.',
            ]);
        }

        $inProgress = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', ExamAttemptStatus::InProgress)
            ->first();

        if ($inProgress !== null) {
            return $inProgress->load([
                'snapshotQuestions.options',
                'answers',
            ]);
        }

        return DB::transaction(function () use ($exam, $user, $promoCode): ExamAttempt {
            $attempt = ExamAttempt::query()->create([
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'status' => ExamAttemptStatus::InProgress,
                'started_at' => now(),
                'max_score' => 0,
            ]);

            if ($user->isStudent()) {
                if ($promoCode === null || trim($promoCode) === '') {
                    throw ValidationException::withMessages([
                        'promo_code' => 'Введите промокод.',
                    ]);
                }

                $promo = $this->promoCodes->findRedeemable($promoCode, $user, $exam);
                $this->promoCodes->redeem($promo, $user, $exam, $attempt->id);

                $attempt->update([
                    'promo_code_id' => $promo->id,
                ]);
            }

            if ($exam->isGenerated()) {
                $this->createGeneratedSnapshots($attempt, $exam, $user);
                $maxScore = $this->calculateMaxScoreFromSnapshots($attempt);
            } else {
                $this->createManualSnapshots($attempt, $exam, $user);
                $maxScore = $this->calculateMaxScoreFromSnapshots($attempt);
            }

            $attempt->update([
                'max_score' => $maxScore,
            ]);

            return $attempt->load([
                'snapshotQuestions.options',
                'answers',
            ]);
        });
    }

    /**
     * @param  array<int, array{exam_attempt_question_id: int, selected_option_ids: array<int>}>  $answers
     */
    public function submit(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'Попытка уже завершена.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $answers): ExamAttempt {
            $attempt->load([
                'snapshotQuestions.options',
            ]);

            $totalScore = 0.0;

            foreach ($answers as $answerPayload) {
                $snapshotQuestion = $attempt->snapshotQuestions
                    ->firstWhere('id', $answerPayload['exam_attempt_question_id']);

                if ($snapshotQuestion === null) {
                    continue;
                }

                $selectedOptionIds = collect($answerPayload['selected_option_ids'])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $this->assertSelectedOptionsBelongToQuestion(
                    $snapshotQuestion,
                    $selectedOptionIds,
                );

                $sourceOptionIds = $snapshotQuestion->options
                    ->whereIn('id', $selectedOptionIds)
                    ->pluck('question_option_id')
                    ->all();

                $question = $this->questions->findForGrading($snapshotQuestion->question_id);
                $score = $this->grader->gradeQuestion($question, $sourceOptionIds);

                ExamAttemptAnswer::query()->updateOrCreate(
                    [
                        'exam_attempt_id' => $attempt->id,
                        'exam_attempt_question_id' => $snapshotQuestion->id,
                    ],
                    [
                        'selected_option_ids' => $selectedOptionIds,
                        'score_awarded' => $score,
                        'answered_at' => now(),
                    ],
                );

                $totalScore += $score;
            }

            $attempt->update([
                'status' => ExamAttemptStatus::Submitted,
                'submitted_at' => now(),
                'total_score' => $totalScore,
            ]);

            return $attempt->fresh([
                'snapshotQuestions.options',
                'answers',
            ]);
        });
    }

    private function createGeneratedSnapshots(ExamAttempt $attempt, Exam $exam, User $user): void
    {
        $paper = $this->paperGenerator->generate($exam, $user);

        foreach ($paper as $item) {
            $this->snapshotQuestion($attempt, $item->question, $item->sortOrder, $item->sectionOrder, $item->subjectId, $item->subjectName);
        }
    }

    private function createManualSnapshots(ExamAttempt $attempt, Exam $exam, User $user): void
    {
        $exam->load([
            'examQuestions.question.options' => fn ($query) => $query->orderBy('sort_order'),
            'examQuestions.question.context',
            'examQuestions.question.subject',
        ]);

        $seenQuestionIds = app(StudentQuestionHistory::class)->seenQuestionIds($user);

        $examQuestions = $exam->examQuestions
            ->filter(fn ($examQuestion) => ! in_array($examQuestion->question_id, $seenQuestionIds, true))
            ->values();

        if ($examQuestions->isEmpty()) {
            $examQuestions = $exam->examQuestions;
        }

        foreach ($examQuestions as $examQuestion) {
            $question = $examQuestion->question;

            $this->snapshotQuestion(
                $attempt,
                $question,
                $examQuestion->sort_order,
                sectionOrder: 0,
                subjectId: $question->subject_id,
                subjectName: $question->subject->name,
                pointsOverride: $examQuestion->points_override,
            );
        }
    }

    private function snapshotQuestion(
        ExamAttempt $attempt,
        Question $question,
        int $sortOrder,
        int $sectionOrder,
        int $subjectId,
        string $subjectName,
        ?float $pointsOverride = null,
    ): void {
        $question->loadMissing(['options' => fn ($query) => $query->orderBy('sort_order'), 'context']);
        $context = $question->context;

        $snapshotQuestion = ExamAttemptQuestion::query()->create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_context_id' => $context?->id,
            'subject_id' => $subjectId,
            'subject_name' => $subjectName,
            'section_order' => $sectionOrder,
            'type' => $question->type,
            'body' => $question->body,
            'double_first_prompt' => $question->double_first_prompt,
            'double_second_prompt' => $question->double_second_prompt,
            'context_title' => $context?->title,
            'context_body' => $context?->body,
            'sort_order' => $sortOrder,
        ]);

        foreach ($question->options as $option) {
            ExamAttemptQuestionOption::query()->create([
                'exam_attempt_question_id' => $snapshotQuestion->id,
                'question_option_id' => $option->id,
                'select_group' => $option->select_group,
                'label' => $option->label,
                'content' => $option->content,
                'sort_order' => $option->sort_order,
            ]);
        }
    }

    private function calculateMaxScoreFromSnapshots(ExamAttempt $attempt): float
    {
        $attempt->loadMissing('snapshotQuestions');

        return (float) $attempt->snapshotQuestions->sum(
            fn (ExamAttemptQuestion $question) => $question->type->maxScore(),
        );
    }

    /**
     * @param  array<int>  $selectedSnapshotOptionIds
     */
    private function assertSelectedOptionsBelongToQuestion(
        ExamAttemptQuestion $snapshotQuestion,
        array $selectedSnapshotOptionIds,
    ): void {
        $allowedIds = $snapshotQuestion->options->pluck('id')->all();

        foreach ($selectedSnapshotOptionIds as $optionId) {
            if (! in_array($optionId, $allowedIds, true)) {
                throw ValidationException::withMessages([
                    'answers' => 'Выбран недопустимый вариант ответа.',
                ]);
            }
        }
    }
}
