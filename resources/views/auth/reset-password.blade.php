@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('auth-content')
    <h1 class="auth-title">Choose a new password</h1>
    <p class="auth-lede">Pick something at least 8 characters long that you don&rsquo;t use anywhere else.</p>

    @error('auth')
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-grid cols-1">
            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $email) }}" autocomplete="email" required
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <div class="field-error" id="email-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">New password</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       placeholder="At least 8 characters" minlength="8"
                       autocomplete="new-password" required autofocus
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <div class="field-error" id="password-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                       placeholder="Type it again" minlength="8" autocomplete="new-password" required>
            </div>
        </div>

        <div class="auth-actions">
            <button type="submit" class="btn btn-primary btn-lg btn-block">
                <i class="fa-solid fa-key"></i> Set new password
            </button>
        </div>
    </form>
@endsection
