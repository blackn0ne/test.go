<?php

namespace App\Models;

use App\Enums\ExamAttemptStatus;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_id
 * @property int $user_id
 * @property ExamAttemptStatus $status
 * @property Carbon $started_at
 * @property Carbon|null $submitted_at
 * @property float|null $total_score
 * @property float|null $max_score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read User $user
 * @property-read Collection<int, ExamAttemptQuestion> $snapshotQuestions
 * @property-read Collection<int, ExamAttemptAnswer> $answers
 */
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'user_id',
        'status',
        'started_at',
        'submitted_at',
        'total_score',
        'max_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExamAttemptStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'total_score' => 'decimal:2',
            'max_score' => 'decimal:2',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ExamAttemptQuestion, $this>
     */
    public function snapshotQuestions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ExamAttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === ExamAttemptStatus::InProgress;
    }
}
