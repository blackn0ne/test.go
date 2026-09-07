<?php

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use App\Services\Questions\QuestionAnswerKeyBuilder;
use App\Services\QuestionScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function createQuestionWithOptions(QuestionType $type): Question
{
    $subject = Subject::factory()->create();
    $question = Question::factory()->create([
        'subject_id' => $subject->id,
        'type' => $type,
        'body' => '<p>Test question</p>',
    ]);

    if ($type === QuestionType::Double) {
        $question->update([
            'double_first_prompt' => '<p>First select prompt</p>',
            'double_second_prompt' => '<p>Second select prompt</p>',
        ]);

        foreach (['A', 'B', 'C', 'D'] as $index => $label) {
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'select_group' => 'first',
                'label' => $label,
                'content' => "<p>Option {$label}</p>",
                'is_correct' => $label === 'A',
                'sort_order' => $index,
            ]);
        }

        foreach (['A', 'B', 'C', 'D'] as $index => $label) {
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'select_group' => 'second',
                'label' => $label,
                'content' => "<p>Option {$label}</p>",
                'is_correct' => $label === 'B',
                'sort_order' => $index + 4,
            ]);
        }

        return app(QuestionAnswerKeyBuilder::class)->refresh($question->fresh(['options']));
    }

    foreach (match ($type) {
        QuestionType::Multiple => ['A', 'B', 'C', 'D', 'E', 'F'],
        default => ['A', 'B', 'C', 'D'],
    } as $index => $label) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'label' => $label,
            'content' => "<p>Option {$label}</p>",
            'is_correct' => match ($type) {
                QuestionType::Single => $label === 'B',
                QuestionType::Multiple => in_array($label, ['A', 'C', 'E'], true),
            },
            'sort_order' => $index,
        ]);
    }

    return app(QuestionAnswerKeyBuilder::class)->refresh($question->fresh(['options']));
}

test('admin can create a single choice question', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            'subject_id' => $subject->id,
            'type' => QuestionType::Single->value,
            'body' => '<p>What is 2+2?</p>',
            'options' => [
                ['label' => 'A', 'content' => '<p>3</p>', 'is_correct' => false, 'select_group' => null, 'sort_order' => 0],
                ['label' => 'B', 'content' => '<p>4</p>', 'is_correct' => true, 'select_group' => null, 'sort_order' => 1],
                ['label' => 'C', 'content' => '<p>5</p>', 'is_correct' => false, 'select_group' => null, 'sort_order' => 2],
                ['label' => 'D', 'content' => '<p>6</p>', 'is_correct' => false, 'select_group' => null, 'sort_order' => 3],
            ],
        ])
        ->assertRedirect(route('admin.questions.index'));

    $question = Question::query()->first();

    expect($question)->not->toBeNull()
        ->and($question->type)->toBe(QuestionType::Single)
        ->and($question->options)->toHaveCount(4)
        ->and($question->answer_key)->toBeArray()
        ->and($question->answer_key['correct_ids'] ?? [])->toHaveCount(1);
});

test('admin can create a multiple choice question with six options', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $options = collect(['A', 'B', 'C', 'D', 'E', 'F'])->map(
        fn (string $label, int $index) => [
            'label' => $label,
            'content' => "<p>Option {$label}</p>",
            'is_correct' => in_array($label, ['A', 'C', 'F'], true),
            'select_group' => null,
            'sort_order' => $index,
        ],
    )->all();

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            'subject_id' => $subject->id,
            'type' => QuestionType::Multiple->value,
            'body' => '<p>Select all correct answers</p>',
            'options' => $options,
        ])
        ->assertRedirect(route('admin.questions.index'));

    $question = Question::query()->first();

    expect($question)->not->toBeNull()
        ->and($question->type)->toBe(QuestionType::Multiple)
        ->and($question->options)->toHaveCount(6)
        ->and($question->options->pluck('label')->all())->toBe(['A', 'B', 'C', 'D', 'E', 'F']);
});

