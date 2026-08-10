<?php

namespace App\Http\Requests\Admin;

use App\Models\Question;
use Illuminate\Validation\Validator;

class UpdateQuestionRequest extends QuestionRequest
{
    protected function prepareForValidation(): void
    {
        /** @var Question $question */
        $question = $this->route('question');

        $this->merge([
            'type' => $question->type->value,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Question $question */
            $question = $this->route('question');

            if ($this->input('type') !== $question->type->value) {
                $validator->errors()->add('type', 'Тип вопроса нельзя изменить после создания.');
            }
        });
    }
}
