<aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-header">
            <div class="sidebar-brand-wrap">
                <h2 class="sidebar-brand">SIGMA</h2>
                <p class="sidebar-subtitle">Monitoring Getaran</p>
            </div>
            <button id="sidebar-close-toggle" class="sidebar-close-toggle" type="button" aria-label="Tutup sidebar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="sidebar-dropdown">
                <button class="sidebar-dropdown-toggle" type="button" onclick="this.parentElement.classList.toggle('active')">
                    <div class="sidebar-dropdown-title">
                        <i class="fa-solid fa-microchip"></i>
                        <span>Data Sensor</span>
                    </div>
                    <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </button>
                <div class="sidebar-dropdown-menu-wrapper">
                    <div class="sidebar-dropdown-menu">
                        <a href="{{ route('accelerometer') }}" class="sidebar-link {{ request()->routeIs('accelerometer') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>Accelerometer</span>
                        </a>
                        <a href="{{ route('gps') }}" class="sidebar-link {{ request()->routeIs('gps') ? 'active' : '' }}">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>GPS</span>
                        </a>
                    </div>
                </div>
            </div>

            <a href="{{ route('history') }}" class="sidebar-link {{ request()->routeIs('history') ? 'active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>History</span>
            </a>

            <a href="{{ route('controller') }}" class="sidebar-link {{ request()->routeIs('controller') ? 'active' : '' }}">
                <i class="fa-solid fa-sliders"></i>
                <span>Kontroller</span>
            </a>

            @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Manajemen Pengguna</span>
                </a>
            @endif
        </nav>


    </div>
</aside>
