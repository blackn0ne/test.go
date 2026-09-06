<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamGenerationMode;
use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamRequest;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamBlueprint;
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
                ->with(['direction:id,code,name', 'blueprint:id,name', 'subject:id,name', 'creator:id,name'])
                ->withCount('attempts')
                ->latest('id')
                ->paginate(25)
                ->through(fn (Exam $exam) => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'status' => $exam->status->value,
                    'status_label' => $exam->status->label(),
                    'generation_mode' => $exam->generation_mode->value,
                    'generation_mode_label' => $exam->generation_mode->label(),
                    'direction' => $exam->direction?->only(['id', 'code', 'name']),
                    'blueprint' => $exam->blueprint?->only(['id', 'name']),
                    'subject' => $exam->subject?->only(['id', 'name']),
                    'attempts_count' => $exam->attempts_count,
                    'starts_at' => $exam->starts_at,
                    'ends_at' => $exam->ends_at,
                    'creator' => $exam->creator->only(['id', 'name']),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/exams/Create', [
            'directions' => Direction::query()
                ->with(['subjects:id,name'])
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'blueprints' => ExamBlueprint::query()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'total_questions', 'is_default']),
            'statuses' => collect(ExamStatus::cases())->map(fn (ExamStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'generationModes' => collect(ExamGenerationMode::cases())->map(fn (ExamGenerationMode $mode) => [
                'value' => $mode->value,
                'label' => $mode->label(),
            ])->values(),
        ]);
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $isGenerated = $validated['generation_mode'] === ExamGenerationMode::Generated->value;

        DB::transaction(function () use ($request, $validated, $isGenerated): void {
            $exam = Exam::query()->create([
                'subject_id' => $isGenerated ? null : $validated['subject_id'],
                'generation_mode' => $validated['generation_mode'],
                'exam_blueprint_id' => $isGenerated ? $validated['exam_blueprint_id'] : null,
                'direction_id' => $isGenerated ? $validated['direction_id'] : null,
                'created_by' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'duration_minutes' => $validated['duration_minutes'] ?? 240,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
            ]);

            if (! $isGenerated) {
                foreach ($validated['question_ids'] as $index => $questionId) {
                    $exam->examQuestions()->create([
                        'question_id' => $questionId,
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exam created.')]);

        return to_route('admin.exams.index');
    }
}
