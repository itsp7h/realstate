<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A profile photo.
 *
 * Every avatar in the shell drew a single letter before this, in five places
 * that each built it themselves. The photo is stored as a path on the public
 * disk — like the branding logo — and the initial stays as the fallback, which
 * is the normal state for an account that has not uploaded one.
 */
class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function image(string $name = 'me.jpg', int $w = 200, int $h = 200): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }

    public function test_an_account_can_upload_a_photo(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Dana', 'email' => 'dana@example.com', 'photo' => $this->image(),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertNotNull($user->photo_path);
        Storage::disk('public')->assertExists($user->photo_path);
        $this->assertStringContainsString('avatars/', $user->photo_path);
    }

    /** Five uploads must not leave five files on disk. */
    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com', 'photo' => $this->image('first.jpg'),
        ]);
        $first = $user->refresh()->photo_path;

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com', 'photo' => $this->image('second.jpg'),
        ]);
        $second = $user->refresh()->photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_removing_a_photo_clears_the_column_and_the_file(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com', 'photo' => $this->image(),
        ]);
        $path = $user->refresh()->photo_path;

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com', 'remove_photo' => '1',
        ]);

        $this->assertNull($user->refresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    /** A replacement and a removal in one request is a contradiction; the file wins. */
    public function test_uploading_while_also_asking_to_remove_keeps_the_upload(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com',
            'photo' => $this->image(), 'remove_photo' => '1',
        ]);

        $this->assertNotNull($user->refresh()->photo_path);
    }

    public function test_a_non_image_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Dana', 'email' => 'dana@example.com',
                'photo' => UploadedFile::fake()->create('document.pdf', 40, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->photo_path);
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Dana', 'email' => 'dana@example.com',
                'photo' => UploadedFile::fake()->image('huge.jpg', 400, 400)->size(3000),
            ])
            ->assertSessionHasErrors('photo');
    }

    /** A 16px favicon stretched across the account chip is the thing to stop. */
    public function test_a_tiny_image_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Dana', 'email' => 'dana@example.com',
                'photo' => $this->image('favicon.png', 32, 32),
            ])
            ->assertSessionHasErrors('photo');
    }

    /** Self-service must not become a way around the admin-only role. */
    public function test_the_photo_field_cannot_smuggle_a_role_change(): void
    {
        $user = User::factory()->create(['name' => 'Dana', 'email' => 'dana@example.com', 'role' => 'viewer']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com',
            'photo' => $this->image(), 'role' => 'admin',
        ]);

        $this->assertSame('viewer', $user->refresh()->role);
    }

    public function test_the_change_is_audited(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com', 'photo' => $this->image(),
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'profile_updated']);

        // Adding, replacing and removing are three different events on a
        // security log, so the payload says which one happened.
        $log = \App\Models\AuditLog::where('action', 'profile_updated')->latest()->first();
        $this->assertSame('added', $log->changes['photo']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com', 'photo' => $this->image('again.jpg'),
        ]);
        $this->assertSame('replaced', \App\Models\AuditLog::where('action', 'profile_updated')
            ->latest()->first()->changes['photo']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana', 'email' => 'dana@example.com', 'remove_photo' => '1',
        ]);
        $this->assertSame('removed', \App\Models\AuditLog::where('action', 'profile_updated')
            ->latest()->first()->changes['photo']);
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function test_the_avatar_falls_back_to_the_initial(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana Reed']);

        $this->assertNull($user->photoUrl());
        $this->assertSame('D', $user->initial());

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringNotContainsString('avatar-photo', $html);
    }

    public function test_the_avatar_renders_the_photo_once_uploaded(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana Reed', 'email' => 'dana@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana Reed', 'email' => 'dana@example.com', 'photo' => $this->image(),
        ]);

        $html = $this->actingAs($user->refresh())->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('avatar-photo', $html);
        $this->assertStringContainsString('has-photo', $html);
        $this->assertStringContainsString($user->photo_path, $html);
    }

    /** One component, so the photo cannot appear in some avatars and not others. */
    public function test_every_avatar_goes_through_the_shared_component(): void
    {
        foreach ([
            'layouts/admin', 'dashboard', 'tenants/show', 'buildings/show',
        ] as $view) {
            $source = file_get_contents(resource_path("views/{$view}.blade.php"));

            $this->assertStringNotContainsString(
                "strtoupper(substr(auth()->user()->name",
                $source,
                "{$view} builds its own initial instead of using <x-avatar>."
            );
        }
    }

    public function test_the_upload_form_accepts_a_file(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('name="photo"', $html);
        $this->assertStringContainsString('data-preview="photo-preview"', $html);
    }
}
