(() => {
    'use strict';

    // Per browser tab and signed-in user: independent searches must not overwrite
    // each other, and one user's filters must not carry over to another user.
    const user = document.currentScript?.dataset.user;
    if (!user) return;
    const storageKey = `win_pos_list_filters_v1:${user}`;
    const listPaths = new Set([
        '/supplier_prices/index.php', '/factory_products/index.php',
        '/customers/index.php', '/products/index.php', '/invoices/index.php',
        '/orders/index.php', '/payments/index.php', '/expenses/index.php',
        '/labour/records/index.php', '/labour/summary/index.php',
        '/inventory/returned.php', '/inventory/history.php', '/audit/index.php',
        '/reports/sales.php', '/reports/customers.php', '/reports/payments.php',
        '/reports/profit.php',
    ]);
    const originalLinks = new WeakMap();

    function loadFilters() {
        try {
            const saved = JSON.parse(sessionStorage.getItem(storageKey) || '{}');
            return saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {};
        } catch (_) {
            return {};
        }
    }

    function syncLinks() {
        const current = new URL(window.location.href);
        const saved = loadFilters();
        if (listPaths.has(current.pathname)) {
            // Keep only submitted filter fields and paging, never messages,
            // record IDs, export actions, or other incidental query parameters.
            const fields = new Set(['page', 'per_page']);
            for (const form of document.forms) {
                if (form.method.toLowerCase() !== 'get') continue;
                const action = new URL(form.action || current.href, current.href);
                if (action.origin !== current.origin || action.pathname !== current.pathname) continue;
                for (const control of form.elements) {
                    if (control.name && !/csrf|token|password/i.test(control.name)) fields.add(control.name);
                }
            }
            const query = new URLSearchParams();
            for (const [key, value] of current.searchParams) {
                if (fields.has(key)) query.append(key, value);
            }
            if (query.size) saved[current.pathname] = query.toString();
            else delete saved[current.pathname];
            try { sessionStorage.setItem(storageKey, JSON.stringify(saved)); }
            catch (_) { /* Navigation still works when browser storage is unavailable. */ }
        }

        for (const link of document.querySelectorAll('a[href]')) {
            if (!originalLinks.has(link)) originalLinks.set(link, link.getAttribute('href'));
            const original = originalLinks.get(link);
            if (!original || original.startsWith('#')) continue;
            let target;
            try { target = new URL(original, current.href); }
            catch (_) { continue; }
            // Restore only a plain link back to a known list. Explicit filters,
            // actions, downloads, and Clear/Reset links on that list stay intact.
            if (target.origin !== current.origin || !listPaths.has(target.pathname)
                || target.pathname === current.pathname || target.search
                || link.hasAttribute('download') || link.hasAttribute('data-reset-filters')) continue;
            const query = saved[target.pathname];
            if (typeof query === 'string' && query.length <= 16000 && query !== '') {
                target.search = query;
                link.setAttribute('href', target.pathname + target.search + target.hash);
            } else {
                link.setAttribute('href', original);
            }
        }
    }

    syncLinks();
    // Back/forward cache restores an existing document without re-running scripts.
    window.addEventListener('pageshow', syncLinks);
})();
