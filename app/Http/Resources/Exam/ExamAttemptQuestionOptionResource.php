<?php

namespace App\Http\Resources\Exam;

use App\Models\ExamAttemptQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExamAttemptQuestionOption */
class ExamAttemptQuestionOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_option_id' => $this->question_option_id,
            'label' => $this->label,
            'content' => $this->content,
            'select_group' => $this->select_group,
            'sort_order' => $this->sort_order,
        ];
    }
}
