<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show the site settings form.
     */
    public function edit(): Response
    {
        $settings = SiteSetting::current();

        return Inertia::render('admin/settings/Edit', [
            'settings' => [
                'project_name' => $settings->project_name,
                'description' => $settings->description,
                'keywords' => $settings->keywords,
                'address' => $settings->address,
                'phone' => $settings->phone,
                'social_networks' => $settings->social_networks ?? [
                    'facebook' => '',
                    'instagram' => '',
                    'telegram' => '',
                    'youtube' => '',
                    'whatsapp' => '',
                ],
                'logo_url' => $settings->logo_path ? Storage::url($settings->logo_path) : null,
                'favicon_url' => $settings->favicon_path ? Storage::url($settings->favicon_path) : null,
            ],
        ]);
    }

    /**
     * Update the site settings.
     */
    public function update(UpdateSiteSettingRequest $request): RedirectResponse
    {
        $settings = SiteSetting::current();
        $data = $request->safe()->except(['logo', 'favicon']);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::delete($settings->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('settings', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::delete($settings->favicon_path);
            }

            $data['favicon_path'] = $request->file('favicon')->store('settings', 'public');
        }

        $settings->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);

        return to_route('admin.settings.edit');
    }
}
