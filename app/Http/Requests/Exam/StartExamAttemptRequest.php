<?php

namespace App\Http\Requests\Exam;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartExamAttemptRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('promo_code')) {
            $this->merge([
                'promo_code' => strtoupper(trim($this->string('promo_code')->toString())),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'promo_code' => [
                Rule::requiredIf(fn () => $this->user()?->isStudent() ?? false),
                'nullable',
                'string',
                'size:5',
                'regex:/^[0-9A-Z]{5}$/',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'promo_code.required' => 'Введите промокод.',
            'promo_code.size' => 'Промокод должен содержать 5 символов.',
            'promo_code.regex' => 'Промокод может содержать только цифры и заглавные латинские буквы.',
        ];
    }
}
