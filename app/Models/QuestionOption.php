<?php

namespace App\Models;

use Database\Factories\QuestionOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $question_id
 * @property string|null $select_group
 * @property string $label
 * @property string $content
 * @property bool $is_correct
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Question $question
 */
class QuestionOption extends Model
{
    /** @use HasFactory<QuestionOptionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_id',
        'select_group',
        'label',
        'content',
        'is_correct',
        'sort_order',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'is_correct',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
