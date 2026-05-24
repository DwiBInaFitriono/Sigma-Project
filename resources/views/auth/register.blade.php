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

            <div style="margin-top: 1rem;">
                <button type="submit" class="auth-button">Daftar Akun</button>
            </div>
        </form>

        <div class="auth-note">
            Setelah mendaftar, Anda akan langsung masuk dan diarahkan ke dashboard.
        </div>

        <div class="auth-footer">
            <p>Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
        </div>

        <div class="auth-copyright" style="margin-top: 1.5rem; text-align: center; font-size: 0.75rem; color: var(--sigma-muted); font-weight: 600;">
            &copy; 2025 Kelompok 2 TKK B. All Rights Reserved.
        </div>
    </div>
</div>

<script src="{{ asset('js/theme.js') }}"></script>
@endsection