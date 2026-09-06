<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExamGenerationMode;
use App\Enums\ExamStatus;
use App\Models\ExamBlueprint;
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

        if (! $this->filled('generation_mode')) {
            $this->merge([
                'generation_mode' => ExamGenerationMode::Generated->value,
            ]);
        }

        if (! $this->filled('exam_blueprint_id')) {
            $defaultBlueprintId = ExamBlueprint::query()
                ->where('is_default', true)
                ->value('id');

            if ($defaultBlueprintId !== null) {
                $this->merge(['exam_blueprint_id' => $defaultBlueprintId]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isGenerated = $this->input('generation_mode') === ExamGenerationMode::Generated->value;

        return [
            'title' => ['required', 'string', 'max:255'],
            'generation_mode' => ['required', Rule::enum(ExamGenerationMode::class)],
            'direction_id' => [
                Rule::requiredIf($isGenerated),
                'nullable',
                'integer',
                Rule::exists('directions', 'id'),
            ],
            'exam_blueprint_id' => [
                Rule::requiredIf($isGenerated),
                'nullable',
                'integer',
                Rule::exists('exam_blueprints', 'id'),
            ],
            'subject_id' => [
                Rule::requiredIf(! $isGenerated),
                'nullable',
                'integer',
                Rule::exists('subjects', 'id'),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ExamStatus::class)],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'question_ids' => [
                Rule::requiredIf(! $isGenerated),
                'nullable',
                'array',
                'min:1',
            ],
            'question_ids.*' => ['integer', Rule::exists('questions', 'id')],
        ];
    }
}
