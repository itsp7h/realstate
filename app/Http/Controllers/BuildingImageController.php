<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\BuildingImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuildingImageController extends Controller
{
    public function store(Request $request, Building $building)
    {
        $request->validate([
            'images'   => ['required', 'array', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ]);

        $nextOrder = $building->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $file) {
            $path = $file->store("buildings/{$building->id}", 'public');
            $building->images()->create(['path' => $path, 'sort_order' => $nextOrder++]);
        }

        return back()->with('success', 'Images uploaded successfully.');
    }

    public function destroy(Building $building, BuildingImage $image)
    {
        abort_if($image->building_id !== $building->id, 403);

        Storage::disk('public')->delete($image->path);
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
