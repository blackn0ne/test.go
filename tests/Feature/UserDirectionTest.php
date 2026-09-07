<?php

use App\Enums\ExamStatus;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('student without direction can access exam lobby but not exams', function () {
    $student = User::factory()->create(['direction_id' => null]);
    $exam = Exam::factory()->create();

    $this->actingAs($student)
        ->get(route('exam.show'))
        ->assertOk();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('exam.show'));

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertRedirect(route('exam.show'));
});

test('student can save direction', function () {
    $student = User::factory()->create(['direction_id' => null]);
    $direction = Direction::query()->create([
        'code' => 'FIZ-MAT',
        'name' => 'Физика + Математика',
    ]);

    $this->actingAs($student)
        ->put(route('direction.update'), [
            'direction_id' => $direction->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($student->fresh()->direction_id)->toBe($direction->id);
});

test('admin does not need direction', function () {
    $admin = User::factory()->admin()->create(['direction_id' => null]);
    $exam = Exam::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.exams.index'))
        ->assertOk();
});

test('student with direction can access exams', function () {
    $direction = Direction::query()->create([
        'code' => 'FIZ-MAT',
        'name' => 'Физика + Математика',
    ]);
    $student = User::factory()->withDirection($direction)->create();

    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $exam = Exam::factory()->create([
        'subject_id' => $subject->id,
        'created_by' => $admin->id,
        'status' => ExamStatus::Published,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertOk();
});

test('shared auth props include iin and direction requirement', function () {
    $student = User::factory()->create([
        'direction_id' => null,
        'iin' => '123456789012',
    ]);

    $this->actingAs($student)
        ->get(route('exam.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.iin', '123456789012')
            ->where('auth.user.must_select_direction', true)
            ->has('directions')
        );
});
