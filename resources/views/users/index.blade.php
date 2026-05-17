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
        <!-- Form Tambah User -->
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    Simpan Pengguna
                </button>
            </form>
        </section>

        <!-- Daftar User -->
        <section class="glow-card panel-card log-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Daftar Pengguna</h2>
                    <p class="section-subtitle">Seluruh pengguna yang terdaftar di sistem.</p>
                </div>
                <div class="live-badge" style="background: var(--sigma-bg-alt); color: var(--sigma-text);">TOTAL: {{ $users->count() }} USER</div>
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
                                <td style="font-weight: 600; color: white;">{{ $user->name }}</td>
                                <td class="text-muted">{{ $user->email }}</td>
                                <td>
                                    <span class="badge {{ $user->isAdmin() ? 'badge-admin' : 'badge-user' }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-right">
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-delete" title="Hapus Pengguna">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted" style="font-size: 0.85rem; font-style: italic;">Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted" style="text-align: center; padding: 2rem;">Belum ada pengguna.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
