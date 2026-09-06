<?php

namespace App\Http\Requests\Admin;

use App\Models\Region;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Region $region */
        $region = $this->route('region');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('regions', 'name')->ignore($region->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Область с таким названием уже существует.',
        ];
    }
}
