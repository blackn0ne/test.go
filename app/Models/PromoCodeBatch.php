<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $school_id
 * @property int $year
 * @property int $month
 * @property int $coupons_per_student
 * @property int $students_count
 * @property int $total_codes
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $school
 * @property-read User $creator
 * @property-read Collection<int, PromoCode> $promoCodes
 */
class PromoCodeBatch extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'year',
        'month',
        'coupons_per_student',
        'students_count',
        'total_codes',
        'created_by',
    ];

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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PromoCode, $this>
     */
    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }

    public function redeemedCount(): int
    {
        return $this->promoCodes()->whereNotNull('redeemed_at')->count();
    }
}
