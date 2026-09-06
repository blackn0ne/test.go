<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromoCodeBatchRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentYear = (int) now()->format('Y');

        return [
            'year' => ['required', 'integer', 'min:2020', 'max:'.($currentYear + 5)],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'school_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::School->value),
            ],
            'coupons_per_student' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => 'Выберите год.',
            'month.required' => 'Выберите месяц.',
            'school_id.required' => 'Выберите школу.',
            'coupons_per_student.required' => 'Укажите количество промокодов на одного студента.',
            'coupons_per_student.min' => 'Минимум 1 промокод на студента.',
        ];
    }
}
