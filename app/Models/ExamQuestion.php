<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $exam_id
 * @property int $question_id
 * @property int $sort_order
 * @property float|null $points_override
 * @property-read Exam $exam
 * @property-read Question $question
 */
class ExamQuestion extends Pivot
{
    protected $table = 'exam_questions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'question_id',
        'sort_order',
        'points_override',
    ];

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_override' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
