@extends('layouts.dashboard')

@section('title', 'Manajemen Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/users.css') }}">
@endpush

@section('dashboard-content')
<div class="panel-page">
    <header class="content-header">
        <div class="content-header-flex">
            <div>
                <p class="content-subtitle">ADMINISTRATOR</p>
                <h1 class="content-title">Manajemen Pengguna</h1>
                <p class="content-desc">Kelola hak akses pengguna untuk sistem pemantauan SIGMA.</p>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <div class="users-grid">
        <section class="glow-card panel-card log-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Tambah Pengguna</h2>
                    <p class="section-subtitle">Buat akun baru untuk mengakses dashboard.</p>
                </div>
            </div>

            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required autofocus autocomplete="name">
                    @error('name')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autocomplete="email">
                    @error('email')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="role">Hak Akses (Role)</label>
                    <select id="role" name="role" class="form-control form-select" required>
                        <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User (Hanya Lihat)</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin (Akses Penuh)</option>
                    </select>
                    @error('role')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                    @error('password')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Kata Sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn-submit">
                    Simpan Pengguna
                </button>
            </form>
        </section>

        <section class="glow-card panel-card log-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Daftar Pengguna</h2>
                    <p class="section-subtitle">Seluruh pengguna yang terdaftar di sistem.</p>
                </div>
                <div class="live-badge badge-alt">TOTAL: {{ $users->count() }} USER</div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Bergabung Sejak</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="font-semibold-text">{{ $user->name }}</td>
                                <td class="text-muted">{{ $user->email }}</td>
                                <td>
                                    <span class="badge {{ $user->isAdmin() ? 'badge-admin' : 'badge-user' }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-right">
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('users.updateRole', $user) }}" method="POST" class="display-inline-block mr-0-25">
                                            @csrf
                                            @method('PATCH')
                                            @if($user->isAdmin())
                                                <input type="hidden" name="role" value="user">
                                                <button type="submit" class="btn-role" title="Jadikan User Biasa" onclick="return confirm('Turunkan role pengguna ini menjadi User?');">
                                                    Jadikan User
                                                </button>
                                            @else
                                                <input type="hidden" name="role" value="admin">
                                                <button type="submit" class="btn-role" title="Jadikan Admin" onclick="return confirm('Jadikan pengguna ini sebagai Admin?');">
                                                    Jadikan Admin
                                                </button>
                                            @endif
                                        </form>
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');" class="display-inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-delete" title="Hapus Pengguna">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted text-italic-sm">Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted table-empty-row">Belum ada pengguna.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
