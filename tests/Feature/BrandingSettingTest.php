<?php

namespace Tests\Feature;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('settings.branding.edit'))->assertOk();
    }

    public function test_user_role_cannot_access_branding_settings(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $this->get(route('settings.branding.edit'))->assertForbidden();
    }

    public function test_maintenance_cannot_access_branding_settings(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $this->get(route('settings.branding.edit'))->assertForbidden();
    }

    public function test_admin_can_save_text_fields(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'site_name'       => 'Acme RE',
            'company_name'    => 'Acme Holdings',
            'tagline'         => 'Property Suite',
            'primary_color'   => '#112233',
            'secondary_color' => '#AABBCC',
        ])->assertRedirect(route('settings.branding.edit'));

        $setting = BrandingSetting::current();
        $this->assertSame('Acme RE', $setting->site_name);
        $this->assertSame('Acme Holdings', $setting->company_name);
        $this->assertSame('Property Suite', $setting->tagline);
        $this->assertSame('#112233', $setting->primary_color);
        $this->assertSame('#AABBCC', $setting->secondary_color);
    }

    // ── Letterhead contact email ─────────────────────────────────────────────

    public function test_admin_can_save_the_contact_email(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'company_email' => 'realestateaccounts@promoseven.com',
        ])->assertRedirect(route('settings.branding.edit'));

        $this->assertSame('realestateaccounts@promoseven.com', BrandingSetting::current()->company_email);
    }

    public function test_the_contact_email_field_is_on_the_form(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        BrandingSetting::current()->update(['company_email' => 'accounts@example.com']);

        $this->get(route('settings.branding.edit'))
            ->assertOk()
            ->assertSee('name="company_email"', false)
            ->assertSee('type="email"', false)
            ->assertSee('accounts@example.com', false);
    }

    /** It prints on tenant-facing documents, so free text is not acceptable. */
    public function test_validates_the_contact_email_is_an_address(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'company_email' => 'not-an-email',
        ])->assertSessionHasErrors(['company_email']);

        $this->assertNull(BrandingSetting::current()->company_email);
    }

    public function test_validates_the_contact_email_length(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'company_email' => str_repeat('a', 250) . '@example.com',
        ])->assertSessionHasErrors(['company_email']);
    }

    /** Optional: the letterhead simply drops the line when it is blank. */
    public function test_the_contact_email_may_be_cleared(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        BrandingSetting::current()->update(['company_email' => 'accounts@example.com']);

        $this->put(route('settings.branding.update'), [
            'company_email' => '',
        ])->assertRedirect(route('settings.branding.edit'));

        $this->assertNull(BrandingSetting::current()->company_email);
    }

    public function test_validates_color_fields_are_hex(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'primary_color'   => 'blue',
            'secondary_color' => '#ZZZZZZ',
        ])->assertSessionHasErrors(['primary_color', 'secondary_color']);
    }

    public function test_validates_string_field_lengths(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'site_name' => str_repeat('a', 101),
        ])->assertSessionHasErrors(['site_name']);
    }

    public function test_admin_can_upload_logo_and_favicon(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'logo'    => UploadedFile::fake()->image('logo.png', 200, 200),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        ])->assertRedirect(route('settings.branding.edit'));

        $setting = BrandingSetting::current();
        $this->assertNotNull($setting->logo_path);
        $this->assertNotNull($setting->favicon_path);
        Storage::disk('public')->assertExists($setting->logo_path);
        Storage::disk('public')->assertExists($setting->favicon_path);
    }

    public function test_rejects_non_image_logo_upload(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.branding.update'), [
            'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['logo']);
    }

    public function test_uploading_a_new_logo_replaces_and_deletes_the_old_one(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        BrandingSetting::current()->update(['logo_path' => 'branding/old-logo.png']);
        Storage::disk('public')->put('branding/old-logo.png', 'fake-contents');

        $this->put(route('settings.branding.update'), [
            'logo' => UploadedFile::fake()->image('new-logo.png'),
        ])->assertRedirect(route('settings.branding.edit'));

        Storage::disk('public')->assertMissing('branding/old-logo.png');
        $this->assertNotSame('branding/old-logo.png', BrandingSetting::current()->logo_path);
    }

    public function test_remove_logo_flag_clears_the_stored_logo(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        BrandingSetting::current()->update(['logo_path' => 'branding/existing-logo.png']);
        Storage::disk('public')->put('branding/existing-logo.png', 'fake-contents');

        $this->put(route('settings.branding.update'), [
            'remove_logo' => '1',
        ])->assertRedirect(route('settings.branding.edit'));

        Storage::disk('public')->assertMissing('branding/existing-logo.png');
        $this->assertNull(BrandingSetting::current()->logo_path);
    }

    public function test_remove_favicon_flag_clears_the_stored_favicon(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        BrandingSetting::current()->update(['favicon_path' => 'branding/existing-favicon.png']);
        Storage::disk('public')->put('branding/existing-favicon.png', 'fake-contents');

        $this->put(route('settings.branding.update'), [
            'remove_favicon' => '1',
        ])->assertRedirect(route('settings.branding.edit'));

        Storage::disk('public')->assertMissing('branding/existing-favicon.png');
        $this->assertNull(BrandingSetting::current()->favicon_path);
    }

    public function test_display_site_name_falls_back_to_app_name_when_blank(): void
    {
        $setting = BrandingSetting::current();

        $this->assertSame(config('app.name'), $setting->displaySiteName());

        $setting->update(['site_name' => 'Acme RE']);
        $this->assertSame('Acme RE', $setting->displaySiteName());
    }
}