test('question scorer calculates single multiple and double scores', function () {
    $scorer = new QuestionScorer;

    $single = createQuestionWithOptions(QuestionType::Single);
    $singleCorrectId = $single->options->firstWhere('label', 'B')?->id;

    expect($scorer->score($single, [$singleCorrectId]))->toBe(1.0)
        ->and($scorer->score($single, [$single->options->firstWhere('label', 'A')?->id]))->toBe(0.0);

    $multiple = createQuestionWithOptions(QuestionType::Multiple);
    $multipleCorrectIds = $multiple->options->where('is_correct', true)->pluck('id')->all();
    $partialCorrectIds = array_slice($multipleCorrectIds, 0, 2);

    expect($scorer->score($multiple, $multipleCorrectIds))->toBe(2.0)
        ->and($scorer->score($multiple, $partialCorrectIds))->toBe(1.0);

    $double = createQuestionWithOptions(QuestionType::Double);
    $firstCorrectId = $double->options
        ->where('select_group', 'first')
        ->firstWhere('is_correct', true)
        ?->id;
    $secondCorrectId = $double->options
        ->where('select_group', 'second')
        ->firstWhere('is_correct', true)
        ?->id;

    expect($scorer->score($double, [$firstCorrectId, $secondCorrectId]))->toBe(2.0)
        ->and($scorer->score($double, [$firstCorrectId]))->toBe(1.0)
        ->and($scorer->score($double, [$secondCorrectId]))->toBe(1.0);
});

test('admin edit form loads existing options and correct flags', function () {
    $admin = User::factory()->admin()->create();
    $question = createQuestionWithOptions(QuestionType::Single);

    $this->actingAs($admin)
        ->get(route('admin.questions.index', [
            'sheet' => 'edit',
            'question' => $question->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/questions/Index')
            ->where('sheet.mode', 'edit')
            ->has('sheet.question.options', 4)
            ->where('sheet.question.options.0.is_correct', false)
            ->where('sheet.question.options.1.is_correct', true)
        );
});

test('admin create route opens sheet on index', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.questions.create'))
        ->assertRedirect(route('admin.questions.index', ['sheet' => 'create']));
});

test('admin cannot change question type on update', function () {
    $admin = User::factory()->admin()->create();
    $question = createQuestionWithOptions(QuestionType::Single);

    $payload = [
        'subject_id' => $question->subject_id,
        'type' => QuestionType::Multiple->value,
        'body' => $question->body,
        'context_mode' => 'none',
        'options' => $question->options->map(fn ($option) => [
            'label' => $option->label,
            'content' => $option->content,
            'is_correct' => $option->is_correct,
            'select_group' => $option->select_group,
            'sort_order' => $option->sort_order,
        ])->all(),
    ];

    $this->actingAs($admin)
        ->put(route('admin.questions.update', $question), $payload)
        ->assertSessionHasErrors('type');

    expect($question->fresh()->type)->toBe(QuestionType::Single);
});

test('store validates correct answer is selected', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            'subject_id' => $subject->id,
            'type' => QuestionType::Single->value,
            'body' => '<p>Test</p>',
            'context_mode' => 'none',
            'options' => collect(['A', 'B', 'C', 'D'])->map(
                fn (string $label, int $index) => [
                    'label' => $label,
                    'content' => "<p>{$label}</p>",
                    'is_correct' => false,
                    'select_group' => null,
                    'sort_order' => $index,
                ],
            )->all(),
        ])
        ->assertSessionHasErrors('options');
});

test('admin can create question with shared context', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.questions.store'), [
            'subject_id' => $subject->id,
            'type' => QuestionType::Single->value,
            'body' => '<p>Question 1?</p>',
            'context_mode' => 'new',
            'context_title' => 'Reading A',
            'context_body' => '<p>Shared passage text.</p>',
            'options' => [
                ['label' => 'A', 'content' => '<p>1</p>', 'is_correct' => false, 'select_group' => null, 'sort_order' => 0],
                ['label' => 'B', 'content' => '<p>2</p>', 'is_correct' => true, 'select_group' => null, 'sort_order' => 1],
                ['label' => 'C', 'content' => '<p>3</p>', 'is_correct' => false, 'select_group' => null, 'sort_order' => 2],
                ['label' => 'D', 'content' => '<p>4</p>', 'is_correct' => false, 'select_group' => null, 'sort_order' => 3],
            ],
        ])
        ->assertRedirect(route('admin.questions.index'));

    $question = Question::query()->with('context')->first();

    expect($question)->not->toBeNull()
        ->and($question->context)->not->toBeNull()
        ->and($question->context->title)->toBe('Reading A');
});

test('admin can upload editor image', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.editor-uploads.store'), [
            'image' => UploadedFile::fake()->image('formula.png'),
        ])
        ->assertOk()
        ->assertJsonStructure(['url']);
});
