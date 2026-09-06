<?php

namespace App\Http\Requests\Admin;

use App\Models\District;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistrictRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var District $district */
        $district = $this->route('district');
        $regionId = $this->integer('region_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('districts', 'name')
                    ->where('region_id', $regionId)
                    ->ignore($district->id),
            ],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Район с таким названием уже существует в этой области.',
        ];
    }
}
