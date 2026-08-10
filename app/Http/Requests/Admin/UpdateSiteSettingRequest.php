<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'social_networks' => ['nullable', 'array'],
            'social_networks.facebook' => ['nullable', 'string', 'max:255'],
            'social_networks.instagram' => ['nullable', 'string', 'max:255'],
            'social_networks.telegram' => ['nullable', 'string', 'max:255'],
            'social_networks.youtube' => ['nullable', 'string', 'max:255'],
            'social_networks.whatsapp' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
        ];
    }
}
