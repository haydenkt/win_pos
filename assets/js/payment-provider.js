(() => {
    const options = {'Bank Transfer':['KBZ','AYA','UAB'], 'Mobile Payment':['Kpay','AYA MMQR']};
    function setup(method) {
        if (method.dataset.providerBound) return;
        method.dataset.providerBound = '1';
        const digital = method.name === 'digital_method';
        const wrapper = document.createElement('div'); wrapper.className = 'mt-2';
        const label = document.createElement('label'); label.className = 'form-label';
        const select = document.createElement('select'); select.className = 'form-select';
        select.name = digital ? 'digital_provider' : 'payment_provider';
        select.id = (method.id || method.name) + '_provider'; label.htmlFor = select.id;
        wrapper.append(label, select); method.after(wrapper);
        const amount = method.form?.querySelector(digital ? '[name="digital_payment"]' : '[name="payment_amount"]');
        let previousMethod = method.value;
        function sync(saved) {
            const choices = options[method.value] || [];
            const value = typeof saved === 'string' ? saved : (previousMethod === method.value ? select.value : '');
            const legacy = method.dataset.legacyMethod === method.value && !method.dataset.provider;
            select.replaceChildren(new Option(legacy ? 'Not recorded (existing payment)' : 'Select bank / service', ''));
            choices.forEach(v => select.add(new Option(v, v)));
            select.value = choices.includes(value) ? value : '';
            const needed = !amount || Number(amount.value.replaceAll(',', '')) > 0;
            select.disabled = !choices.length || !needed;
            select.required = choices.length > 0 && needed && !legacy;
            wrapper.hidden = !choices.length;
            label.textContent = method.value === 'Bank Transfer' ? 'Bank' : 'Mobile service';
            previousMethod = method.value;
        }
        method.addEventListener('change', () => sync());
        amount?.addEventListener('input', () => sync());
        method.syncProvider = sync;
        sync(method.dataset.provider || '');
    }
    function init() { document.querySelectorAll('select[name="payment_method"],select[name="digital_method"]').forEach(setup); }
    window.restorePaymentProvider = (methodId, value) => { init(); document.getElementById(methodId)?.syncProvider?.(value || ''); };
    init();
})();
