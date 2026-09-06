<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Support\AvailableExamResolver;
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

        $exam = AvailableExamResolver::forUser($user);
        $attempt = AvailableExamResolver::inProgressAttempt($user, $exam);

        if ($exam !== null && $attempt !== null) {
            return to_route('exams.take', $exam);
        }

        return Inertia::render('exam/Show', [
            'exam' => $exam ? $this->examPayload($exam) : null,
            'sections' => ExamSectionCatalog::forUser($user),
            'requiresPromoCode' => true,
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
}
