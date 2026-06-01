@extends('layouts.admin')

@section('title', 'Edit — ' . $building->property_name)
@section('topbar-title', 'Edit Building')

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.index') }}">Buildings</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ $building->property_name }}</span>
        </div>
        <h1 class="page-header-title">Edit: {{ $building->property_name }}</h1>
        <p class="page-header-sub">{{ $building->property_code }} &mdash; {{ $building->property_type }}</p>
    </div>
    <div class="page-header-actions">
        <form method="POST" action="{{ route('buildings.destroy', $building) }}"
              onsubmit="return confirm('Delete this building? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fa-regular fa-trash-can"></i> Delete Building
            </button>
        </form>
    </div>
</div>

@include('buildings._form', [
    'building'   => $building,
    'action'     => route('buildings.update', $building),
    'method'     => 'PUT',
    'formFields' => $formFields,
])

@endsection
