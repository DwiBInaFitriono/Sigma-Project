@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endpush

@section('content')
<div class="auth-page">
    <!-- Theme Toggle -->
    <button id="theme-toggle-btn" class="theme-toggle-btn" title="Switch to Dark Mode" aria-label="Toggle dark mode">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.536l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.828-2.828a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm.707-7.071a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM9 4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1zm0 14a1 1 0 01-1-1v-1a1 1 0 112 0v1a1 1 0 01-1 1zm8-1a1 1 0 111 0 4 4 0 01-4 4 1 1 0 110-2 2 2 0 002-2zM3 15a1 1 0 11-2 0 4 4 0 014-4 1 1 0 110 2 2 2 0 00-2 2z" clip-rule="evenodd"></path>
        </svg>
    </button>

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
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="off" class="auth-input">
                @error('email')
                    <p class="auth-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="auth-label">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="auth-input">
                @error('password')
                    <p class="auth-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="auth-label">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="auth-input">
            </div>

            <div>
                <button type="submit" class="auth-button">Daftar Akun</button>
            </div>
        </form>

        <div class="auth-note">
            Setelah mendaftar, Anda akan langsung masuk dan diarahkan ke dashboard.
        </div>

        <div class="auth-footer">
            <p>Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
        </div>
    </div>
</div>

<script src="{{ asset('js/theme.js') }}"></script>
@endsection