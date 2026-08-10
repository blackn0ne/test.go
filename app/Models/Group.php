<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $subject_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Subject $subject
 */
class Group extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'subject_id',
    ];

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
