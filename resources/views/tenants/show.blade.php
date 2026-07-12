@extends('layouts.admin')

@section('title', $tenant->name . ' — Tenant Profile')
@section('topbar-title', 'Tenant Profile')

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('tenants.index') }}">Tenants</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Profile</span>
        </div>
        <h1 class="page-header-title">Tenant Profile</h1>
        <p class="page-header-sub">Full details for this tenant record</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('tenants.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

@include('tenants._profile', ['tenant' => $tenant, 'rentSchedule' => $rentSchedule])

@endsection
