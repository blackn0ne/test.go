<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promo_code_batch_id
 * @property int $school_id
 * @property int $year
 * @property int $month
 * @property string $code
 * @property int|null $user_id
 * @property int|null $exam_attempt_id
 * @property Carbon|null $redeemed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PromoCodeBatch $batch
 * @property-read User $school
 * @property-read User|null $user
 * @property-read ExamAttempt|null $examAttempt
 */
class PromoCode extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'promo_code_batch_id',
        'school_id',
        'year',
        'month',
        'code',
        'user_id',
        'exam_attempt_id',
        'redeemed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PromoCodeBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PromoCodeBatch::class, 'promo_code_batch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(User::class, 'school_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function isRedeemed(): bool
    {
        return $this->redeemed_at !== null;
    }
}
