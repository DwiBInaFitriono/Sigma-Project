@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="text-center mb-8">
            <h1 class="auth-title">Daftar SIGMA</h1>
            <p class="auth-subtitle">Buat akun untuk memantau data gempa dan menerima alert</p>
            <div class="auth-icon-wrapper">
                <svg class="mx-auto" style="height: 2rem; width: 2rem; color: #7c4c2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf

            <div>
                <label for="name" class="auth-label">Nama</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="auth-input">
                @error('name')
                    <p class="auth-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="auth-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="auth-input">
                @error('email')
                    <p class="auth-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="auth-label">Password</label>
                <input id="password" name="password" type="password" required class="auth-input">
                @error('password')
                    <p class="auth-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="auth-label">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="auth-input">
            </div>

            <div>
                <button type="submit" class="auth-button">Daftar Akun</button>
            </div>
        </form>

        <div class="auth-note">
            Setelah mendaftar, Anda akan langsung masuk dan diarahkan ke dashboard.
        </div>
    </div>
</div>

<script src="{{ asset('js/theme.js') }}"></script>
@endsection