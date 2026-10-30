(() => {
    const style = document.createElement('style');
    style.textContent = '[data-theme="dark"] .customer-results button {background:#182235;color:#f1f5f9;border-color:#475569} .customer-results button:focus,.customer-results button:hover {outline:2px solid #3b82f6;outline-offset:-2px}';
    document.head.append(style);
    document.querySelectorAll('select[data-customer-search]').forEach(select => {
        // Keep all original options for held drafts, editing and form submission.
        const box = document.createElement('div');
        box.className = 'mb-2';
        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-control';
        search.placeholder = 'Search customer by name or phone…';
        search.setAttribute('aria-label', 'Search customer by name or phone');
        search.autocomplete = 'off';
        const results = document.createElement('div');
        results.className = 'list-group mt-1 customer-results';
        results.style.maxHeight = '240px';
        results.style.overflowY = 'auto';
        results.hidden = true;
        const status = document.createElement('small');
        status.setAttribute('role', 'status');
        box.append(search, results, status);
        select.before(box);
        const normalize = value => value.normalize('NFKC').toLocaleLowerCase().trim();
        const clear = () => {
            search.value = '';
            results.replaceChildren();
            results.hidden = true;
            status.textContent = '';
        };
        search.addEventListener('input', () => {
            const query = normalize(search.value);
            results.replaceChildren();
            if (!query) { clear(); return; }
            const terms = query.split(/\s+/);
            const matches = Array.from(select.options).filter(option => option.value && !option.disabled
                && terms.every(term => normalize(option.textContent).includes(term)));
            results.hidden = matches.length === 0;
            status.textContent = matches.length ? `${matches.length} customers found${matches.length > 30 ? ' — showing first 30; type more to narrow down' : ''}` : 'No matching customers.';
            matches.slice(0, 30).forEach(option => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action text-start';
                button.textContent = option.textContent.trim().replace(/\s+/g, ' ');
                button.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', {bubbles: true}));
                    clear();
                    select.focus();
                });
                results.append(button);
            });
        });
        search.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === 'ArrowDown') {
                event.preventDefault();
                results.querySelector('button')?.focus();
            } else if (event.key === 'Escape') { clear(); }
        });
        results.addEventListener('keydown', event => {
            if (event.key === 'Escape') { clear(); search.focus(); }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const next = event.key === 'ArrowDown' ? event.target.nextElementSibling : event.target.previousElementSibling;
                (next || search).focus();
            }
        });
        select.addEventListener('change', clear);
        select.form?.addEventListener('reset', clear);
    });
})();
