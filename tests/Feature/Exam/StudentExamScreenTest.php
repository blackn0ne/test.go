<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\PromoCode;
use App\Models\PromoCodeBatch;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use App\Support\CoreSubjects;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStudentExamFixtures(): array
{
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create(['name' => 'Физика']);
    $school = User::factory()->school()->create();
    $direction = Direction::query()->create([
        'code' => 'DG',
        'name' => 'ДЖТ-ГЕО',
    ]);
    $direction->subjects()->attach($subject->id, ['position' => 1]);

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
        'direction_id' => $direction->id,
        'created_by' => $admin->id,
        'status' => ExamStatus::Published,
        'title' => 'ЕНТ 2026',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    ExamQuestion::query()->create([
        'exam_id' => $exam->id,
        'question_id' => $question->id,
        'sort_order' => 0,
    ]);

    $student = User::factory()->withDirection($direction)->create([
        'school_id' => $school->id,
    ]);

    return compact('admin', 'exam', 'student', 'direction');
}

test('student login redirects to exam screen', function () {
    ['student' => $student] = createStudentExamFixtures();

    $this->post(route('login'), [
        'iin' => $student->iin,
        'password' => 'password',
    ])->assertRedirect(route('exam.show'));
});

test('student exam screen shows lobby with sections and exam info', function () {
    ['exam' => $exam, 'student' => $student, 'direction' => $direction] = createStudentExamFixtures();

    foreach (CoreSubjects::CODES as $name) {
        Subject::factory()->create(['name' => $name]);
    }

    $this->actingAs($student)
        ->get(route('exam.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('exam/Show')
            ->where('exam.id', $exam->id)
            ->where('exam.title', 'ЕНТ 2026')
            ->where('lobby.available', true)
            ->where('lobby.title', 'ЕНТ 2026')
            ->has('sections', 4)
            ->where('requiresPromoCode', true)
        );
});

test('student with in progress attempt is redirected from lobby to take page', function () {
    ['exam' => $exam, 'student' => $student] = createStudentExamFixtures();

    ExamAttempt::query()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'status' => ExamAttemptStatus::InProgress,
        'started_at' => now(),
        'max_score' => 1,
    ]);

    $this->actingAs($student)
        ->get(route('exam.show'))
        ->assertRedirect(route('exams.take', $exam));
});

test('student exam lobby shows waiting state when no published exam', function () {
    ['student' => $student] = createStudentExamFixtures();

    Exam::query()->delete();

    $this->actingAs($student)
        ->get(route('exam.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('exam/Show')
            ->where('exam', null)
            ->where('lobby.available', false)
            ->where('lobby.title', 'ЕНТ')
            ->has('lobby.period_label')
        );
});

test('take page without attempt redirects to exam lobby', function () {
    ['exam' => $exam, 'student' => $student] = createStudentExamFixtures();

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertRedirect(route('exam.show'));
});

test('take page exposes section metadata on questions', function () {
    ['exam' => $exam, 'student' => $student, 'admin' => $admin] = createStudentExamFixtures();

    $batch = PromoCodeBatch::query()->create([
        'school_id' => $student->school_id,
        'year' => (int) $exam->starts_at?->year,
        'month' => (int) $exam->starts_at?->month,
        'coupons_per_student' => 1,
        'students_count' => 1,
        'total_codes' => 1,
        'created_by' => $admin->id,
    ]);

    PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $student->school_id,
        'year' => (int) $exam->starts_at?->year,
        'month' => (int) $exam->starts_at?->month,
        'code' => 'A1B2C',
    ]);

    $this->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => 'A1B2C'])
        ->assertRedirect(route('exams.take', $exam));

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('exams/Take')
            ->has('sections')
            ->has('questions.0.subject_name')
            ->has('questions.0.section_order')
        );
});
