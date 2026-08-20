@extends('layouts.auth')

@section('title', 'Reset your password')

@section('auth-content')
    <h1 class="auth-title">Forgot your password?</h1>
    <p class="auth-lede">Enter the email address on your account and we&rsquo;ll send you a link to choose a new password.</p>

    @if (session('status'))
        <div class="alert alert-success" role="status">
            <i class="fa-solid fa-circle-check"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    @error('auth')
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="you@promoseven.com"
                   autocomplete="email" required autofocus
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <div class="field-error" id="email-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <div class="auth-actions">
            <button type="submit" class="btn btn-primary btn-lg btn-block">
                <i class="fa-solid fa-paper-plane"></i> Send reset link
            </button>
        </div>
    </form>
@endsection
