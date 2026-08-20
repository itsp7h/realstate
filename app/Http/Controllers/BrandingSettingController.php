<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBrandingSettingRequest;
use App\Models\BrandingSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class BrandingSettingController extends Controller
{
    public function edit(): View
    {
        $setting = BrandingSetting::current();

        return view('settings.branding', compact('setting'));
    }

    public function update(UpdateBrandingSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $setting = BrandingSetting::current();

        unset($data['logo'], $data['favicon'], $data['remove_logo'], $data['remove_favicon']);

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo') && $setting->logo_path) {
            Storage::disk('public')->delete($setting->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('favicon')) {
            if ($setting->favicon_path) {
                Storage::disk('public')->delete($setting->favicon_path);
            }
            $data['favicon_path'] = $request->file('favicon')->store('branding', 'public');
        } elseif ($request->boolean('remove_favicon') && $setting->favicon_path) {
            Storage::disk('public')->delete($setting->favicon_path);
            $data['favicon_path'] = null;
        }

        $setting->fill($data);
        $setting->save();

        return redirect()->route('settings.branding.edit')
            ->with('success', 'Branding settings saved.');
    }
}
