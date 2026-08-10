<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_attempt_id
 * @property int $exam_attempt_question_id
 * @property array<int, int> $selected_option_ids
 * @property float|null $score_awarded
 * @property Carbon|null $answered_at
 * @property-read ExamAttempt $attempt
 * @property-read ExamAttemptQuestion $attemptQuestion
 */
class ExamAttemptAnswer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'exam_attempt_question_id',
        'selected_option_ids',
        'score_awarded',
        'answered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'score_awarded' => 'decimal:1',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ExamAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    /**
     * @return BelongsTo<ExamAttemptQuestion, $this>
     */
    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptQuestion::class, 'exam_attempt_question_id');
    }
}
