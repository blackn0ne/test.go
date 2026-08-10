<?php

namespace App\Http\Resources\Admin;

use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QuestionOption */
class AdminQuestionOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->label,
            'content' => $this->content,
            'is_correct' => $this->is_correct,
            'select_group' => $this->select_group,
            'sort_order' => $this->sort_order,
        ];
    }
}
