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

        if (! $student->isStudent()) {
            return $promoCode;
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

        if (
            $promoCode->year !== (int) now()->year
            || $promoCode->month !== (int) now()->month
        ) {
            throw ValidationException::withMessages([
                'promo_code' => sprintf(
                    'Промокод действителен для %s. Сейчас %s.',
                    $this->formatPeriod($promoCode->year, $promoCode->month),
                    $this->formatPeriod((int) now()->year, (int) now()->month),
                ),
            ]);
        }

        return $promoCode;
    }

    private function formatPeriod(int $year, int $month): string
    {
        return mb_convert_case(
            now()->setYear($year)->setMonth($month)->startOfMonth()->translatedFormat('F Y'),
            MB_CASE_TITLE,
            'UTF-8',
        );
    }
}
