document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const openButton = document.getElementById('mobile-sidebar-toggle');
    const closeButton = document.getElementById('sidebar-close-toggle');

    function openSidebar() {
        if (sidebar) {
            sidebar.classList.add('is-open');
            sidebar.classList.add('mobile-visible');
        }

        if (overlay) {
            overlay.classList.add('is-visible');
        }
    }

    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('is-open');
            sidebar.classList.remove('mobile-visible');
        }

        if (overlay) {
            overlay.classList.remove('is-visible');
        }
    }

    if (openButton) {
        openButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (sidebar) {
                sidebar.classList.toggle('is-open');
                sidebar.classList.toggle('mobile-visible');
            }
            
            if (overlay) {
                overlay.classList.toggle('is-visible');
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.querySelectorAll('.sidebar-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 1024px)').matches) {
                closeSidebar();
            }
        });
    });
});