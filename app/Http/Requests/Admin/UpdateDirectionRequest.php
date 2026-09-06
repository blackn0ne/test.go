<?php

namespace App\Http\Requests\Admin;

use App\Enums\SubjectKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDirectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $direction = $this->route('direction');

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('directions', 'code')->ignore($direction),
            ],
            'name' => ['required', 'string', 'max:255'],
            'first_subject_id' => [
                'required',
                'integer',
                'different:second_subject_id',
                Rule::exists('subjects', 'id')->where('kind', SubjectKind::Profile->value),
            ],
            'second_subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where('kind', SubjectKind::Profile->value),
            ],
        ];
    }
}
