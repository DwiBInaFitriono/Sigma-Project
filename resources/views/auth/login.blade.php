@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
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
            <h1 class="auth-title">SIGMA</h1>
            <p class="auth-subtitle">Sistem Informasi Gempa, Monitoring & Alert</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div>
                <label for="email" class="auth-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="auth-input">
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

            <div class="flex items-center justify-between">
                <label for="remember" class="auth-checkbox-label">
                    <input id="remember" name="remember" type="checkbox" class="auth-checkbox">
                    Ingat saya
                </label>
            </div>

            <div>
                <button type="submit" class="auth-button">Masuk</button>
            </div>

            <div class="auth-footer">
                <p>Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a></p>
            </div>
        </form>

        <div class="auth-note">
            Sistem monitoring gempa bumi untuk kesiapsiagaan dan respons cepat.
        </div>
    </div>
</div>

<script src="{{ asset('js/theme.js') }}"></script>
@endsection