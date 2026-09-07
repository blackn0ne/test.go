<?php

namespace App\Services\PromoCodes;

use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\PromoCode;
use App\Models\PromoCodeBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoCodeBatchService
{
    public function __construct(
        private readonly PromoCodeGenerator $generator,
    ) {}

    public function generate(
        User $school,
        int $year,
        int $month,
        int $couponsPerStudent,
        User $admin,
    ): PromoCodeBatch {
        if ($school->role !== UserRole::School) {
            throw ValidationException::withMessages([
                'school_id' => 'Выберите школу.',
            ]);
        }

        $studentsCount = User::query()
            ->where('school_id', $school->id)
            ->where('role', UserRole::User)
            ->count();

        $totalCodes = $studentsCount * $couponsPerStudent;

        if ($totalCodes === 0) {
            throw ValidationException::withMessages([
                'school_id' => 'У выбранной школы нет студентов.',
            ]);
        }

        return DB::transaction(function () use (
            $school,
            $year,
            $month,
            $couponsPerStudent,
            $studentsCount,
            $totalCodes,
            $admin,
        ): PromoCodeBatch {
            $batch = PromoCodeBatch::query()->create([
                'school_id' => $school->id,
                'year' => $year,
                'month' => $month,
                'coupons_per_student' => $couponsPerStudent,
                'students_count' => $studentsCount,
                'total_codes' => $totalCodes,
                'created_by' => $admin->id,
            ]);

            $codes = $this->generator->generateUniqueCodes($totalCodes);

            foreach ($codes as $code) {
                PromoCode::query()->create([
                    'promo_code_batch_id' => $batch->id,
                    'school_id' => $school->id,
                    'year' => $year,
                    'month' => $month,
                    'code' => $code,
                ]);
            }

            return $batch->load(['school:id,name', 'creator:id,name']);
        });
    }

    public function redeem(PromoCode $promoCode, User $student, Exam $exam, int $attemptId): void
    {
        if (! $student->isStudent()) {
            return;
        }

        if ($student->school_id === null) {
            throw ValidationException::withMessages([
                'promo_code' => 'Студент не привязан к школе.',
            ]);
        }

        if ($promoCode->school_id !== $student->school_id) {
            throw ValidationException::withMessages([
                'promo_code' => 'Промокод не относится к вашей школе.',
            ]);
        }

        if ($promoCode->isRedeemed()) {
            throw ValidationException::withMessages([
                'promo_code' => 'Этот промокод уже был использован. Запросите новый у школы.',
            ]);
        }

        $examYear = (int) $exam->starts_at?->year;
        $examMonth = (int) $exam->starts_at?->month;

        if ($promoCode->year !== $examYear || $promoCode->month !== $examMonth) {
            throw ValidationException::withMessages([
                'promo_code' => 'Промокод истёк или не подходит к этому экзамену.',
            ]);
        }

        $promoCode->update([
            'user_id' => $student->id,
            'exam_attempt_id' => $attemptId,
            'redeemed_at' => now(),
        ]);
    }

    public function findRedeemable(string $code, User $student, Exam $exam): PromoCode
    {
        $promoCode = PromoCode::query()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if ($promoCode === null) {
            throw ValidationException::withMessages([
                'promo_code' => 'Такой промокод не существует. Проверьте код и попробуйте снова.',
            ]);
        }

        return $promoCode;
    }
}
