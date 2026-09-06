<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use App\Http\Requests\Admin\Concerns\ValidatesOptionalRegionDistrict;
use App\Http\Requests\Admin\Concerns\ValidatesStudentSchool;
use App\Models\User;
use App\Support\UserContact;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules;
    use ValidatesOptionalRegionDistrict;
    use ValidatesStudentSchool;

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $normalizedPhone = UserContact::normalizePhone($this->string('phone')->toString());

            $this->merge([
                'phone' => $normalizedPhone,
                'email' => UserContact::emailFromPhone($normalizedPhone),
            ]);
        }

        $this->prepareRegionDistrict();
        $this->prepareStudentSchool();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'iin' => ['required', 'digits:12', Rule::unique(User::class)],
            'phone' => ['required', 'regex:/^7\d{10}$/', Rule::unique(User::class, 'phone')],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
            ...$this->regionDistrictRules(),
            ...$this->studentSchoolRules(),
        ];
    }

    public function withValidator($validator): void
    {
        $this->validateRegionDistrict($validator);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите ФИО.',
            'iin.required' => 'Укажите ИИН.',
            'iin.digits' => 'ИИН должен содержать 12 цифр.',
            'iin.unique' => 'Пользователь с таким ИИН уже существует.',
            'phone.required' => 'Укажите номер телефона.',
            'phone.regex' => 'Введите корректный номер телефона.',
            'phone.unique' => 'Пользователь с таким телефоном уже существует.',
            ...$this->studentSchoolMessages(),
        ];
    }
}
