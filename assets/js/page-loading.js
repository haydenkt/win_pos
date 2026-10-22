(() => {
    const message = document.getElementById('loadingMessage');
    const hint = document.getElementById('loadingHint');
    const retry = document.getElementById('loadingRetry');
    let busy = false;
    async function load() {
        if (busy) return;
        busy = true;
        retry.hidden = true;
        document.getElementById('loadingSpinner').hidden = false;
        document.getElementById('loadingSkeleton').hidden = false;
        document.getElementById('loadingMain').setAttribute('aria-busy', 'true');
        message.textContent = 'Loading your data…';
        hint.textContent = 'Please wait while Win POS connects.';
        const controller = new AbortController();
        const slow = setTimeout(() => { hint.textContent = 'The connection is taking longer than usual. Still waiting for your data…'; }, 8000);
        const timeout = setTimeout(() => controller.abort(), 60000);
        try {
            const response = await fetch(location.href, {
                headers: {'X-WinPOS-Content': '1'}, credentials: 'same-origin',
                cache: 'no-store', signal: controller.signal
            });
            if (response.redirected) {
                const target = new URL(response.url);
                if (target.origin !== location.origin) throw new Error('Unexpected redirect');
                location.replace(target.href);
                return;
            }
            if (!response.ok) throw new Error(response.status === 403 ? 'permission' : 'server');
            const html = await response.text();
            if (!/<\/html\s*>/i.test(html) || !/id=["']sidebar["']/i.test(html)) throw new Error('Incomplete page');
            clearTimeout(slow); clearTimeout(timeout);
            // Render the same-origin PHP document in its original URL context.
            // This executes its normal scripts once and preserves native forms,
            // dialogs, filter memory, navigation and per-page behaviour.
            document.open(); document.write(html); document.close();
        } catch (error) {
            document.getElementById('loadingMain').setAttribute('aria-busy', 'false');
            document.getElementById('loadingSpinner').hidden = true;
            document.getElementById('loadingSkeleton').hidden = true;
            message.textContent = error.message === 'permission' ? 'You don’t have permission to open this page.' : 'Could not load this page.';
            hint.textContent = 'Check your connection and try again. No data has been changed.';
            retry.hidden = false;
        } finally {
            clearTimeout(slow); clearTimeout(timeout); busy = false;
        }
    }
    retry.addEventListener('click', load);
    load();
})();
