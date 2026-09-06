<?php

use App\Enums\ExamGenerationMode;
use App\Enums\ExamStatus;
use App\Enums\SubjectKind;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamBlueprint;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Support\CoreSubjects;
use Database\Seeders\EntSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedExistingCoreSubjects(): void
{
    foreach (CoreSubjects::CODES as $name) {
        Subject::factory()->create(['name' => $name]);
    }
}

test('ent seeder links existing core subjects and creates blueprint', function () {
    seedExistingCoreSubjects();

    $this->seed(EntSystemSeeder::class);

    expect(Subject::query()->where('kind', SubjectKind::Core)->count())->toBe(3)
        ->and(Subject::query()->where('is_system', true)->count())->toBe(3)
        ->and(ExamBlueprint::query()->where('is_default', true)->exists())->toBeTrue();

    $blueprint = ExamBlueprint::query()->where('code', 'ent_standard')->first();

    expect($blueprint->total_questions)->toBe(120)
        ->and($blueprint->sections)->toHaveCount(5);
});

test('ent seeder removes empty duplicate subjects created earlier', function () {
    Subject::factory()->create(['name' => 'Оқу сауаттылығы']);
    Subject::factory()->create([
        'name' => 'Оқу сауаттылығы',
        'code' => 'reading_literacy',
        'kind' => SubjectKind::Core,
        'is_system' => true,
    ]);

    $this->seed(EntSystemSeeder::class);

    expect(Subject::query()->where('name', 'Оқу сауаттылығы')->count())->toBe(1);
});

test('ent sync keeps original subject with questions and removes seeder duplicate', function () {
    $original = Subject::factory()->create(['name' => 'Оқу сауаттылығы']);
    Question::factory()->count(5)->create(['subject_id' => $original->id]);

    $duplicate = Subject::factory()->create([
        'name' => 'Оқу сауаттылығы',
        'code' => 'reading_literacy',
        'kind' => SubjectKind::Core,
        'is_system' => true,
    ]);

    $resolved = CoreSubjects::resolve('reading_literacy');

    expect(Subject::query()->where('name', 'Оқу сауаттылығы')->count())->toBe(1)
        ->and($resolved->id)->toBe($original->id)
        ->and($resolved->code)->toBe('reading_literacy')
        ->and($resolved->is_system)->toBeTrue()
        ->and($resolved->questions()->count())->toBe(5)
        ->and(Subject::query()->find($duplicate->id))->toBeNull();
});

test('ent sync removes empty seeder duplicate when original has no code', function () {
    $original = Subject::factory()->create(['name' => 'Оқу сауаттылығы']);

    Subject::factory()->create([
        'name' => 'Оқу сауаттылығы',
        'code' => 'reading_literacy',
        'kind' => SubjectKind::Core,
        'is_system' => true,
    ]);

    $resolved = CoreSubjects::resolve('reading_literacy');

    expect($resolved->id)->toBe($original->id)
        ->and(Subject::query()->where('name', 'Оқу сауаттылығы')->count())->toBe(1);
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
    seedExistingCoreSubjects();
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

test('admin can mark subject as core via checkbox', function () {
    $admin = User::factory()->admin()->create();
    $schoolClass = SchoolClass::factory()->create();

    $subject = Subject::factory()->create(['name' => 'Оқу сауаттылығы']);

    $this->actingAs($admin)
        ->put(route('admin.directories.subjects.update', $subject), [
            'name' => 'Оқу сауаттылығы',
            'code' => 'reading_literacy',
            'school_class_ids' => [$schoolClass->id],
            'is_core' => true,
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'subjects']));

    $subject->refresh();

    expect($subject->is_system)->toBeTrue()
        ->and($subject->kind)->toBe(SubjectKind::Core)
        ->and($subject->code)->toBe('reading_literacy');
});

test('admin can fill missing code on existing core subject', function () {
    $admin = User::factory()->admin()->create();
    $schoolClass = SchoolClass::factory()->create();

    $subject = Subject::factory()->create([
        'name' => 'Математикалық сауаттылық',
        'kind' => SubjectKind::Core,
        'is_system' => true,
        'code' => null,
    ]);
    $subject->schoolClasses()->attach($schoolClass);

    $this->actingAs($admin)
        ->put(route('admin.directories.subjects.update', $subject), [
            'name' => 'Математикалық сауаттылық',
            'code' => 'math_literacy',
            'school_class_ids' => [$schoolClass->id],
            'is_core' => true,
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'subjects']));

    expect($subject->fresh()->code)->toBe('math_literacy');
});

test('system subjects cannot be deleted', function () {
    seedExistingCoreSubjects();
    $this->seed(EntSystemSeeder::class);

    $admin = User::factory()->admin()->create();
    $subject = Subject::query()->where('code', 'reading_literacy')->first();

    $this->actingAs($admin)
        ->delete(route('admin.directories.subjects.destroy', $subject))
        ->assertRedirect(route('admin.directories.index', ['tab' => 'subjects']));

    expect(Subject::query()->find($subject->id))->not->toBeNull();
});
