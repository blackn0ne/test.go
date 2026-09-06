<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use App\Support\CoreSubjects;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->code)) {
            $code = trim($this->code);

            $this->merge([
                'code' => $code === '' ? null : $code,
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Subject $subject */
        $subject = $this->route('subject');

        $wantsCore = $this->boolean('is_core');
        $knownCode = CoreSubjects::codeForName((string) $this->input('name'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                Rule::requiredIf($wantsCore && $knownCode === null),
                'nullable',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('subjects', 'code')->ignore($subject),
            ],
            'school_class_ids' => ['required', 'array', 'min:1'],
            'school_class_ids.*' => ['integer', Rule::exists('school_classes', 'id')],
            'is_core' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Укажите код для обязательного предмета.',
            'code.unique' => 'Этот код уже используется другим предметом.',
        ];
    }
}
