<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExamStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('question_ids') && is_string($this->question_ids)) {
            $this->merge([
                'question_ids' => collect(explode(',', $this->question_ids))
                    ->map(fn (string $id) => (int) trim($id))
                    ->filter(fn (int $id) => $id > 0)
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ExamStatus::class)],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', Rule::exists('questions', 'id')],
        ];
    }
}
