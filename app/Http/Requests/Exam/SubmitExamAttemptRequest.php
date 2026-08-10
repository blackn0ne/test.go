<?php

namespace App\Http\Requests\Exam;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitExamAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.exam_attempt_question_id' => ['required', 'integer'],
            'answers.*.selected_option_ids' => ['present', 'array'],
            'answers.*.selected_option_ids.*' => ['integer'],
        ];
    }
}
