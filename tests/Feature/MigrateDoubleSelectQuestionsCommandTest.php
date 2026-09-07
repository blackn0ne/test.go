<?php

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createLegacyDoubleQuestion(): Question
{
    $subject = Subject::factory()->create();
    $question = Question::factory()->create([
        'subject_id' => $subject->id,
        'type' => QuestionType::Double,
        'body' => '<p>Legacy double question</p>',
    ]);

    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'select_group' => 'first',
        'label' => 'A',
        'content' => '<p>баррель</p>',
        'match_label' => 'A',
        'is_correct' => false,
        'sort_order' => 0,
    ]);

    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'select_group' => 'first',
        'label' => 'B',
        'content' => '<p>м³</p>',
        'match_label' => 'C',
        'is_correct' => false,
        'sort_order' => 1,
    ]);

    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'select_group' => 'first',
        'label' => 'C',
        'content' => '<p>.</p>',
        'match_label' => null,
        'is_correct' => false,
        'sort_order' => 2,
    ]);

    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'select_group' => 'first',
        'label' => 'D',
        'content' => '<p>.</p>',
        'match_label' => null,
        'is_correct' => false,
        'sort_order' => 3,
    ]);

    foreach ([
        ['A', '<p>мұнай</p>', true],
        ['B', '<p>ағаш сүрегі</p>', false],
        ['C', '<p>темір</p>', false],
        ['D', '<p>гауһар тас</p>', false],
    ] as $index => [$label, $content, $isCorrect]) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'select_group' => 'second',
            'label' => $label,
            'content' => $content,
            'is_correct' => $isCorrect,
            'sort_order' => $index + 4,
        ]);
    }

    return $question->fresh(['options']);
}

test('migrate double selects command converts legacy layout to prompts and shared option pool', function () {
    $question = createLegacyDoubleQuestion();

    $this->artisan('questions:migrate-double-selects')
        ->assertSuccessful();

    $question->refresh()->load('options');

    expect($question->double_first_prompt)->toBe('<p>баррель</p>')
        ->and($question->double_second_prompt)->toBe('<p>м³</p>');

    $firstOptions = $question->options->where('select_group', 'first')->values();
    $secondOptions = $question->options->where('select_group', 'second')->values();

    expect($firstOptions)->toHaveCount(4)
        ->and($secondOptions)->toHaveCount(4)
        ->and($firstOptions->pluck('content')->all())->toBe($secondOptions->pluck('content')->all())
        ->and($firstOptions->firstWhere('label', 'A')?->is_correct)->toBeTrue()
        ->and($secondOptions->firstWhere('label', 'C')?->is_correct)->toBeTrue()
        ->and($question->answer_key)->toBe([
            'groups' => [
                'first' => $firstOptions->firstWhere('label', 'A')?->id,
                'second' => $secondOptions->firstWhere('label', 'C')?->id,
            ],
        ]);
});

test('migrate double selects command skips already migrated questions', function () {
    $question = createLegacyDoubleQuestion();

    $this->artisan('questions:migrate-double-selects')->assertSuccessful();
    $this->artisan('questions:migrate-double-selects')
        ->assertSuccessful()
        ->expectsOutputToContain('skipped');

    expect($question->fresh(['options'])->options->where('select_group', 'first'))->toHaveCount(4);
});

test('migrate double selects dry run does not persist changes', function () {
    $question = createLegacyDoubleQuestion();

    $this->artisan('questions:migrate-double-selects --dry-run')
        ->assertSuccessful();

    $question->refresh();

    expect($question->double_first_prompt)->toBeNull()
        ->and($question->options->where('select_group', 'first'))->toHaveCount(4);
});
