<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlockRequest;
use App\Http\Requests\UpdateBlockRequest;
use App\Models\Block;
use App\Models\Building;

class BlockController extends Controller
{
    public function index(Building $building)
    {
        $blocks = $building->blocks()
            ->withCount('floors')
            ->orderBy('block_name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_blocks' => $building->blocks()->count(),
            'total_floors' => $building->blocks()->withCount('floors')->get()->sum('floors_count'),
        ];

        return view('blocks.index', compact('building', 'blocks', 'stats'));
    }

    public function create(Building $building)
    {
        $block = new Block();
        return view('blocks.create', compact('building', 'block'));
    }

    public function store(Building $building, StoreBlockRequest $request)
    {
        $validated = $request->validated();
        $validated['building_id'] = $building->id;

        Block::create($validated);

        return redirect(route('buildings.show', $building) . '?tab=blocks')
            ->with('success', 'Block added successfully.');
    }

    public function edit(Block $block)
    {
        $building = $block->building;
        return view('blocks.edit', compact('building', 'block'));
    }

    public function update(UpdateBlockRequest $request, Block $block)
    {
        $block->update($request->validated());

        return redirect(route('buildings.show', $block->building) . '?tab=blocks')
            ->with('success', 'Block updated successfully.');
    }

    public function destroy(Block $block)
    {
        if ($block->floors()->exists()) {
            return back()->with('error', 'Cannot delete block — it still has floors linked to it.');
        }

        $building = $block->building;
        $block->delete();

        return redirect(route('buildings.show', $building) . '?tab=blocks')
            ->with('success', 'Block deleted successfully.');
    }
}
