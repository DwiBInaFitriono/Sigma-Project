// Theme Toggle Management
(function () {
    const THEME_KEY = 'theme';
    const DARK_CLASS = 'dark-mode';

    // Get saved theme or system preference
    function getSavedTheme() {
        const saved = localStorage.getItem(THEME_KEY);
        if (saved) return saved;

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Apply theme to document
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add(DARK_CLASS);
        } else {
            document.documentElement.classList.remove(DARK_CLASS);
        }
        localStorage.setItem(THEME_KEY, theme);
        updateThemeToggle(theme);
    }

    // Update toggle button appearance
    function updateThemeToggle(theme) {
        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (!toggleBtn) return;

        if (theme === 'dark') {
            toggleBtn.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>';
            toggleBtn.title = 'Switch to Light Mode';
        } else {
            toggleBtn.innerHTML = '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.536l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.828-2.828a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm.707-7.071a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM9 4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1zm0 14a1 1 0 01-1-1v-1a1 1 0 112 0v1a1 1 0 01-1 1zm8-1a1 1 0 111 0 4 4 0 01-4 4 1 1 0 110-2 2 2 0 002-2zM3 15a1 1 0 11-2 0 4 4 0 014-4 1 1 0 110 2 2 2 0 00-2 2z" clip-rule="evenodd"></path></svg>';
            toggleBtn.title = 'Switch to Dark Mode';
        }
    }

    // Toggle theme
    function toggleTheme() {
        const currentTheme = getSavedTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    }

    // Initialize theme on page load
    document.addEventListener('DOMContentLoaded', function () {
        const theme = getSavedTheme();
        applyTheme(theme);

        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    });

    // Expose toggle function globally
    window.toggleTheme = toggleTheme;
})();
