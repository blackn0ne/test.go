<?php

namespace App\Http\Controllers;

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use App\Support\ExamSectionCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentExamHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isStudent(), 403);

        $user = $request->user();
        $user->loadMissing('direction.subjects');

        $attempts = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', ExamAttemptStatus::Submitted)
            ->with(['exam:id,title,show_results_after_submit'])
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ExamAttempt $attempt): array => [
                'id' => $attempt->id,
                'exam_id' => $attempt->exam_id,
                'exam_title' => $attempt->exam->title,
                'submitted_at' => $attempt->submitted_at,
                'total_score' => (int) round((float) $attempt->total_score),
                'max_score' => (int) $attempt->max_score,
                'can_view_result' => $attempt->exam->show_results_after_submit,
            ]);

        return Inertia::render('exam/History', [
            'sections' => ExamSectionCatalog::forUser($user),
            'attempts' => $attempts,
        ]);
    }
}
