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
@section('page-back')
    <a href="{{ route('tenants.index') }}" class="btn btn-outline" aria-label="Back">
        <i class="fa-solid fa-arrow-left"></i><span class="pagehead-back-label"> Back</span>
    </a>
@endsection



{{-- MOBILE: pushed-screen header with a back chevron, per the Miknas design --}}
<div class="pm-push-header">
    <a href="{{ route('tenants.index') }}" class="pm-push-back"><i class="fa-solid fa-chevron-left"></i></a>
    <div class="pm-header-text">
        <div class="pm-title" style="font-size:19px;">{{ $tenant->name }}</div>
        <div class="pm-subtitle">Tenant</div>
    </div>
    {{-- Same bell, same destination as the dashboard's: the alert list in the
         Today segment. It carries no dot here because this screen doesn't
         compute portfolio metrics — it's the way there, not the count. --}}
    <a href="{{ route('dashboard') }}#today" class="pm-icon-btn" title="Alerts"
       aria-label="Show what needs you today"><i class="fa-regular fa-bell" aria-hidden="true"></i></a>
    <x-avatar class="pm-avatar" tag="div" style="font-size:14px;" />
</div>



@include('tenants._profile', ['tenant' => $tenant, 'rentSchedule' => $rentSchedule])

@endsection
