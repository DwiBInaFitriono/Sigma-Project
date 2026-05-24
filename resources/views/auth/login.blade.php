@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
<div class="auth-page">
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