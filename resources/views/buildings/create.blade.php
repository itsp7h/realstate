@extends('layouts.admin')

@section('title', 'Add Building')
@section('topbar-title', 'Add Building')

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.index') }}">Buildings</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Add Building</span>
        </div>
        <h1 class="page-header-title">Add Building</h1>
        <p class="page-header-sub">Fill in all sections to register a new building</p>
    </div>
</div>

@include('buildings._form', [
    'building'   => $building,
    'action'     => route('buildings.store'),
    'method'     => 'POST',
    'formFields' => $formFields,
])

@endsection
