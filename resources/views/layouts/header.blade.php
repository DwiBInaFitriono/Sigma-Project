<div class="content-topbar">
    <div class="topbar-left"></div>
    <div class="topbar-right">
        <button id="theme-toggle-desktop" class="topbar-icon-btn" type="button" title="Toggle Dark Mode">
            <i class="fa-solid fa-moon"></i>
        </button>
        <div class="profile-dropdown-container" id="desktop-profile-dropdown">
            <div class="topbar-profile cursor-pointer" onclick="document.getElementById('desktop-profile-dropdown').classList.toggle('is-open')">
                <div class="topbar-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <span class="topbar-user-name">{{ auth()->user()?->name ?? auth()->user()?->email ?? 'User' }}</span>
            </div>
            <div class="profile-dropdown-menu">
                <a href="{{ route('profile.edit') }}" class="profile-dropdown-item">
                    <i class="fa-solid fa-user-pen"></i> Manage Profile
                </a>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="display-inline">
            @csrf
            <button type="submit" class="topbar-icon-btn topbar-logout-btn" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </form>
    </div>
</div>
