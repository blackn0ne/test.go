<?php

namespace App\Http\Requests\Admin;

use App\Enums\QuestionType;
use App\Support\HtmlContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class QuestionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'body' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! HtmlContent::hasMeaningfulContent($value)) {
                        $fail('Введите условие вопроса.');
                    }
                },
            ],
            'double_first_prompt' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === QuestionType::Double->value),
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('type') !== QuestionType::Double->value) {
                        return;
                    }

                    if (! is_string($value) || ! HtmlContent::hasMeaningfulContent($value)) {
                        $fail('Заполните заголовок первого селекта.');
                    }
                },
            ],
            'double_second_prompt' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === QuestionType::Double->value),
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('type') !== QuestionType::Double->value) {
                        return;
                    }

                    if (! is_string($value) || ! HtmlContent::hasMeaningfulContent($value)) {
                        $fail('Заполните заголовок второго селекта.');
                    }
                },
            ],
            'context_mode' => ['nullable', 'string', Rule::in(['none', 'existing', 'new'])],
            'context_id' => ['nullable', 'integer', Rule::exists('question_contexts', 'id')],
            'context_title' => ['nullable', 'string', 'max:255'],
            'context_body' => ['nullable', 'string'],
            'options' => ['required', 'array'],
            'options.*.label' => ['required', 'string', Rule::in(['A', 'B', 'C', 'D', 'E', 'F'])],
            'options.*.content' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! HtmlContent::hasMeaningfulContent($value)) {
                        $fail('Заполните текст варианта ответа.');
                    }
                },
            ],
            'options.*.is_correct' => ['required', 'boolean'],
            'options.*.select_group' => ['nullable', 'string', Rule::in(['first', 'second'])],
            'options.*.match_label' => ['nullable', 'string', Rule::in(['A', 'B', 'C', 'D'])],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = QuestionType::from($this->input('type'));
            /** @var array<int, array<string, mixed>> $options */
            $options = $this->input('options', []);

            $this->validateOptionsForType($validator, $type, $options);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    protected function validateOptionsForType(Validator $validator, QuestionType $type, array $options): void
    {
        match ($type) {
            QuestionType::Single => $this->validateSingleOptions($validator, $options),
            QuestionType::Multiple => $this->validateMultipleOptions($validator, $options),
            QuestionType::Double => $this->validateDoubleOptions($validator, $options),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function validateSingleOptions(Validator $validator, array $options): void
    {
        if (count($options) !== 4) {
            $validator->errors()->add('options', 'Для одиночного ответа нужно ровно 4 варианта.');

            return;
        }

        if (collect($options)->where('is_correct', true)->count() !== 1) {
            $validator->errors()->add('options', 'Для одиночного ответа выберите один правильный вариант.');
        }

        if (collect($options)->pluck('select_group')->filter()->isNotEmpty()) {
            $validator->errors()->add('options', 'Для одиночного ответа группы селектов не используются.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function validateMultipleOptions(Validator $validator, array $options): void
    {
        if (count($options) !== 6) {
            $validator->errors()->add('options', 'Для множественного ответа нужно ровно 6 вариантов (A–F).');

            return;
        }

        $correctCount = collect($options)->where('is_correct', true)->count();

        if ($correctCount < 1 || $correctCount > 6) {
            $validator->errors()->add('options', 'Для множественного ответа выберите от 1 до 6 правильных вариантов.');
        }

        if (collect($options)->pluck('select_group')->filter()->isNotEmpty()) {
            $validator->errors()->add('options', 'Для множественного ответа группы селектов не используются.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function validateDoubleOptions(Validator $validator, array $options): void
    {
        if (count($options) !== 8) {
            $validator->errors()->add('options', 'Для двойного селекта нужно 8 вариантов (4 + 4).');

            return;
        }

        foreach (['first', 'second'] as $group) {
            $groupOptions = collect($options)->where('select_group', $group);

            if ($groupOptions->count() !== 4) {
                $validator->errors()->add('options', "В группе {$group} должно быть 4 варианта.");

                return;
            }
        }

        $firstOptions = collect($options)->where('select_group', 'first');
        $secondOptions = collect($options)->where('select_group', 'second');

        if ($firstOptions->where('is_correct', true)->count() !== 1) {
            $validator->errors()->add('options', 'В первом селекте выберите один правильный вариант.');
        }

        if ($secondOptions->where('is_correct', true)->count() !== 1) {
            $validator->errors()->add('options', 'Во втором селекте выберите один правильный вариант.');
        }
    }
}
