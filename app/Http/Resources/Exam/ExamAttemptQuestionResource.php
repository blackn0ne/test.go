<?php

namespace App\Http\Resources\Exam;

use App\Models\ExamAttemptQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExamAttemptQuestion */
class ExamAttemptQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'type' => $this->type->value,
            'body' => $this->body,
            'sort_order' => $this->sort_order,
            'subject_id' => $this->subject_id,
            'subject_name' => $this->subject_name,
            'section_order' => $this->section_order,
            'context_id' => $this->question_context_id,
            'context_title' => $this->context_title,
            'context_body' => $this->context_body,
            'options' => $this->when(
                $this->relationLoaded('options'),
                fn (): array => ExamAttemptQuestionOptionResource::collection($this->options)->resolve(),
                [],
            ),
        ];
    }
}
