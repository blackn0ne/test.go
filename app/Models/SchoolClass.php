<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SchoolClass extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sort_order',
    ];

    /**
     * @return BelongsToMany<Subject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'school_class_id', 'subject_id');
    }
}
