<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\PromoCode;
use App\Models\PromoCodeBatch;
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
    $school = User::factory()->school()->create();
    $direction = Direction::query()->create([
        'code' => 'FIZ-MAT',
        'name' => 'Физика + Математика',
    ]);

    return User::factory()->withDirection($direction)->create([
        'school_id' => $school->id,
    ]);
}

function createPromoCodeForStudent(User $student, Exam $exam, User $admin, string $code = 'A1B2C'): PromoCode
{
    $batch = PromoCodeBatch::query()->create([
        'school_id' => $student->school_id,
        'year' => (int) $exam->starts_at?->year,
        'month' => (int) $exam->starts_at?->month,
        'coupons_per_student' => 1,
        'students_count' => 1,
        'total_codes' => 1,
        'created_by' => $admin->id,
    ]);

    return PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $student->school_id,
        'year' => (int) $exam->starts_at?->year,
        'month' => (int) $exam->starts_at?->month,
        'code' => $code,
    ]);
}

function startExamAsStudent(User $student, Exam $exam, PromoCode $promoCode): void
{
    test()->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => $promoCode->code])
        ->assertRedirect(route('exams.take', $exam));
}

test('exam taking response never exposes is_correct', function () {
    $exam = createPublishedExamWithQuestion();
    $student = createStudentWithDirection();
    $admin = User::factory()->admin()->create();
    $promoCode = createPromoCodeForStudent($student, $exam, $admin);

    startExamAsStudent($student, $exam, $promoCode);

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('questions', 1)
            ->has('questions.0.options', 4)
            ->where('questions.0.options.0.label', 'A')
            ->has('questions.0.options.0.content')
            ->missing('questions.0.options.0.is_correct')
        );
});

test('student can submit exam and receive server-side score', function () {
    $exam = createPublishedExamWithQuestion();
    $student = createStudentWithDirection();
    $admin = User::factory()->admin()->create();
    $promoCode = createPromoCodeForStudent($student, $exam, $admin);
    $service = app(ExamAttemptService::class);

    $attempt = $service->startOrResume($exam, $student, $promoCode->code);
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
    $student = createStudentWithDirection();
    $admin = User::factory()->admin()->create();
    $promoCode = createPromoCodeForStudent($student, $exam, $admin);
    $service = app(ExamAttemptService::class);

    $attempt = $service->startOrResume($exam, $student, $promoCode->code);
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

test('student can start a new attempt on the same exam with another promo code', function () {
    $exam = createPublishedExamWithQuestion();
    $student = createStudentWithDirection();
    $admin = User::factory()->admin()->create();
    $service = app(ExamAttemptService::class);

    $firstPromo = createPromoCodeForStudent($student, $exam, $admin, 'AAA11');

    $batch = PromoCodeBatch::query()->create([
        'school_id' => $student->school_id,
        'year' => (int) $exam->starts_at?->year,
        'month' => (int) $exam->starts_at?->month,
        'coupons_per_student' => 2,
        'students_count' => 1,
        'total_codes' => 2,
        'created_by' => $admin->id,
    ]);

    $secondPromo = PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $student->school_id,
        'year' => (int) $exam->starts_at?->year,
        'month' => (int) $exam->starts_at?->month,
        'code' => 'BBB22',
    ]);

    $firstAttempt = $service->startOrResume($exam, $student, $firstPromo->code);
    $snapshotQuestion = $firstAttempt->snapshotQuestions->first();
    $option = $snapshotQuestion->options->first();

    $service->submit($firstAttempt, [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => [$option->id],
        ],
    ]);

    $secondAttempt = $service->startOrResume($exam, $student, $secondPromo->code);

    expect($secondAttempt->id)->not->toBe($firstAttempt->id)
        ->and($secondAttempt->status)->toBe(ExamAttemptStatus::InProgress)
        ->and($secondPromo->fresh()->redeemed_at)->not->toBeNull();

    test()->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => $secondPromo->code])
        ->assertRedirect(route('exams.take', $exam));
});

test('multiple choice answers are limited to three selections on submit', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();

    $question = Question::factory()->create([
        'subject_id' => $subject->id,
        'type' => QuestionType::Multiple,
        'body' => '<p>Select up to three</p>',
    ]);

    foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $index => $label) {
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'label' => $label,
            'content' => "<p>{$label}</p>",
            'is_correct' => in_array($label, ['A', 'B', 'C'], true),
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

    $student = createStudentWithDirection();
    $promoCode = createPromoCodeForStudent($student, $exam, $admin);
    $service = app(ExamAttemptService::class);

    $attempt = $service->startOrResume($exam, $student, $promoCode->code);
    $snapshotQuestion = $attempt->snapshotQuestions->first();

    expect(fn () => $service->submit($attempt, [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => $snapshotQuestion->options->take(4)->pluck('id')->all(),
        ],
    ]))->toThrow(ValidationException::class);
});

test('student answers are saved and restored during in-progress attempt', function () {
    $exam = createPublishedExamWithQuestion();
    $student = createStudentWithDirection();
    $admin = User::factory()->admin()->create();
    $promoCode = createPromoCodeForStudent($student, $exam, $admin);
    $service = app(ExamAttemptService::class);

    $attempt = $service->startOrResume($exam, $student, $promoCode->code);
    $snapshotQuestion = $attempt->snapshotQuestions->first();
    $selectedOption = $snapshotQuestion->options->firstWhere('label', 'B');

    $service->saveAnswers($attempt, [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => [$selectedOption->id],
        ],
    ]);

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('exams/Take')
            ->where('savedAnswers.0.exam_attempt_question_id', $snapshotQuestion->id)
            ->where('savedAnswers.0.selected_option_ids', [$selectedOption->id])
        );
});

test('incremental answer saves preserve previously stored answers', function () {
    $exam = createPublishedExamWithQuestion();
    $student = createStudentWithDirection();
    $admin = User::factory()->admin()->create();
    $promoCode = createPromoCodeForStudent($student, $exam, $admin);
    $service = app(ExamAttemptService::class);

    $attempt = $service->startOrResume($exam, $student, $promoCode->code);
    $snapshotQuestion = $attempt->snapshotQuestions->first();
    $firstOption = $snapshotQuestion->options->firstWhere('label', 'A');
    $secondOption = $snapshotQuestion->options->firstWhere('label', 'B');

    $service->saveAnswers($attempt, [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => [$firstOption->id],
        ],
    ]);

    $service->saveAnswers($attempt, [
        [
            'exam_attempt_question_id' => $snapshotQuestion->id,
            'selected_option_ids' => [$firstOption->id, $secondOption->id],
        ],
    ]);

    $attempt->refresh()->load('answers');

    expect($attempt->answers)->toHaveCount(1)
        ->and($attempt->answers->first()->selected_option_ids)->toBe([
            $firstOption->id,
            $secondOption->id,
        ]);
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
