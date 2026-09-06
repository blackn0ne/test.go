<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use App\Services\Exams\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createPublishedExamWithQuestion(): Exam
{
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $question = Question::factory()->create([
        'subject_id' => $subject->id,
        'type' => QuestionType::Single,
        'body' => '<p>2 + 2 = ?</p>',
    ]);

    foreach (['A', 'B', 'C', 'D'] as $index => $label) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'label' => $label,
            'content' => "<p>{$label}</p>",
            'is_correct' => $label === 'B',
            'sort_order' => $index,
        ]);
    }

    $exam = Exam::factory()->create([
        'subject_id' => $subject->id,
        'created_by' => $admin->id,
        'status' => ExamStatus::Published,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    ExamQuestion::query()->create([
        'exam_id' => $exam->id,
        'question_id' => $question->id,
        'sort_order' => 0,
    ]);

    return $exam->fresh(['examQuestions']);
}

function createStudentWithDirection(): User
{
    $direction = Direction::query()->create([
        'code' => 'FIZ-MAT',
        'name' => 'Физика + Математика',
    ]);

    return User::factory()->withDirection($direction)->create();
}

test('exam taking response never exposes is_correct', function () {
    $exam = createPublishedExamWithQuestion();
    $student = createStudentWithDirection();

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('questions', 1)
            ->has('questions.0.options', 4)
            ->missing('questions.0.options.0.is_correct')
        );
});

test('student can submit exam and receive server-side score', function () {
    $exam = createPublishedExamWithQuestion();
    $student = User::factory()->create();
    $service = app(ExamAttemptService::class);

    $attempt = $service->startOrResume($exam, $student);
    $snapshotQuestion = $attempt->snapshotQuestions->first();
    $correctSnapshotOption = $snapshotQuestion->options->firstWhere('label', 'B');

    $graded = $service->submit($attempt, [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => [$correctSnapshotOption->id],
        ],
    ]);

    expect($graded->status)->toBe(ExamAttemptStatus::Submitted)
        ->and((float) $graded->total_score)->toBe(1.0)
        ->and($graded->answers)->toHaveCount(1);
});

test('student cannot submit twice', function () {
    $exam = createPublishedExamWithQuestion();
    $student = User::factory()->create();
    $service = app(ExamAttemptService::class);

    $attempt = $service->startOrResume($exam, $student);
    $snapshotQuestion = $attempt->snapshotQuestions->first();
    $option = $snapshotQuestion->options->first();

    $service->submit($attempt, [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => [$option->id],
        ],
    ]);

    expect(fn () => $service->submit($attempt->fresh(), [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => [$option->id],
        ],
    ]))->toThrow(ValidationException::class);
});

test('admin question edit exposes is_correct only in admin context', function () {
    $admin = User::factory()->admin()->create();
    createPublishedExamWithQuestion();
    $question = Question::query()->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.questions.edit', $question))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('question.options.0.is_correct')
        );
});
