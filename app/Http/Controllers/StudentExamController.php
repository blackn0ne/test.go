<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Support\AvailableExamResolver;
use App\Support\ExamPeriodFormatter;
use App\Support\ExamSectionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentExamController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        $lobbyExam = AvailableExamResolver::forLobby($user);
        $exam = AvailableExamResolver::forUser($user);
        $attempt = AvailableExamResolver::inProgressAttempt($user, $exam ?? $lobbyExam);

        if ($exam !== null && $attempt !== null) {
            return to_route('exams.take', $exam);
        }

        return Inertia::render('exam/Show', [
            'exam' => $exam ? $this->examPayload($exam) : null,
            'sections' => ExamSectionCatalog::forUser($user),
            'requiresPromoCode' => true,
            'lobby' => [
                'title' => $lobbyExam?->title ?? 'ЕНТ',
                'period_label' => ExamPeriodFormatter::currentMonthYearKazakh(),
                'available' => $exam !== null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function examPayload(Exam $exam): array
    {
        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'description' => $exam->description,
            'duration_minutes' => $exam->duration_minutes,
            'starts_at' => $exam->starts_at,
            'ends_at' => $exam->ends_at,
            'period_label' => ExamPeriodFormatter::forExam($exam),
        ];
    }
}
