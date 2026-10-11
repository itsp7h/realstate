<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuildingImageTest extends TestCase
{
    use RefreshDatabase;

    private function building(): Building
    {
        return Building::create([
            'property_name' => 'Test Tower',
            'property_code' => 'TT1',
        ]);
    }

    /**
     * Photo URLs must follow the host the request came in on, not APP_URL —
     * staging is reached by IP while APP_URL names a subdomain the browser
     * cannot resolve, which made every <img> fail with ERR_NAME_NOT_RESOLVED.
     */
    public function test_image_url_follows_request_host_not_app_url(): void
    {
        config(['app.url' => 'http://unresolvable.example']);
        $building = $this->building();
        $image = BuildingImage::create([
            'building_id' => $building->id,
            'path' => 'buildings/'.$building->id.'/img1.jpg',
            'sort_order' => 0,
        ]);

        $this->assertStringNotContainsString('unresolvable.example', $image->url);
        $this->assertStringEndsWith('/storage/buildings/'.$building->id.'/img1.jpg', $image->url);
    }

    public function test_show_page_has_photos_tab(): void
    {
        $building = $this->building();
        $this->get(route('buildings.show', $building))
            ->assertOk()
            ->assertSee('photos-tab', false)
            ->assertSee('panel-photos', false);
    }

    public function test_upload_single_image(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('photo.jpg', 800, 600)],
        ])->assertRedirect();

        $this->assertCount(1, $building->fresh()->images);
        Storage::disk('public')->assertExists($building->images()->first()->path);
    }

    public function test_upload_multiple_images(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.png'),
            ],
        ])->assertRedirect();

        $this->assertCount(3, $building->fresh()->images);
    }

    public function test_upload_assigns_sort_order(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.jpg'),
            ],
        ]);

        $orders = $building->images()->pluck('sort_order')->toArray();
        $this->assertEquals([1, 2], $orders);
    }

    public function test_upload_rejects_non_image(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors();
    }

    public function test_upload_rejects_oversized_file(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('big.jpg')->size(5000)],
        ])->assertSessionHasErrors();
    }

    public function test_upload_requires_at_least_one_file(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [])
            ->assertSessionHasErrors('images');
    }

    public function test_successful_upload_returns_to_photos_tab_with_count(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])
            ->assertRedirect(route('buildings.show', [$building, 'tab' => 'photos']))
            ->assertSessionHas('success', '2 photos uploaded.');
    }

    public function test_rejected_upload_returns_to_photos_tab_whatever_the_referrer(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->from(route('buildings.index'))
            ->post(route('buildings.images.store', $building), [
                'images' => [UploadedFile::fake()->image('big.jpg')->size(5000)],
            ])
            ->assertRedirect(route('buildings.show', [$building, 'tab' => 'photos']));
    }

    public function test_oversized_photo_error_names_the_file(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('MP4.jpg')->size(10367)],
        ])->assertSessionHasErrors([
            'images.0' => '"MP4.jpg" is larger than 4 MB. Compress or resize it and try again.',
        ]);

        $this->assertCount(0, $building->fresh()->images);
    }

    public function test_non_image_error_names_the_file(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->create('lease.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors([
            'images.0' => '"lease.pdf" isn\'t a JPG, PNG or WEBP image.',
        ]);
    }

    public function test_more_than_ten_photos_is_rejected(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->image("p$i.jpg");
        }

        $this->post(route('buildings.images.store', $building), ['images' => $files])
            ->assertSessionHasErrors(['images' => 'You can upload up to 10 photos at a time.']);

        $this->assertCount(0, $building->fresh()->images);
    }

    public function test_one_bad_photo_rejects_the_whole_batch(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('ok.jpg'), UploadedFile::fake()->image('huge.jpg')->size(5000)],
        ])->assertSessionHasErrors('images.1');

        $this->assertCount(0, $building->fresh()->images);
    }

    public function test_photos_tab_shows_upload_errors_after_rejection(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->followingRedirects()
            ->post(route('buildings.images.store', $building), [
                'images' => [UploadedFile::fake()->image('MP4.jpg')->size(10367)],
            ])
            ->assertOk()
            ->assertSee('No photos were uploaded')
            ->assertSee('&quot;MP4.jpg&quot; is larger than 4 MB', false);
    }

    public function test_photos_tab_error_box_is_hidden_without_errors_and_form_carries_limits(): void
    {
        $building = $this->building();

        $this->get(route('buildings.show', $building))
            ->assertOk()
            ->assertSee('id="photoUploadErrors" role="alert" tabindex="-1"  hidden', false)
            ->assertSee('data-max-kb="4096"', false)
            ->assertSee('data-max-files="10"', false);
    }

    public function test_destroy_removes_image_and_file(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        $image = $building->images()->first();
        $path  = $image->path;

        $this->delete(route('buildings.images.destroy', [$building, $image]))
            ->assertRedirect();

        $this->assertDatabaseMissing('building_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_destroy_rejects_image_from_another_building(): void
    {
        Storage::fake('public');
        $b1 = $this->building();
        $b2 = Building::create(['property_name' => 'Other', 'property_code' => 'OT1']);

        $this->post(route('buildings.images.store', $b1), [
            'images' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        $image = $b1->images()->first();

        $this->delete(route('buildings.images.destroy', [$b2, $image]))
            ->assertForbidden();
    }

    public function test_reorder_updates_sort_order(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ]);

        $ids = $building->images()->pluck('id')->toArray();

        $this->post(route('buildings.images.reorder', $building), [
            'order' => array_reverse($ids),
        ])->assertJson(['ok' => true]);

        $this->assertEquals(
            array_reverse($ids),
            $building->images()->orderBy('sort_order')->pluck('id')->toArray()
        );
    }

    public function test_images_cascade_delete_with_building(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        $this->assertCount(1, $building->images);
        $building->delete();
        $this->assertCount(0, BuildingImage::where('building_id', $building->id)->get());
    }

    public function test_card_view_shows_images(): void
    {
        Storage::fake('public');
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('photo.jpg')],
        ]);

        $this->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('photo-slide', false);
    }

    public function test_card_view_shows_placeholder_when_no_images(): void
    {
        $this->building();

        $this->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('photo-placeholder', false);
    }
}
