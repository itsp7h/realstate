<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingImage;
use App\Services\BuildingImageThumbnailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuildingImageThumbnailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function building(): Building
    {
        return Building::create(['property_name' => 'Test Tower', 'property_code' => 'TT1']);
    }

    private function thumbSize(string $originalPath): array
    {
        $thumb = Storage::disk('public')->path(BuildingImageThumbnailer::thumbPath($originalPath));

        return array_slice(getimagesize($thumb), 0, 2);
    }

    public function test_thumb_path_sits_in_a_thumbs_folder_as_jpg(): void
    {
        $this->assertSame('buildings/4/thumbs/abc.jpg', BuildingImageThumbnailer::thumbPath('buildings/4/abc.jpg'));
        $this->assertSame('buildings/4/thumbs/abc.jpg', BuildingImageThumbnailer::thumbPath('buildings/4/abc.png'));
        $this->assertSame('thumbs/abc.jpg', BuildingImageThumbnailer::thumbPath('abc.webp'));
    }

    public function test_upload_creates_a_thumbnail_capped_at_800px_keeping_aspect_ratio(): void
    {
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('wide.jpg', 3000, 2000)],
        ])->assertRedirect();

        $path = $building->images()->first()->path;
        Storage::disk('public')->assertExists(BuildingImageThumbnailer::thumbPath($path));
        $this->assertSame([800, 533], $this->thumbSize($path));
    }

    public function test_portrait_photo_is_capped_on_its_height(): void
    {
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('tall.jpg', 2000, 3000)],
        ]);

        $this->assertSame([533, 800], $this->thumbSize($building->images()->first()->path));
    }

    public function test_small_photo_is_not_enlarged(): void
    {
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('small.png', 300, 200)],
        ]);

        $path = $building->images()->first()->path;
        $this->assertSame([300, 200], $this->thumbSize($path));
        $this->assertSame(IMAGETYPE_JPEG, getimagesize(Storage::disk('public')->path(BuildingImageThumbnailer::thumbPath($path)))[2]);
    }

    public function test_thumb_url_points_at_the_thumbnail_when_it_exists(): void
    {
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('photo.jpg', 1600, 1200)],
        ]);

        $image = $building->images()->first();
        $this->assertStringContainsString('/thumbs/', $image->thumb_url);
        $this->assertStringNotContainsString('/thumbs/', $image->url);
    }

    public function test_thumb_url_falls_back_to_the_original_without_a_thumbnail(): void
    {
        $building = $this->building();
        $image = BuildingImage::create(['building_id' => $building->id, 'path' => 'buildings/1/old.jpg', 'sort_order' => 0]);

        $this->assertSame($image->url, $image->thumb_url);
    }

    public function test_generate_returns_false_for_missing_or_unreadable_files(): void
    {
        Storage::disk('public')->put('buildings/1/broken.jpg', 'not an image');
        $thumbnailer = new BuildingImageThumbnailer();

        $this->assertFalse($thumbnailer->generate('buildings/1/missing.jpg'));
        $this->assertFalse($thumbnailer->generate('buildings/1/broken.jpg'));
        Storage::disk('public')->assertMissing('buildings/1/thumbs/broken.jpg');
    }

    public function test_deleting_a_photo_removes_its_thumbnail(): void
    {
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('photo.jpg', 1600, 1200)],
        ]);
        $image = $building->images()->first();
        $thumb = BuildingImageThumbnailer::thumbPath($image->path);
        Storage::disk('public')->assertExists($thumb);

        $this->delete(route('buildings.images.destroy', [$building, $image]))->assertRedirect();

        Storage::disk('public')->assertMissing($thumb);
    }

    public function test_command_fills_in_missing_thumbnails_and_skips_existing_ones(): void
    {
        $building = $this->building();

        // Two photos stored the way older uploads were: original only.
        foreach (['a', 'b'] as $name) {
            UploadedFile::fake()->image("$name.jpg", 1600, 1200)->storeAs("buildings/{$building->id}", "$name.jpg", 'public');
            BuildingImage::create(['building_id' => $building->id, 'path' => "buildings/{$building->id}/$name.jpg", 'sort_order' => 0]);
        }
        // One that points at a file that's gone.
        BuildingImage::create(['building_id' => $building->id, 'path' => "buildings/{$building->id}/gone.jpg", 'sort_order' => 0]);

        $this->artisan('buildings:thumbnails')
            ->expectsOutputToContain('Thumbnails: 2 created, 0 already present, 1 failed.')
            ->assertSuccessful();

        Storage::disk('public')->assertExists("buildings/{$building->id}/thumbs/a.jpg");
        Storage::disk('public')->assertExists("buildings/{$building->id}/thumbs/b.jpg");

        $this->artisan('buildings:thumbnails')
            ->expectsOutputToContain('Thumbnails: 0 created, 2 already present, 1 failed.')
            ->assertSuccessful();
    }

    public function test_pages_show_thumbnails_and_the_lightbox_opens_the_original(): void
    {
        $building = $this->building();

        $this->post(route('buildings.images.store', $building), [
            'images' => [UploadedFile::fake()->image('photo.jpg', 1600, 1200)],
        ]);
        $image = $building->images()->first();

        $this->get(route('buildings.show', $building))
            ->assertOk()
            ->assertSee('src="' . $image->thumb_url . '"', false)
            ->assertSee("openLightbox('" . $image->url . "')", false);

        $this->get(route('buildings.index'))
            ->assertOk()
            ->assertSee($image->thumb_url, false);
    }

    public function test_both_deploys_backfill_thumbnails_before_fixing_permissions(): void
    {
        foreach (['.github/workflows/deploy.yml', '.github/workflows/deploy-staging.yml'] as $file) {
            $yaml = file_get_contents(base_path($file));
            $thumbs = strpos($yaml, 'php artisan buildings:thumbnails');
            $this->assertNotFalse($thumbs, "$file never generates thumbnails");
            $this->assertLessThan(strpos($yaml, 'sudo chown -R actionsrunner'), $thumbs, "$file must generate thumbnails before the permissions fix");
        }
    }
}
