<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Http\Resources\Admin\AdminQuestionResource;
use App\Models\Question;
use App\Models\QuestionContext;
use App\Models\Subject;
use App\Services\Questions\QuestionContextResolver;
use App\Services\Questions\QuestionOptionSyncService;
use App\Services\Questions\QuestionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function __construct(
        private readonly QuestionRepository $questions,
        private readonly QuestionOptionSyncService $optionSync,
        private readonly QuestionContextResolver $contextResolver,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $paginator = $this->questions->paginateForAdmin(
            subjectId: $request->integer('subject_id') ?: null,
            type: $request->string('type')->toString() ?: null,
            search: $request->string('search')->toString() ?: null,
        );

        return Inertia::render('admin/questions/Index', [
            'questions' => $paginator->through(fn (Question $question) => [
                'id' => $question->id,
                'type' => $question->type->value,
                'type_label' => $question->type->label(),
                'body' => $question->body,
                'subject' => $question->subject->only(['id', 'name']),
                'created_at' => $question->created_at,
            ]),
            'filters' => [
                'subject_id' => $request->input('subject_id'),
                'type' => $request->input('type'),
                'search' => $request->input('search'),
            ],
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'types' => $this->questionTypes(),
            'contexts' => $this->contextOptions(),
            'sheet' => $this->resolveSheet($request),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return to_route('admin.questions.index', ['sheet' => 'create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $contextId = $this->contextResolver->resolveForSubject(
                $request->validated(),
                $request->integer('subject_id'),
            );

            $question = Question::query()->create([
                ...$request->safe()->only([
                    'subject_id',
                    'type',
                    'body',
                ]),
                'question_context_id' => $contextId,
            ]);

            $this->optionSync->sync($question, $request->validated('options'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Question created.')]);

        return to_route('admin.questions.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question): RedirectResponse
    {
        return to_route('admin.questions.index', [
            'sheet' => 'edit',
            'question' => $question->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        DB::transaction(function () use ($request, $question): void {
            $contextId = $this->contextResolver->resolveForSubject(
                $request->validated(),
                $request->integer('subject_id'),
            );

            $question->update([
                ...$request->safe()->only([
                    'subject_id',
                    'body',
                ]),
                'question_context_id' => $contextId,
            ]);

            $this->optionSync->sync($question, $request->validated('options'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Question updated.')]);

        return to_route('admin.questions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Question deleted.')]);

        return to_route('admin.questions.index');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function questionTypes(): array
    {
        return collect(QuestionType::cases())->map(fn (QuestionType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveSheet(Request $request): ?array
    {
        $mode = $request->string('sheet')->toString();

        if ($mode === 'create') {
            return [
                'mode' => 'create',
            ];
        }

        if (! in_array($mode, ['edit', 'show'], true) || ! $request->filled('question')) {
            return null;
        }

        $question = Question::query()
            ->with(['options', 'subject', 'context'])
            ->findOrFail($request->integer('question'));

        return [
            'mode' => $mode,
            'question' => AdminQuestionResource::make($question)->resolve(),
        ];
    }

    /**
     * @return list<array{id: int, subject_id: int, title: string|null, label: string}>
     */
    private function contextOptions(): array
    {
        return QuestionContext::query()
            ->orderByDesc('id')
            ->get(['id', 'subject_id', 'title', 'body'])
            ->map(fn (QuestionContext $context) => [
                'id' => $context->id,
                'subject_id' => $context->subject_id,
                'title' => $context->title,
                'label' => $context->title
                    ?: str($context->body)->stripTags()->limit(80)->value(),
            ])
            ->values()
            ->all();
    }
}
