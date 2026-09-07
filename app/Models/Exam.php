<?php

namespace App\Models;

use App\Enums\ExamGenerationMode;
use App\Enums\ExamStatus;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $subject_id
 * @property ExamGenerationMode $generation_mode
 * @property int|null $exam_blueprint_id
 * @property int|null $direction_id
 * @property int|null $group_id
 * @property int $created_by
 * @property string $title
 * @property string|null $description
 * @property ExamStatus $status
 * @property int|null $duration_minutes
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $show_results_after_submit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Subject|null $subject
 * @property-read ExamBlueprint|null $blueprint
 * @property-read Direction|null $direction
 * @property-read Group|null $group
 * @property-read User $creator
 * @property-read Collection<int, ExamQuestion> $examQuestions
 * @property-read Collection<int, Question> $questions
 * @property-read Collection<int, ExamAttempt> $attempts
 */
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subject_id',
        'generation_mode',
        'exam_blueprint_id',
        'direction_id',
        'group_id',
        'created_by',
        'title',
        'description',
        'status',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'show_results_after_submit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation_mode' => ExamGenerationMode::class,
            'status' => ExamStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'show_results_after_submit' => 'boolean',
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
     * @return BelongsTo<ExamBlueprint, $this>
     */
    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'exam_blueprint_id');
    }

    /**
     * @return BelongsTo<Direction, $this>
     */
    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ExamQuestion, $this>
     */
    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<Question, $this, ExamQuestion>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->using(ExamQuestion::class)
            ->withPivot(['sort_order', 'points_override'])
            ->orderByPivot('sort_order');
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isGenerated(): bool
    {
        return $this->generation_mode === ExamGenerationMode::Generated;
    }

    public function isAvailableNow(): bool
    {
        if ($this->status !== ExamStatus::Published) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function periodDate(): Carbon
    {
        return $this->starts_at ?? now();
    }

    public function periodYear(): int
    {
        return (int) $this->periodDate()->year;
    }

    public function periodMonth(): int
    {
        return (int) $this->periodDate()->month;
    }
}
