<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $exam_attempt_id
 * @property int $question_id
 * @property QuestionType $type
 * @property string $body
 * @property int $sort_order
 * @property-read ExamAttempt $attempt
 * @property-read Question $sourceQuestion
 * @property-read Collection<int, ExamAttemptQuestionOption> $options
 */
class ExamAttemptQuestion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'question_context_id',
        'subject_id',
        'subject_name',
        'section_order',
        'type',
        'body',
        'context_title',
        'context_body',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
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
     * @return BelongsTo<Question, $this>
     */
    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    /**
     * @return HasMany<ExamAttemptQuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestionOption::class)->orderBy('sort_order');
    }
}
