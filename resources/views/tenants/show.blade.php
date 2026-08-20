@extends('layouts.admin')

@section('title', $tenant->name . ' — Tenant Profile')
@section('topbar-title', 'Tenant Profile')

@section('content')

@section('page-breadcrumb')
    <a href="{{ url('/dashboard') }}">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('tenants.index') }}">Tenants</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>Profile</span>
@endsection
@section('page-title', 'Tenant Profile')
@section('page-subtitle', 'Full details for this tenant record')
@section('page-actions')
    <a href="{{ route('tenants.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

{{-- MOBILE: pushed-screen header with a back chevron, per the Miknas design --}}
<div class="pm-push-header">
    <a href="{{ route('tenants.index') }}" class="pm-push-back"><i class="fa-solid fa-chevron-left"></i></a>
    <div class="pm-header-text">
        <div class="pm-title" style="font-size:19px;">{{ $tenant->name }}</div>
        <div class="pm-subtitle">Tenant</div>
    </div>
    <button type="button" class="pm-icon-btn" title="Notifications — coming soon"><i class="fa-regular fa-bell"></i></button>
    <div class="pm-avatar" style="font-size:14px;">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
</div>



@include('tenants._profile', ['tenant' => $tenant, 'rentSchedule' => $rentSchedule])

@endsection
