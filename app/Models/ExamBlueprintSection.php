<?php

namespace App\Models;

use App\Enums\BlueprintSectionKind;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_blueprint_id
 * @property BlueprintSectionKind $section_kind
 * @property int|null $subject_id
 * @property int|null $profile_position
 * @property int $question_count
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamBlueprint $blueprint
 * @property-read Subject|null $subject
 * @property-read Collection<int, ExamBlueprintSlotRule> $slotRules
 */
class ExamBlueprintSection extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_blueprint_id',
        'section_kind',
        'subject_id',
        'profile_position',
        'question_count',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_kind' => BlueprintSectionKind::class,
        ];
    }

    /**
     * @return BelongsTo<ExamBlueprint, $this>
     */
    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'exam_blueprint_id');
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return HasMany<ExamBlueprintSlotRule, $this>
     */
    public function slotRules(): HasMany
    {
        return $this->hasMany(ExamBlueprintSlotRule::class)->orderBy('sort_order');
    }

    public function isProfile(): bool
    {
        return $this->section_kind === BlueprintSectionKind::Profile;
    }
}
