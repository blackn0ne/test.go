<?php

namespace App\Http\Resources\Admin;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Question */
class AdminQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'question_context_id' => $this->question_context_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'body' => $this->body,
            'double_first_prompt' => $this->double_first_prompt,
            'double_second_prompt' => $this->double_second_prompt,
            'subject' => $this->when(
                $this->relationLoaded('subject'),
                fn () => $this->subject->only(['id', 'name']),
            ),
            'context' => $this->when(
                $this->relationLoaded('context') && $this->context !== null,
                fn () => [
                    'id' => $this->context->id,
                    'title' => $this->context->title,
                    'body' => $this->context->body,
                ],
            ),
            'options' => $this->whenLoaded(
                'options',
                fn () => AdminQuestionOptionResource::collection($this->options)->resolve(),
            ),
        ];
    }
}
