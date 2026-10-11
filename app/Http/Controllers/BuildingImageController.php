<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\BuildingImage;
use App\Http\Requests\StoreBuildingImageRequest;
use App\Services\BuildingImageThumbnailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuildingImageController extends Controller
{
    public function store(StoreBuildingImageRequest $request, Building $building, BuildingImageThumbnailer $thumbnailer)
    {
        $nextOrder = $building->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $file) {
            $path = $file->store("buildings/{$building->id}", 'public');
            $building->images()->create(['path' => $path, 'sort_order' => $nextOrder++]);
            $thumbnailer->generate($path);
        }

        $count = count($request->file('images'));

        return redirect()
            ->route('buildings.show', [$building, 'tab' => 'photos'])
            ->with('success', $count === 1 ? '1 photo uploaded.' : "$count photos uploaded.");
    }

    public function destroy(Building $building, BuildingImage $image, BuildingImageThumbnailer $thumbnailer)
    {
        abort_if($image->building_id !== $building->id, 403);

        Storage::disk('public')->delete($image->path);
        $thumbnailer->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    public function reorder(Request $request, Building $building)
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($request->input('order') as $sortOrder => $imageId) {
            $building->images()->where('id', $imageId)->update(['sort_order' => $sortOrder]);
        }

        return response()->json(['ok' => true]);
    }
}
