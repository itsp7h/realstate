@extends('layouts.admin')

@section('title', 'Edit Floor — ' . $floor->floor_name)
@section('topbar-title', 'Edit Floor')

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.index') }}">Buildings</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.show', $building) }}?tab=floors">{{ $building->property_name }}</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Edit Floor</span>
        </div>
        <h1 class="page-header-title">Edit — {{ $floor->floor_name }}</h1>
        <p class="page-header-sub">Editing floor in <strong>{{ $building->property_name }}</strong></p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('buildings.show', $building) }}?tab=floors" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Floors
        </a>
    </div>
</div>

<form method="POST" action="{{ route('floors.update', $floor) }}" novalidate>
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <h3>Floor Details</h3>
                <p>Update the floor information</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label>Floor Name <span class="required">*</span></label>
                    <input type="text" name="floor_name"
                        value="{{ old('floor_name', $floor->floor_name) }}"
                        placeholder="e.g. Floor 26"
                        class="{{ $errors->has('floor_name') ? 'error' : '' }}"
                        required autofocus>
                    @error('floor_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Floor Code</label>
                    <input type="text" name="floor_code"
                        value="{{ old('floor_code', $floor->floor_code) }}"
                        placeholder="e.g. FL26"
                        class="{{ $errors->has('floor_code') ? 'error' : '' }}">
                    @error('floor_code') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Block Name</label>
                    <input type="text" name="block_name"
                        value="{{ old('block_name', $floor->block_name) }}"
                        placeholder="e.g. Block 1"
                        class="{{ $errors->has('block_name') ? 'error' : '' }}">
                    @error('block_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Block Code</label>
                    <input type="text" name="block_code"
                        value="{{ old('block_code', $floor->block_code) }}"
                        placeholder="e.g. BL1"
                        class="{{ $errors->has('block_code') ? 'error' : '' }}">
                    @error('block_code') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Total No. of Units</label>
                    <input type="number" name="total_no_of_units"
                        value="{{ old('total_no_of_units', $floor->total_no_of_units) }}"
                        placeholder="e.g. 10"
                        min="1"
                        class="{{ $errors->has('total_no_of_units') ? 'error' : '' }}">
                    @error('total_no_of_units') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:20px;justify-content:flex-end;">
        <a href="{{ route('buildings.show', $building) }}?tab=floors" class="btn btn-outline">
            <i class="fa-solid fa-xmark"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
        </button>
    </div>
</form>

@endsection
