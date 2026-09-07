<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamGenerationMode;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Enums\SubjectKind;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\PromoCode;
use App\Models\PromoCodeBatch;
use App\Models\Question;
use App\Models\QuestionContext;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamPaperGenerator;
use App\Support\CoreSubjects;
use Database\Seeders\EntSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createQuestionWithOptions(Subject $subject, QuestionType $type, ?QuestionContext $context = null): Question
{
    $question = Question::factory()->create([
        'subject_id' => $subject->id,
        'type' => $type,
        'question_context_id' => $context?->id,
    ]);

    if ($type === QuestionType::Single) {
        foreach (['A', 'B', 'C', 'D'] as $index => $label) {
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'label' => $label,
                'is_correct' => $label === 'A',
                'sort_order' => $index,
            ]);
        }
    }

    if ($type === QuestionType::Multiple) {
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $index => $label) {
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'label' => $label,
                'is_correct' => in_array($label, ['A', 'B'], true),
                'sort_order' => $index,
            ]);
        }
    }

    if ($type === QuestionType::Double) {
        foreach (['first', 'second'] as $group) {
            foreach (['A', 'B', 'C', 'D'] as $index => $label) {
                QuestionOption::factory()->create([
                    'question_id' => $question->id,
                    'select_group' => $group,
                    'label' => $label,
                    'is_correct' => $label === 'A',
                    'sort_order' => $index,
                ]);
            }
        }
    }

    return $question;
}

function seedEntQuestionBank(Direction $direction): void
{
    $reading = Subject::query()->where('code', 'reading_literacy')->firstOrFail();
    $mathLiteracy = Subject::query()->where('code', 'math_literacy')->firstOrFail();
    $history = Subject::query()->where('code', 'kazakhstan_history')->firstOrFail();
    $profileOne = $direction->subjectAtPosition(1);
    $profileTwo = $direction->subjectAtPosition(2);

    for ($i = 0; $i < 15; $i++) {
        createQuestionWithOptions($reading, QuestionType::Single);
        createQuestionWithOptions($mathLiteracy, QuestionType::Single);
    }

    for ($i = 0; $i < 25; $i++) {
        createQuestionWithOptions($history, QuestionType::Single);
    }

    foreach ([$profileOne, $profileTwo] as $profileSubject) {
        for ($i = 0; $i < 30; $i++) {
            createQuestionWithOptions($profileSubject, QuestionType::Single);
        }

        $context = QuestionContext::query()->create([
            'subject_id' => $profileSubject->id,
            'title' => 'Контекст',
            'body' => '<p>Текст</p>',
        ]);

        for ($i = 0; $i < 6; $i++) {
            createQuestionWithOptions($profileSubject, QuestionType::Single, $context);
        }

        for ($i = 0; $i < 6; $i++) {
            createQuestionWithOptions($profileSubject, QuestionType::Double);
        }

        for ($i = 0; $i < 6; $i++) {
            createQuestionWithOptions($profileSubject, QuestionType::Multiple);
        }
    }
}

function createGeneratedEntExam(Direction $direction): Exam
{
    $admin = User::factory()->admin()->create();
    $blueprint = ExamBlueprint::query()->where('is_default', true)->firstOrFail();

    return Exam::query()->create([
        'generation_mode' => ExamGenerationMode::Generated,
        'exam_blueprint_id' => $blueprint->id,
        'direction_id' => $direction->id,
        'created_by' => $admin->id,
        'title' => 'ЕНТ тест',
        'status' => ExamStatus::Published,
        'duration_minutes' => 240,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);
}

test('generator builds 120 question paper with profile slot types', function () {
    foreach (CoreSubjects::CODES as $name) {
        Subject::factory()->create(['name' => $name]);
    }
    $this->seed(EntSystemSeeder::class);

    $physics = Subject::factory()->create(['name' => 'Физика', 'kind' => SubjectKind::Profile]);
    $math = Subject::factory()->create(['name' => 'Математика', 'kind' => SubjectKind::Profile]);

    $direction = Direction::query()->create(['code' => 'FIZ-MAT', 'name' => 'Физ-MAT']);
    $direction->subjects()->sync([
        $physics->id => ['position' => 1],
        $math->id => ['position' => 2],
    ]);
    $direction->load('subjects');

    seedEntQuestionBank($direction);

    $exam = createGeneratedEntExam($direction);
    $student = User::factory()->create();

    $paper = app(ExamPaperGenerator::class)->generate($exam, $student);

    expect($paper)->toHaveCount(120);

    $profileOneQuestions = collect($paper)
        ->filter(fn ($item) => $item->subjectId === $physics->id)
        ->values();

    expect($profileOneQuestions)->toHaveCount(40)
        ->and($profileOneQuestions->take(25)->every(fn ($item) => $item->question->type === QuestionType::Single))->toBeTrue()
        ->and($profileOneQuestions->slice(25, 5)->every(fn ($item) => $item->question->question_context_id !== null))->toBeTrue()
        ->and($profileOneQuestions->slice(30, 5)->every(fn ($item) => $item->question->type === QuestionType::Double))->toBeTrue()
        ->and($profileOneQuestions->slice(35, 5)->every(fn ($item) => $item->question->type === QuestionType::Multiple))->toBeTrue();
});

test('second exam excludes questions from first submitted attempt', function () {
    foreach (CoreSubjects::CODES as $name) {
        Subject::factory()->create(['name' => $name]);
    }
    $this->seed(EntSystemSeeder::class);

    $physics = Subject::factory()->create(['name' => 'Физика', 'kind' => SubjectKind::Profile]);
    $math = Subject::factory()->create(['name' => 'Математика', 'kind' => SubjectKind::Profile]);

    $direction = Direction::query()->create(['code' => 'FIZ-MAT', 'name' => 'Физ-MAT']);
    $direction->subjects()->sync([
        $physics->id => ['position' => 1],
        $math->id => ['position' => 2],
    ]);

    seedEntQuestionBank($direction);

    $service = app(ExamAttemptService::class);

    $school = User::factory()->school()->create();
    $student = User::factory()->create(['school_id' => $school->id]);
    $admin = User::factory()->admin()->create();

    $examOne = createGeneratedEntExam($direction);
    $batch = PromoCodeBatch::query()->create([
        'school_id' => $school->id,
        'year' => (int) $examOne->starts_at?->year,
        'month' => (int) $examOne->starts_at?->month,
        'coupons_per_student' => 2,
        'students_count' => 1,
        'total_codes' => 2,
        'created_by' => $admin->id,
    ]);

    PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $school->id,
        'year' => (int) $examOne->starts_at?->year,
        'month' => (int) $examOne->starts_at?->month,
        'code' => 'AAA11',
    ]);

    PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $school->id,
        'year' => (int) $examOne->starts_at?->year,
        'month' => (int) $examOne->starts_at?->month,
        'code' => 'BBB22',
    ]);

    $attemptOne = $service->startOrResume($examOne, $student, 'AAA11');
    $firstQuestionIds = $attemptOne->snapshotQuestions->pluck('question_id')->all();

    $attemptOne->update([
        'status' => ExamAttemptStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $attemptTwo = $service->startOrResume($examOne, $student, 'BBB22');
    $secondQuestionIds = $attemptTwo->snapshotQuestions->pluck('question_id')->all();

    expect($attemptOne->id)->not->toBe($attemptTwo->id)
        ->and(array_intersect($firstQuestionIds, $secondQuestionIds))->toBeEmpty();
});
