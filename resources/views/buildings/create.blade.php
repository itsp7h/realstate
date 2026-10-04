@extends('layouts.admin')

@section('title', 'Add Building')
@section('topbar-title', 'Add Building')

@section('content')

@section('page-breadcrumb')
    <a href="{{ url('/dashboard') }}">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('buildings.index') }}">Buildings</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>Add Building</span>
@endsection
@section('page-title', 'Add Building')
@section('page-subtitle', 'Fill in all sections to register a new building')


@include('buildings._form', [
    'building'   => $building,
    'action'     => route('buildings.store'),
    'method'     => 'POST',
    'formFields' => $formFields,
])

@endsection
