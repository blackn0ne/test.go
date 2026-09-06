<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Direction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * @return BelongsToMany<Subject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'direction_subject')
            ->withPivot('position')
            ->orderBy('direction_subject.position');
    }

    /**
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function subjectAtPosition(int $position): ?Subject
    {
        return $this->subjects->firstWhere('pivot.position', $position);
    }
}
