<?php

use App\Enums\ExamGenerationMode;
use App\Enums\ExamStatus;
use App\Enums\SubjectKind;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\EntSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ent seeder creates core subjects and default blueprint', function () {
    $this->seed(EntSystemSeeder::class);

    expect(Subject::query()->where('kind', SubjectKind::Core)->count())->toBe(3)
        ->and(ExamBlueprint::query()->where('is_default', true)->exists())->toBeTrue();

    $blueprint = ExamBlueprint::query()->where('code', 'ent_standard')->first();

    expect($blueprint->total_questions)->toBe(120)
        ->and($blueprint->sections)->toHaveCount(5);
});

test('admin can create direction combination', function () {
    $admin = User::factory()->admin()->create();
    $physics = Subject::factory()->create(['name' => 'Физика', 'kind' => SubjectKind::Profile]);
    $math = Subject::factory()->create(['name' => 'Математика', 'kind' => SubjectKind::Profile]);

    $this->actingAs($admin)
        ->post(route('admin.directories.directions.store'), [
            'code' => 'FIZ-MAT',
            'name' => 'Физика + Математика',
            'first_subject_id' => $physics->id,
            'second_subject_id' => $math->id,
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'directions']));

    $direction = Direction::query()->where('code', 'FIZ-MAT')->first();

    expect($direction)->not->toBeNull()
        ->and($direction->subjects)->toHaveCount(2);
});

test('admin can create generated ent exam', function () {
    $this->seed(EntSystemSeeder::class);
    $admin = User::factory()->admin()->create();

    $physics = Subject::factory()->create(['name' => 'Физика', 'kind' => SubjectKind::Profile]);
    $math = Subject::factory()->create(['name' => 'Математика', 'kind' => SubjectKind::Profile]);

    $direction = Direction::query()->create(['code' => 'FIZ-MAT', 'name' => 'Физика + Математика']);
    $direction->subjects()->sync([
        $physics->id => ['position' => 1],
        $math->id => ['position' => 2],
    ]);

    $blueprint = ExamBlueprint::query()->where('is_default', true)->first();

    $this->actingAs($admin)
        ->post(route('admin.exams.store'), [
            'title' => 'ЕНТ пробный',
            'generation_mode' => ExamGenerationMode::Generated->value,
            'direction_id' => $direction->id,
            'exam_blueprint_id' => $blueprint->id,
            'status' => ExamStatus::Draft->value,
            'duration_minutes' => 240,
        ])
        ->assertRedirect(route('admin.exams.index'));

    $exam = Exam::query()->where('title', 'ЕНТ пробный')->first();

    expect($exam)->not->toBeNull()
        ->and($exam->generation_mode)->toBe(ExamGenerationMode::Generated)
        ->and($exam->direction_id)->toBe($direction->id)
        ->and($exam->examQuestions)->toHaveCount(0);
});

test('system subjects cannot be deleted', function () {
    $this->seed(EntSystemSeeder::class);
    $admin = User::factory()->admin()->create();

    $subject = Subject::query()->where('code', 'reading_literacy')->first();

    $this->actingAs($admin)
        ->delete(route('admin.directories.subjects.destroy', $subject))
        ->assertRedirect(route('admin.directories.index', ['tab' => 'subjects']));

    expect(Subject::query()->find($subject->id))->not->toBeNull();
});
