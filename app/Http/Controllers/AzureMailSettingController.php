<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAzureMailSettingRequest;
use App\Models\AzureMailSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AzureMailSettingController extends Controller
{
    public function edit(): View
    {
        $setting = AzureMailSetting::current();

        return view('settings.azure-mail', compact('setting'));
    }

    public function update(UpdateAzureMailSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $setting = AzureMailSetting::current();

        if (blank($data['client_secret'] ?? null)) {
            unset($data['client_secret']);
        }

        $setting->fill($data);
        $setting->save();

        return redirect()->route('settings.azure-mail.edit')
            ->with('success', 'Azure Mail settings saved.');
    }

    public function sendTest(): RedirectResponse
    {
        $setting = AzureMailSetting::current();

        if (! $setting->isConfigured()) {
            return redirect()->route('settings.azure-mail.edit')
                ->with('error', 'Save all four fields before sending a test email.');
        }

        try {
            Mail::mailer('azure')->raw(
                'This is a test email from the RealEstate app, confirming the Azure Mail configuration works.',
                function ($message) {
                    $message->to(Auth::user()->email)
                            ->subject('Azure Mail test — RealEstate');
                }
            );
        } catch (Throwable $e) {
            return redirect()->route('settings.azure-mail.edit')
                ->with('error', 'Test email failed: ' . $e->getMessage());
        }

        return redirect()->route('settings.azure-mail.edit')
            ->with('success', 'Test email sent to ' . Auth::user()->email . '.');
    }
}
