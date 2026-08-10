<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamRequest;
use App\Models\Exam;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/exams/Index', [
            'exams' => Exam::query()
                ->with(['subject:id,name', 'creator:id,name'])
                ->withCount('examQuestions')
                ->latest('id')
                ->paginate(25)
                ->through(fn (Exam $exam) => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'status' => $exam->status->value,
                    'status_label' => $exam->status->label(),
                    'subject' => $exam->subject->only(['id', 'name']),
                    'questions_count' => $exam->exam_questions_count,
                    'starts_at' => $exam->starts_at,
                    'ends_at' => $exam->ends_at,
                    'creator' => $exam->creator->only(['id', 'name']),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/exams/Create', [
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(ExamStatus::cases())->map(fn (ExamStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $exam = Exam::query()->create([
                'subject_id' => $validated['subject_id'],
                'created_by' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
            ]);

            foreach ($validated['question_ids'] as $index => $questionId) {
                $exam->examQuestions()->create([
                    'question_id' => $questionId,
                    'sort_order' => $index,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exam created.')]);

        return to_route('admin.exams.index');
    }
}
