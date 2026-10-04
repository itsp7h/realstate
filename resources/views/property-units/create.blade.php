@extends('layouts.admin')

@section('title', 'Add Property Unit')
@section('topbar-title', 'Add Property Unit')

@section('content')

@section('page-breadcrumb')
    <a href="{{ url('/dashboard') }}">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('property-units.index') }}">Property Units</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>Add Unit</span>
@endsection
@section('page-title', 'Add Property Unit')
@section('page-subtitle', 'Fill in all sections to register a new unit')


@include('property-units._form', [
    'unit'       => $unit,
    'action'     => route('property-units.store'),
    'method'     => 'POST',
    'formFields' => $formFields,
])

@endsection
