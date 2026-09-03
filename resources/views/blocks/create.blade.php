@extends('layouts.admin')

@section('title', 'Add Block — ' . $building->property_name)
@section('topbar-title', 'Add Block')

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.index') }}">Buildings</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.blocks.index', $building) }}">{{ $building->property_name }}</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Add Block</span>
        </div>
        <h1 class="page-header-title">Add Block</h1>
        <p class="page-header-sub">Add a new block to <strong>{{ $building->property_name }}</strong></p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('buildings.blocks.index', $building) }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Blocks
        </a>
    </div>
</div>

<form method="POST" action="{{ route('buildings.blocks.store', $building) }}" novalidate>
    @csrf

    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-building"></i></div>
            <div>
                <h3>Block Details</h3>
                <p>Enter the block information for {{ $building->property_name }}</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label>Block Name <span class="required">*</span></label>
                    <input type="text" name="block_name"
                        value="{{ old('block_name') }}"
                        placeholder="e.g. Block A"
                        class="{{ $errors->has('block_name') ? 'error' : '' }}"
                        required autofocus>
                    @error('block_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Block Code</label>
                    <input type="text" name="block_code"
                        value="{{ old('block_code') }}"
                        placeholder="e.g. BLK-A"
                        class="{{ $errors->has('block_code') ? 'error' : '' }}">
                    @error('block_code') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Total No. of Floors</label>
                    <input type="number" name="total_no_of_floors"
                        value="{{ old('total_no_of_floors') }}"
                        placeholder="e.g. 10"
                        min="1"
                        class="{{ $errors->has('total_no_of_floors') ? 'error' : '' }}">
                    @error('total_no_of_floors') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
        <a href="{{ route('buildings.blocks.index', $building) }}" class="btn btn-outline">
            <i class="fa-solid fa-xmark"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Save Block
        </button>
    </div>
</form>

@endsection
