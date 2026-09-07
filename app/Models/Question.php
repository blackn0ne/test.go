<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $subject_id
 * @property QuestionType $type
 * @property string $body
 * @property string|null $double_first_prompt
 * @property string|null $double_second_prompt
 * @property array<string, mixed>|null $answer_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $question_context_id
 * @property-read Subject $subject
 * @property-read QuestionContext|null $context
 * @property-read Collection<int, QuestionOption> $options
 */
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subject_id',
        'question_context_id',
        'type',
        'body',
        'double_first_prompt',
        'double_second_prompt',
        'answer_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'answer_key' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<QuestionContext, $this>
     */
    public function context(): BelongsTo
    {
        return $this->belongsTo(QuestionContext::class, 'question_context_id');
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<Exam, $this, ExamQuestion>
     */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->using(ExamQuestion::class)
            ->withPivot(['sort_order', 'points_override']);
    }
}
