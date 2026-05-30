@extends('layouts.app')

@section('content')
<div class="panel-layout">
    <header class="mobile-top-nav">
        <div class="flex-align-center-gap-1">
            <button class="btn-toggle-sidebar" id="mobile-sidebar-toggle" type="button" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="{{ route('dashboard') }}" class="mobile-logo logo-link-reset">SIGMA</a>
        </div>
        <div class="mobile-top-actions">
            <button id="theme-toggle" class="btn-toggle-sidebar" title="Toggle Dark Mode">
                <i class="fa-solid fa-moon"></i>
            </button>
            <div class="profile-dropdown-container mobile-user-container" id="mobile-profile-dropdown">
                <div class="topbar-profile cursor-pointer" onclick="document.getElementById('mobile-profile-dropdown').classList.toggle('is-open')">
                    <div class="topbar-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="topbar-user-name">{{ auth()->user()?->name ?? auth()->user()?->email ?? 'User' }}</span>
                    <i class="fa-solid fa-chevron-down profile-dropdown-arrow"></i>
                </div>
                <div class="profile-dropdown-menu">
                    <a href="{{ route('profile.edit') }}" class="profile-dropdown-item">
                        <i class="fa-solid fa-user-pen"></i> Manage Profile
                    </a>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="display-inline">
                @csrf
                <button type="submit" class="btn-mobile-logout" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </header>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    @include('components.sidebar')

    <main class="panel-content">
        @include('layouts.header')
        @yield('dashboard-content')
        <footer class="dashboard-footer">
            <p>&copy; 2025 Kelompok 2 TKK B. All Rights Reserved.</p>
        </footer>
    </main>


</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDesktop = document.getElementById('theme-toggle-desktop');
        const htmlElement = document.documentElement;
        
        // Initial check
        if (localStorage.getItem('theme') === 'dark') {
            htmlElement.classList.add('dark-mode');
        }

        const toggleTheme = () => {
            htmlElement.classList.toggle('dark-mode');
            if (htmlElement.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        };

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', toggleTheme);
        }
        if (themeToggleDesktop) {
            themeToggleDesktop.addEventListener('click', toggleTheme);
        }

        // Close profile dropdowns when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.profile-dropdown-container')) {
                document.querySelectorAll('.profile-dropdown-container').forEach(container => {
                    container.classList.remove('is-open');
                });
            }
        });
    });
</script>
<script src="{{ asset('js/sidebar.js') }}"></script>
@endsection
