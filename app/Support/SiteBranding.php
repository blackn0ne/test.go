<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

final class SiteBranding
{
    /**
     * @return array{
     *     project_name: string|null,
     *     logo_url: string|null,
     *     favicon_url: string|null
     * }
     */
    public static function shared(): array
    {
        $settings = SiteSetting::current();

        return [
            'project_name' => $settings->project_name,
            'logo_url' => $settings->logo_path
                ? Storage::url($settings->logo_path)
                : null,
            'favicon_url' => $settings->favicon_path
                ? Storage::url($settings->favicon_path)
                : null,
        ];
    }

    public static function name(): string
    {
        return SiteSetting::current()->project_name ?: config('app.name');
    }
}
