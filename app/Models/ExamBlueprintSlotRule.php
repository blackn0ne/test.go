<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_blueprint_section_id
 * @property int $slot_from
 * @property int $slot_to
 * @property QuestionType $question_type
 * @property bool $requires_context
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ExamBlueprintSlotRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_blueprint_section_id',
        'slot_from',
        'slot_to',
        'question_type',
        'requires_context',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_type' => QuestionType::class,
            'requires_context' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ExamBlueprintSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprintSection::class, 'exam_blueprint_section_id');
    }

    public function slotCount(): int
    {
        return $this->slot_to - $this->slot_from + 1;
    }
}
