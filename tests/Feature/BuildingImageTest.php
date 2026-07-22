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
