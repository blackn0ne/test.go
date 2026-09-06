<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\UserRole;
use Illuminate\Validation\Rule;

trait ValidatesStudentSchool
{
    protected function prepareStudentSchool(): void
    {
        if ($this->input('role') !== UserRole::User->value) {
            $this->merge(['school_id' => null]);
        } elseif (blank($this->input('school_id'))) {
            $this->merge(['school_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function studentSchoolRules(): array
    {
        return [
            'school_id' => [
                Rule::requiredIf(fn () => $this->input('role') === UserRole::User->value),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::School->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function studentSchoolMessages(): array
    {
        return [
            'school_id.required' => 'Выберите школу для студента.',
            'school_id.exists' => 'Выберите существующую школу.',
        ];
    }
}
