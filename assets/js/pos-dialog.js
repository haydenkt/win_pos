/* Shared asynchronous dialogs. Never use a Promise as a synchronous confirm result. */
(() => {
    if (window.posDialog) return;
    let queue = Promise.resolve();
    function show(message, confirm = false) {
        const result = queue.then(() => new Promise(resolve => {
            const previous = document.activeElement;
            const dialog = document.createElement('dialog');
            dialog.className = 'pos-dialog';
            dialog.setAttribute('aria-labelledby', 'pos-dialog-title');
            dialog.setAttribute('aria-describedby', 'pos-dialog-message');
            const dark = document.documentElement.dataset.theme === 'dark' ||
                (!document.documentElement.dataset.theme && matchMedia('(prefers-color-scheme: dark)').matches);
            dialog.style.cssText = `box-sizing:border-box;width:min(440px,calc(100% - 32px));max-height:calc(100dvh - 32px);overflow:auto;margin:auto;padding:28px;border:1px solid ${dark?'#475569':'#dbe3ef'};border-radius:20px;background:${dark?'#172338':'#fff'};color:${dark?'#f1f5f9':'#172b4d'};box-shadow:0 24px 80px #0006;font:16px/1.6 system-ui,sans-serif;`;
            const title = document.createElement('h2');
            title.id = 'pos-dialog-title'; title.textContent = confirm ? 'Please confirm' : 'Win POS';
            title.style.cssText = 'margin:0 0 12px;font-size:22px;color:inherit';
            const text = document.createElement('p');
            text.id = 'pos-dialog-message'; text.textContent = String(message);
            text.style.cssText = 'white-space:pre-wrap;overflow-wrap:anywhere;margin:0 0 24px;color:inherit';
            const actions = document.createElement('div');
            actions.style.cssText = 'display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap';
            let answer = false;
            function button(label, value) {
                const b = document.createElement('button'); b.type = 'button'; b.textContent = label;
                b.style.cssText = `min-height:44px;padding:10px 22px;border-radius:10px;border:1px solid ${value?'#2563eb':'#94a3b8'};background:${value?'#2563eb':'transparent'};color:${value?'#fff':'inherit'};font:inherit;font-weight:600;cursor:pointer`;
                b.onclick = () => { answer = value; dialog.close(); };
                actions.append(b); return b;
            }
            const cancel = confirm ? button('Cancel', false) : null;
            const ok = button(confirm ? 'Confirm' : 'OK', true);
            (cancel || ok).autofocus = true;
            dialog.append(title, text, actions); document.body.append(dialog);
            dialog.addEventListener('close', () => {
                dialog.remove(); if (previous?.isConnected) previous.focus(); resolve(answer);
            }, {once:true});
            dialog.showModal();
        }));
        queue = result.catch(() => {}); return result;
    }
    window.posDialog = {alert: message => show(message), confirm: message => show(message, true)};
    const style = document.createElement('style');
    style.textContent = '.pos-dialog::backdrop{background:rgba(7,15,30,.65);backdrop-filter:blur(3px)}.pos-dialog button:focus-visible{outline:3px solid #60a5fa;outline-offset:3px}';
    document.head.append(style);
    const approved = new WeakSet(), pending = new WeakSet();
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (approved.has(form)) { approved.delete(form); return; }
        if (!form.dataset.confirm) return;
        event.preventDefault(); event.stopImmediatePropagation();
        if (pending.has(form)) return;
        pending.add(form);
        const submitter = event.submitter;
        try {
            if (await window.posDialog.confirm(form.dataset.confirm)) {
                approved.add(form);
                try { form.requestSubmit(submitter || undefined); } finally { approved.delete(form); }
            }
        } finally { pending.delete(form); }
    }, true);
    document.addEventListener('click', async event => {
        const link = event.target.closest('a[data-confirm]');
        if (!link) return;
        event.preventDefault(); event.stopImmediatePropagation();
        if (pending.has(link)) return;
        pending.add(link);
        try { if (await window.posDialog.confirm(link.dataset.confirm)) window.location.assign(link.href); }
        finally { pending.delete(link); }
    }, true);
})();
