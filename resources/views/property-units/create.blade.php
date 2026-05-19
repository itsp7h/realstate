@extends('layouts.admin')

@section('title', 'Add Property Unit')
@section('topbar-title', 'Add Property Unit')

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('property-units.index') }}">Property Units</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Add Unit</span>
        </div>
        <h1 class="page-header-title">Add Property Unit</h1>
        <p class="page-header-sub">Fill in all sections to register a new unit</p>
    </div>
</div>

@include('property-units._form', [
    'unit'   => $unit,
    'action' => route('property-units.store'),
    'method' => 'POST',
])

@endsection
