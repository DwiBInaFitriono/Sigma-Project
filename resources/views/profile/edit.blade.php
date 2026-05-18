@extends('layouts.dashboard')

@section('title', 'Manage Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('dashboard-content')
<div class="panel-page">
    <header class="content-header">
        <div class="content-header-flex">
            <div>
                <p class="content-subtitle">PENGATURAN AKUN</p>
                <h1 class="content-title">Manage Profile</h1>
                <p class="content-desc">Perbarui informasi profil dan kata sandi Anda.</p>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="alert alert-success" style="margin-bottom: 2rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="users-grid">
        <section class="glow-card panel-card log-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Informasi Profil</h2>
                    <p class="section-subtitle">Ubah detail akun Anda di bawah ini.</p>
                </div>
            </div>

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required autocomplete="name">
                    @error('name')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="email">
                    @error('email')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="section-header" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--sigma-border);">
                    <div>
                        <h2 class="section-title">Keamanan</h2>
                        <p class="section-subtitle">Kosongkan jika Anda tidak ingin mengubah kata sandi.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi Baru (Opsional)</label>
                    <input type="password" id="password" name="password" class="form-control" autocomplete="new-password">
                    @error('password')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-submit" style="margin-top: 1.5rem;">
                    Simpan Perubahan
                </button>
            </form>
        </section>
    </div>
</div>
@endsection
