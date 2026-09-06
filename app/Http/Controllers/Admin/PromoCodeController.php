<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromoCodeBatchRequest;
use App\Models\PromoCodeBatch;
use App\Models\User;
use App\Services\PromoCodes\PromoCodeBatchService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/promo-codes/Index', [
            'batches' => PromoCodeBatch::query()
                ->with([
                    'school:id,name',
                    'creator:id,name',
                    'promoCodes:id,promo_code_batch_id,code,redeemed_at',
                ])
                ->withCount([
                    'promoCodes',
                    'promoCodes as redeemed_count' => fn ($query) => $query->whereNotNull('redeemed_at'),
                ])
                ->latest()
                ->get()
                ->map(fn (PromoCodeBatch $batch) => [
                    'id' => $batch->id,
                    'year' => $batch->year,
                    'month' => $batch->month,
                    'coupons_per_student' => $batch->coupons_per_student,
                    'students_count' => $batch->students_count,
                    'total_codes' => $batch->total_codes,
                    'redeemed_count' => $batch->redeemed_count,
                    'school' => $batch->school->only(['id', 'name']),
                    'creator' => $batch->creator->only(['id', 'name']),
                    'created_at' => $batch->created_at,
                    'codes' => $batch->promoCodes
                        ->sortBy('code')
                        ->values()
                        ->map(fn ($code) => [
                            'id' => $code->id,
                            'code' => $code->code,
                            'is_redeemed' => $code->redeemed_at !== null,
                        ]),
                ]),
        ]);
    }

    public function create(): Response
    {
        $currentYear = (int) now()->format('Y');

        return Inertia::render('admin/promo-codes/Create', [
            'schools' => User::query()
                ->where('role', UserRole::School)
                ->withCount(['students' => fn ($query) => $query->where('role', UserRole::User)])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $school) => [
                    'id' => $school->id,
                    'name' => $school->name,
                    'students_count' => $school->students_count,
                ]),
            'years' => range($currentYear, $currentYear + 2),
            'months' => collect(range(1, 12))->map(fn (int $month) => [
                'value' => $month,
                'label' => mb_convert_case(
                    now()->setMonth($month)->translatedFormat('F'),
                    MB_CASE_TITLE,
                    'UTF-8',
                ),
            ])->values(),
        ]);
    }

    public function store(
        StorePromoCodeBatchRequest $request,
        PromoCodeBatchService $promoCodeBatchService,
    ): RedirectResponse {
        /** @var User $school */
        $school = User::query()->findOrFail($request->integer('school_id'));

        $batch = $promoCodeBatchService->generate(
            $school,
            $request->integer('year'),
            $request->integer('month'),
            $request->integer('coupons_per_student'),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Сгенерировано {$batch->total_codes} промокодов.",
        ]);

        return to_route('admin.promo-codes.index');
    }
}
