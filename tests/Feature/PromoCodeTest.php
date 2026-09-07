<?php

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\PromoCode;
use App\Models\PromoCodeBatch;
use App\Models\Subject;
use App\Models\User;
use App\Services\PromoCodes\PromoCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can generate promo codes for school students', function () {
    $admin = User::factory()->admin()->create();
    $school = User::factory()->school()->create(['name' => 'СШ №1']);

    User::factory()->count(10)->create([
        'school_id' => $school->id,
        'role' => UserRole::User,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.promo-codes.store'), [
            'year' => 2026,
            'month' => 3,
            'school_id' => $school->id,
            'coupons_per_student' => 2,
        ])
        ->assertRedirect(route('admin.promo-codes.index'));

    expect(PromoCodeBatch::query()->count())->toBe(1)
        ->and(PromoCode::query()->count())->toBe(20);

    PromoCode::query()->each(function (PromoCode $promoCode): void {
        expect($promoCode->code)->toMatch('/^[0-9A-Z]{5}$/');
    });
});

test('promo code generator creates unique five character codes', function () {
    $generator = app(PromoCodeGenerator::class);

    $codes = $generator->generateUniqueCodes(50);

    expect($codes)->toHaveCount(50)
        ->and($codes)->each->toMatch('/^[0-9A-Z]{5}$/')
        ->and(collect($codes)->unique()->count())->toBe(50);
});

test('student needs valid promo code to start exam', function () {
    $school = User::factory()->school()->create();
    $direction = Direction::query()->create([
        'code' => 'FIZ-MAT',
        'name' => 'Физика + Математика',
    ]);
    $student = User::factory()->withDirection($direction)->create([
        'school_id' => $school->id,
    ]);

    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $exam = Exam::factory()->create([
        'subject_id' => $subject->id,
        'created_by' => $admin->id,
        'status' => ExamStatus::Published,
        'starts_at' => now()->setYear(2026)->setMonth(3)->startOfMonth(),
        'ends_at' => now()->setYear(2026)->setMonth(3)->endOfMonth(),
    ]);

    $batch = PromoCodeBatch::query()->create([
        'school_id' => $school->id,
        'year' => 2026,
        'month' => 3,
        'coupons_per_student' => 1,
        'students_count' => 1,
        'total_codes' => 1,
        'created_by' => $admin->id,
    ]);

    $promoCode = PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $school->id,
        'year' => 2026,
        'month' => 3,
        'code' => 'A1B2C',
    ]);

    $this->actingAs($student)
        ->get(route('exams.take', $exam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('requiresPromoCode', true)
            ->where('attempt', null)
        );

    $this->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => 'WRONG'])
        ->assertSessionHasErrors('promo_code');

    $this->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => 'A1B2C'])
        ->assertRedirect(route('exams.take', $exam));

    expect($promoCode->fresh()->redeemed_at)->not->toBeNull()
        ->and($promoCode->fresh()->user_id)->toBe($student->id);

    $this->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => 'A1B2C'])
        ->assertRedirect(route('exams.take', $exam));
});

test('student can start exam with promo when exam has no start date', function () {
    $school = User::factory()->school()->create();
    $direction = Direction::query()->create([
        'code' => 'FIZ-MAT',
        'name' => 'Физика + Математика',
    ]);
    $student = User::factory()->withDirection($direction)->create([
        'school_id' => $school->id,
    ]);

    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $now = now();

    $exam = Exam::factory()->create([
        'subject_id' => $subject->id,
        'created_by' => $admin->id,
        'status' => ExamStatus::Published,
        'starts_at' => null,
        'ends_at' => null,
    ]);

    $batch = PromoCodeBatch::query()->create([
        'school_id' => $school->id,
        'year' => (int) $now->year,
        'month' => (int) $now->month,
        'coupons_per_student' => 1,
        'students_count' => 1,
        'total_codes' => 1,
        'created_by' => $admin->id,
    ]);

    $promoCode = PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $school->id,
        'year' => (int) $now->year,
        'month' => (int) $now->month,
        'code' => '42155',
    ]);

    $this->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => $promoCode->code])
        ->assertRedirect(route('exams.take', $exam));

    expect($promoCode->fresh()->redeemed_at)->not->toBeNull();
});

test('student cannot start exam with already used promo code', function () {
    $school = User::factory()->school()->create();
    $direction = Direction::query()->create([
        'code' => 'FIZ-MAT',
        'name' => 'Физика + Математика',
    ]);
    $student = User::factory()->withDirection($direction)->create([
        'school_id' => $school->id,
    ]);
    $otherStudent = User::factory()->withDirection($direction)->create([
        'school_id' => $school->id,
    ]);

    $admin = User::factory()->admin()->create();
    $subject = Subject::factory()->create();
    $exam = Exam::factory()->create([
        'subject_id' => $subject->id,
        'created_by' => $admin->id,
        'status' => ExamStatus::Published,
        'starts_at' => now()->setYear(2026)->setMonth(3)->startOfMonth(),
        'ends_at' => now()->setYear(2026)->setMonth(3)->endOfMonth(),
    ]);

    $batch = PromoCodeBatch::query()->create([
        'school_id' => $school->id,
        'year' => 2026,
        'month' => 3,
        'coupons_per_student' => 1,
        'students_count' => 2,
        'total_codes' => 1,
        'created_by' => $admin->id,
    ]);

    $promoCode = PromoCode::query()->create([
        'promo_code_batch_id' => $batch->id,
        'school_id' => $school->id,
        'year' => 2026,
        'month' => 3,
        'code' => 'A1B2C',
        'user_id' => $otherStudent->id,
        'redeemed_at' => now(),
    ]);

    $this->actingAs($student)
        ->post(route('exams.start', $exam), ['promo_code' => $promoCode->code])
        ->assertSessionHasErrors([
            'promo_code' => 'Этот промокод уже был использован. Запросите новый у школы.',
        ]);
});

test('cannot generate promo codes when school has no students', function () {
    $admin = User::factory()->admin()->create();
    $school = User::factory()->school()->create();

    $this->actingAs($admin)
        ->post(route('admin.promo-codes.store'), [
            'year' => 2026,
            'month' => 3,
            'school_id' => $school->id,
            'coupons_per_student' => 2,
        ])
        ->assertSessionHasErrors('school_id');
});
