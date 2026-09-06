<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exam\StartExamAttemptRequest;
use App\Http\Requests\Exam\SubmitExamAttemptRequest;
use App\Http\Resources\Exam\ExamAttemptQuestionResource;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamAttemptService;
use App\Support\ExamSectionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamAttemptController extends Controller
{
    public function __construct(
        private readonly ExamAttemptService $attempts,
    ) {}

    public function show(Request $request, Exam $exam): Response
    {
        if (! $exam->isAvailableNow()) {
            abort(403, 'Экзамен недоступен.');
        }

        $user = $request->user();
        $attempt = $this->attempts->findAttempt($exam, $user);

        if ($attempt === null) {
            return redirect()->route('exam.show');
        }

        if ($attempt->status->value === 'submitted') {
            abort(403, 'Вы уже завершили этот экзамен.');
        }

        $questions = $attempt->snapshotQuestions;

        return Inertia::render('exams/Take', [
            'exam' => $this->examPayload($exam),
            'attempt' => $this->attemptPayload($attempt),
            'questions' => ExamAttemptQuestionResource::collection($questions)->resolve(),
            'sections' => ExamSectionCatalog::fromAttemptQuestions($questions),
            'requiresPromoCode' => false,
        ]);
    }

    public function start(StartExamAttemptRequest $request, Exam $exam): RedirectResponse
    {
        $this->attempts->startOrResume(
            $exam,
            $request->user(),
            $request->validated('promo_code'),
        );

        return to_route('exams.take', $exam);
    }

    public function submit(
        SubmitExamAttemptRequest $request,
        Exam $exam,
    ): RedirectResponse {
        $attempt = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $graded = $this->attempts->submit($attempt, $request->validated('answers'));

        return to_route('exams.result', [
            'exam' => $exam,
            'attempt' => $graded,
        ]);
    }

    public function result(Request $request, Exam $exam, ExamAttempt $attempt): Response
    {
        abort_unless($attempt->exam_id === $exam->id, 404);
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_unless($exam->show_results_after_submit, 403);

        $attempt->load([
            'snapshotQuestions.options',
            'answers',
        ]);

        return Inertia::render('exams/Result', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status->value,
                'total_score' => $attempt->total_score,
                'max_score' => $attempt->max_score,
                'submitted_at' => $attempt->submitted_at,
            ],
            'questions' => ExamAttemptQuestionResource::collection(
                $attempt->snapshotQuestions,
            )->resolve(),
            'answers' => $attempt->answers->map(fn ($answer) => [
                'exam_attempt_question_id' => $answer->exam_attempt_question_id,
                'selected_option_ids' => $answer->selected_option_ids,
                'score_awarded' => $answer->score_awarded,
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function examPayload(Exam $exam): array
    {
        $startsAt = $exam->starts_at;

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'description' => $exam->description,
            'duration_minutes' => $exam->duration_minutes,
            'starts_at' => $startsAt,
            'ends_at' => $exam->ends_at,
            'period_label' => $startsAt !== null
                ? mb_convert_case($startsAt->translatedFormat('F'), MB_CASE_TITLE).' '.$startsAt->year
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptPayload(ExamAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'status' => $attempt->status->value,
            'started_at' => $attempt->started_at,
            'max_score' => $attempt->max_score,
        ];
    }
}
