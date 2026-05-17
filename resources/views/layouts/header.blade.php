<div class="content-topbar">
    <div class="topbar-left"></div>
    <div class="topbar-right">
        <button id="theme-toggle-desktop" class="topbar-icon-btn" type="button" title="Toggle Dark Mode">
            <i class="fa-solid fa-moon"></i>
        </button>
        <div class="topbar-profile">
            <div class="topbar-avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <span class="topbar-user-name">{{ auth()->user()?->name ?? auth()->user()?->email ?? 'User' }}</span>
        </div>
        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="topbar-icon-btn topbar-logout-btn" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </form>
    </div>
</div>
