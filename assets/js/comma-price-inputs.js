(() => {
    const selector = '.js-comma-price';

    const numericValue = (value) => Number.parseFloat(
        String(value ?? '').replaceAll(',', '')
    ) || 0;

    const formatValue = (value) => {
        const raw = String(value ?? '')
            .replaceAll(',', '')
            .replace(/[^0-9.]/g, '');

        if (raw === '') return '';

        const hasDecimal = raw.includes('.');
        const parts = raw.split('.');
        const integer = (parts.shift() || '0').replace(/^0+(?=\d)/, '');
        const decimal = parts.join('');
        const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return hasDecimal ? `${formattedInteger}.${decimal}` : formattedInteger;
    };

    const formatInput = (input) => {
        if (!input) return;

        const oldValue = input.value;
        const oldCursor = input.selectionStart ?? oldValue.length;
        const charactersBeforeCursor = oldValue.slice(0, oldCursor).replaceAll(',', '').length;
        const formatted = formatValue(oldValue);
        input.value = formatted;

        const step = Number(input.dataset.priceStep || 50);
        const invalidStep = formatted !== ''
            && step > 0
            && Math.abs(numericValue(formatted) % step) > 1e-7;
        input.setCustomValidity(invalidStep ? `Price must be in steps of ${step}.` : '');

        if (document.activeElement === input) {
            let cursor = 0;
            let characters = 0;
            while (cursor < formatted.length && characters < charactersBeforeCursor) {
                if (formatted[cursor] !== ',') characters++;
                cursor++;
            }
            input.setSelectionRange(cursor, cursor);
        }
    };

    const bindInput = (input) => {
        if (input.dataset.commaPriceBound === 'true') return;
        input.dataset.commaPriceBound = 'true';
        input.type = 'text';
        input.inputMode = 'decimal';
        input.addEventListener('input', () => formatInput(input));
        input.addEventListener('focus', () => input.select());
        formatInput(input);
    };

    const bindWithin = (root) => {
        if (root.matches?.(selector)) bindInput(root);
        root.querySelectorAll?.(selector).forEach(bindInput);
    };

    bindWithin(document);
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) bindWithin(node);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });

    document.addEventListener('submit', (event) => {
        const inputs = [...event.target.querySelectorAll(selector)];
        inputs.forEach((input) => {
            input.value = input.value.replaceAll(',', '');
        });
        setTimeout(() => {
            if (event.defaultPrevented) inputs.forEach(formatInput);
        }, 0);
    }, true);

    window.commaPriceNumber = numericValue;
    window.formatCommaPriceInput = formatInput;
})();
