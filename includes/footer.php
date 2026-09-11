    </div>
</main>

<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('menuBtn');
    const closeButton = document.getElementById('sidebarCloseBtn');
    const collapseButton = document.getElementById('sidebarCollapseBtn');
    const backdrop = document.getElementById('sidebarBackdrop');

    const setMobileOpen = (open) => {
        if (!sidebar || !menuButton || !backdrop) return;

        sidebar.classList.toggle('show', open);
        backdrop.classList.toggle('show', open);
        menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.classList.toggle('sidebar-mobile-open', open);
    };

    const syncCollapseIcon = () => {
        if (!collapseButton) return;

        const icon = collapseButton.querySelector('i');
        const collapsed = body.classList.contains('sidebar-collapsed');

        icon.classList.toggle('fa-chevron-left', !collapsed);
        icon.classList.toggle('fa-chevron-right', collapsed);
        collapseButton.title = collapsed
            ? 'Expand navigation'
            : 'Collapse navigation';
    };

    if (localStorage.getItem('winposSidebar') === 'collapsed') {
        body.classList.add('sidebar-collapsed');
    }

    document.documentElement.classList.remove('sidebar-pre-collapsed');
    syncCollapseIcon();

    menuButton?.addEventListener('click', () => setMobileOpen(true));
    closeButton?.addEventListener('click', () => setMobileOpen(false));
    backdrop?.addEventListener('click', () => setMobileOpen(false));

    collapseButton?.addEventListener('click', () => {
        const collapsed = body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('winposSidebar', collapsed ? 'collapsed' : 'expanded');
        syncCollapseIcon();
    });

    sidebar?.addEventListener('click', (event) => {
        if (window.innerWidth <= 900 && event.target.closest('a')) {
            setMobileOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMobileOpen(false);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) setMobileOpen(false);
    });
})();

(() => {
    const root = document.documentElement;
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

    const applyTheme = () => {
        const mode = root.dataset.themeMode || 'system';

        if (mode === 'system') {
            root.dataset.theme = systemTheme.matches ? 'dark' : 'light';
        }

        const themeColor = document.querySelector('meta[name="theme-color"]');
        if (themeColor) {
            themeColor.content = root.dataset.theme === 'dark' ? '#0b1220' : '#f7f9fc';
        }
    };

    if (systemTheme.addEventListener) {
        systemTheme.addEventListener('change', applyTheme);
    } else {
        systemTheme.addListener(applyTheme);
    }

    applyTheme();
})();
</script>
</body>
</html>
