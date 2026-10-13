<?php
// One-time notice, set only after the save transaction has committed.
$saveNotice = $_SESSION['save_notice'] ?? '';
unset($_SESSION['save_notice']);
if (is_string($saveNotice) && $saveNotice !== ''):
?>
<style>
    .pos-save-notice {
        width: min(420px, calc(100% - 32px));
        max-height: calc(100dvh - 32px);
        overflow: auto;
        margin: auto;
        padding: 32px 24px 24px;
        border: 1px solid var(--ink-200);
        border-radius: 20px;
        background: var(--surface, #fff);
        color: var(--ink-900, #101b2d);
        box-shadow: 0 24px 80px rgba(0, 0, 0, .28);
        text-align: center;
    }
    .pos-save-notice::backdrop { background: rgba(7, 15, 30, .6); }
    .pos-save-notice__icon {
        display: grid;
        place-items: center;
        width: 64px;
        height: 64px;
        margin: 0 auto 18px;
        border-radius: 50%;
        color: #059669;
        background: rgba(16, 185, 129, .14);
        font-size: 30px;
    }
    .pos-save-notice h2 { font-size: 1.4rem; margin-bottom: 10px; }
    .pos-save-notice p { overflow-wrap: anywhere; margin-bottom: 24px; }
    .pos-save-notice .btn { min-width: 140px; }
    [data-theme="dark"] .pos-save-notice .btn-primary { color: #0b1220; }
    [data-theme="dark"] .pos-save-notice__icon { color: #34d399; }
</style>
<dialog open class="pos-save-notice" id="posSaveNotice" aria-labelledby="posSaveNoticeTitle" aria-describedby="posSaveNoticeMessage">
    <div class="pos-save-notice__icon" aria-hidden="true"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"/></svg></div>
    <h2 id="posSaveNoticeTitle">Saved successfully</h2>
    <p id="posSaveNoticeMessage"><?=htmlspecialchars($saveNotice, ENT_QUOTES, 'UTF-8');?></p>
    <form method="dialog"><button type="submit" class="btn btn-primary" autofocus>Done</button></form>
</dialog>
<script>
(() => {
    const notice = document.getElementById('posSaveNotice');
    if (typeof notice.showModal === 'function') {
        notice.removeAttribute('open');
        notice.showModal();
    }
})();
</script>
<?php endif; ?>
