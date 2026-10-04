@extends('layouts.admin')

@section('title', 'Edit — ' . $building->property_name)
@section('topbar-title', 'Edit Building')

@section('content')

@section('page-breadcrumb')
    <a href="{{ url('/dashboard') }}">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('buildings.index') }}">Buildings</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>{{ $building->property_name }}</span>
@endsection
@section('page-title')
    Edit: {{ $building->property_name }}
@endsection
@section('page-subtitle')
    {{ $building->property_code }} &mdash; {{ $building->property_type }}
@endsection
@section('page-actions')
    <form method="POST" action="{{ route('buildings.destroy', $building) }}"
          onsubmit="return confirm('Delete this building? This cannot be undone.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">
            <i class="fa-regular fa-trash-can"></i> Delete Building
        </button>
    </form>
@endsection


@include('buildings._form', [
    'building'   => $building,
    'action'     => route('buildings.update', $building),
    'method'     => 'PUT',
    'formFields' => $formFields,
])

@endsection
