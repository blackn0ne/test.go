<?php

namespace App\Http\Requests\Admin;

use App\Models\Question;
use Illuminate\Validation\Validator;

class UpdateQuestionRequest extends QuestionRequest
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Question $question */
            $question = $this->route('question');

            if ($this->input('type') !== $question->type->value) {
                $validator->errors()->add('type', 'Тип вопроса нельзя изменить после создания.');
            }
        });

        parent::withValidator($validator);
    }
}
