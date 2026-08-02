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
                ->with('error', 'Test email failed: ' . $this->friendlyMailErrorMessage($e));
        }

        return redirect()->route('settings.azure-mail.edit')
            ->with('success', 'Test email sent to ' . Auth::user()->email . '.');
    }

    /**
     * Translates known Graph API failure strings into messages an admin can
     * act on without needing to decode Microsoft's raw error codes. Falls
     * back to the original message for anything not recognized.
     */
    private function friendlyMailErrorMessage(Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, '[RAOP]') || str_contains($message, 'AppOnlyAccessPolicy')) {
            return "The From Address (\"{$this->currentFromAddress()}\") is not allowed to send emails through this app. Ask IT to add it to the approved senders list.";
        }

        if (str_contains($message, 'ErrorAccessDenied') || str_contains($message, 'Forbidden')) {
            return 'This app does not have permission to send from that address. Check the Azure app registration and its access policy.';
        }

        if (str_contains($message, 'invalid_client') || str_contains($message, 'AADSTS7000215')) {
            return 'The Client ID or Client Secret is incorrect. Double-check both against the Azure app registration.';
        }

        if (str_contains($message, 'invalid_grant') || str_contains($message, 'AADSTS90002')) {
            return 'The Tenant ID is incorrect or not recognized by Microsoft.';
        }

        return $message;
    }

    private function currentFromAddress(): string
    {
        return AzureMailSetting::current()->from_address ?? 'the configured address';
    }
}
