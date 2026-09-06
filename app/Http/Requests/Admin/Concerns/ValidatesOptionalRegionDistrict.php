<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\District;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait ValidatesOptionalRegionDistrict
{
    protected function prepareRegionDistrict(): void
    {
        $regionId = $this->input('region_id');
        $districtId = $this->input('district_id');

        if (blank($regionId)) {
            $this->merge([
                'region_id' => null,
                'district_id' => null,
            ]);

            return;
        }

        if (blank($districtId)) {
            $this->merge(['district_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function regionDistrictRules(): array
    {
        return [
            'region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
        ];
    }

    protected function validateRegionDistrict(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('district_id') && $this->filled('region_id')) {
                $belongsToRegion = District::query()
                    ->whereKey($this->integer('district_id'))
                    ->where('region_id', $this->integer('region_id'))
                    ->exists();

                if (! $belongsToRegion) {
                    $validator->errors()->add(
                        'district_id',
                        'Район не относится к выбранной области.',
                    );
                }
            }
        });
    }
}
