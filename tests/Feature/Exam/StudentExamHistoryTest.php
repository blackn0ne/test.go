<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('student can view submitted exam history', function () {
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $direction = Direction::query()->create([
        'code' => 'DG',
        'name' => 'ДЖТ-ГЕО',
    ]);
    $school = User::factory()->school()->create();
    $student = User::factory()->withDirection($direction)->create([
        'school_id' => $school->id,
    ]);

    $question = Question::factory()->create([
        'subject_id' => $subject->id,
        'type' => QuestionType::Single,
    ]);

    QuestionOption::factory()->create([
        'question_id' => $question->id,
        'label' => 'A',
        'is_correct' => true,
        'sort_order' => 0,
    ]);

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

    $attempt = ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'status' => ExamAttemptStatus::Submitted,
        'started_at' => now()->subHour(),
        'submitted_at' => now()->subMinutes(30),
        'total_score' => 29,
        'max_score' => 140,
    ]);

    $this->actingAs($student)
        ->get(route('exam.history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('exam/History')
            ->has('attempts.data', 1)
            ->where('attempts.data.0.id', $attempt->id)
            ->where('attempts.data.0.exam_title', $exam->title)
            ->where('attempts.data.0.total_score', 29)
            ->where('attempts.data.0.max_score', 140)
            ->where('attempts.data.0.can_view_result', true)
        );
});

test('admin cannot access student exam history', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('exam.history'))
        ->assertForbidden();
});

test('in-progress attempts are not listed in history', function () {
    $direction = Direction::query()->create([
        'code' => 'DG',
        'name' => 'ДЖТ-ГЕО',
    ]);
    $student = User::factory()->withDirection($direction)->create();
    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $exam = Exam::factory()->create([
        'subject_id' => $subject->id,
        'created_by' => $admin->id,
    ]);

    ExamAttempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'status' => ExamAttemptStatus::InProgress,
    ]);

    $this->actingAs($student)
        ->get(route('exam.history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('attempts.data', 0)
        );
});
