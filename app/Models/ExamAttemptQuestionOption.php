<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $exam_attempt_question_id
 * @property int $question_option_id
 * @property string|null $select_group
 * @property string $label
 * @property string $content
 * @property int $sort_order
 * @property-read ExamAttemptQuestion $attemptQuestion
 * @property-read QuestionOption $sourceOption
 */
class ExamAttemptQuestionOption extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_question_id',
        'question_option_id',
        'select_group',
        'label',
        'content',
        'sort_order',
    ];

    /**
     * @return BelongsTo<ExamAttemptQuestion, $this>
     */
    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptQuestion::class, 'exam_attempt_question_id');
    }

    /**
     * @return BelongsTo<QuestionOption, $this>
     */
    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
