@extends('layouts.app')

@section('content')
<div class="panel-layout">
    <header class="mobile-top-nav">
        <div class="mobile-logo">SIGMA</div>
        <button class="btn-toggle-sidebar" id="mobile-sidebar-toggle" type="button" aria-label="Toggle sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="mobile-top-actions">
            <button id="theme-toggle" class="btn-toggle-sidebar" title="Toggle Dark Mode">
                <i class="fa-solid fa-moon"></i>
            </button>
            <span class="mobile-user">{{ auth()->user()?->name ?? auth()->user()?->email ?? 'User' }}</span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
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
    });
</script>
@endsection
