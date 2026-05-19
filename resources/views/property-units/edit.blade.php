@extends('layouts.admin')

@section('title', 'Edit — ' . $unit->unit_name)
@section('topbar-title', 'Edit Unit')

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('property-units.index') }}">Property Units</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ $unit->unit_name }}</span>
        </div>
        <h1 class="page-header-title">Edit: {{ $unit->unit_name }}</h1>
        <p class="page-header-sub">{{ $unit->description }} &mdash; {{ $unit->property_name }}</p>
    </div>
    <div class="page-header-actions">
        <form method="POST" action="{{ route('property-units.destroy', $unit) }}"
              onsubmit="return confirm('Delete this unit? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fa-regular fa-trash-can"></i> Delete Unit
            </button>
        </form>
    </div>
</div>

@include('property-units._form', [
    'unit'       => $unit,
    'action'     => route('property-units.update', $unit),
    'method'     => 'PUT',
    'formFields' => $formFields,
])

@endsection
