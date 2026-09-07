<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamAttemptQuestionOption;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use App\Services\Exams\DoubleSelectSnapshotSync;
use App\Services\Exams\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createMigratedDoubleQuestion(): Question
{
    $subject = Subject::factory()->create();
    $question = Question::factory()->create([
        'subject_id' => $subject->id,
        'type' => QuestionType::Double,
        'body' => '<p>Double question</p>',
        'double_first_prompt' => '<p>Header A</p>',
        'double_second_prompt' => '<p>Header B</p>',
    ]);

    foreach ([
        ['first', 'A', '<p>Option A</p>', true, 0],
        ['first', 'B', '<p>Option B</p>', false, 1],
        ['first', 'C', '<p>Option C</p>', false, 2],
        ['first', 'D', '<p>Option D</p>', false, 3],
        ['second', 'A', '<p>Option A</p>', false, 4],
        ['second', 'B', '<p>Option B</p>', false, 5],
        ['second', 'C', '<p>Option C</p>', true, 6],
        ['second', 'D', '<p>Option D</p>', false, 7],
    ] as [$group, $label, $content, $isCorrect, $sortOrder]) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'select_group' => $group,
            'label' => $label,
            'content' => $content,
            'is_correct' => $isCorrect,
            'sort_order' => $sortOrder,
        ]);
    }

    return $question->fresh(['options']);
}

test('double select snapshot sync updates legacy attempt question prompts and options', function () {
    $question = createMigratedDoubleQuestion();
    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $user->id,
        'status' => ExamAttemptStatus::InProgress,
    ]);

    $snapshot = ExamAttemptQuestion::query()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'subject_id' => $question->subject_id,
        'subject_name' => 'Test',
        'section_order' => 1,
        'type' => QuestionType::Double,
        'body' => $question->body,
        'sort_order' => 1,
    ]);

    ExamAttemptQuestionOption::query()->create([
        'exam_attempt_question_id' => $snapshot->id,
        'question_option_id' => $question->options->firstWhere('select_group', 'first')?->id,
        'select_group' => 'first',
        'label' => 'A',
        'content' => '<p>Legacy header</p>',
        'sort_order' => 0,
    ]);

    app(DoubleSelectSnapshotSync::class)->syncIfNeeded($snapshot->fresh(['options']));

    $snapshot->refresh()->load('options');

    expect($snapshot->double_first_prompt)->toBe('<p>Header A</p>')
        ->and($snapshot->double_second_prompt)->toBe('<p>Header B</p>')
        ->and($snapshot->options)->toHaveCount(8)
        ->and($snapshot->options->firstWhere('select_group', 'first')?->content)->toBe('<p>Option A</p>');
});

test('find attempt syncs legacy double snapshots before returning', function () {
    $question = createMigratedDoubleQuestion();
    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $user->id,
        'status' => ExamAttemptStatus::InProgress,
    ]);

    $snapshot = ExamAttemptQuestion::query()->create([
        'exam_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'subject_id' => $question->subject_id,
        'subject_name' => 'Test',
        'section_order' => 1,
        'type' => QuestionType::Double,
        'body' => $question->body,
        'sort_order' => 1,
    ]);

    ExamAttemptQuestionOption::query()->create([
        'exam_attempt_question_id' => $snapshot->id,
        'question_option_id' => $question->options->firstWhere('select_group', 'first')?->id,
        'select_group' => 'first',
        'label' => 'A',
        'content' => '<p>Legacy header</p>',
        'sort_order' => 0,
    ]);

    $loaded = app(ExamAttemptService::class)->findAttempt($exam, $user);

    $loadedSnapshot = $loaded?->snapshotQuestions->first();

    expect($loadedSnapshot?->double_first_prompt)->toBe('<p>Header A</p>')
        ->and($loadedSnapshot?->options)->toHaveCount(8);
});
