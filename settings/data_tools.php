<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/data_tools_support.php';
requirePermission('backup_manage');

if (empty($_SESSION['data_tools_csrf'])) {
    $_SESSION['data_tools_csrf'] = bin2hex(random_bytes(32));
}

$isAdmin = winPosIsAdmin();
$demoPreview = winPosDemoPreview($conn);
$demoMainCount = array_sum(array_intersect_key($demoPreview, array_flip([
    'customers', 'invoices', 'orders', 'returns', 'quotations', 'site_surveys',
    'expenses', 'workers', 'labour_records', 'labour_transactions'
])));
$page_title = 'Backup & reset';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-hero">
    <div><div class="page-kicker">System</div><h1 class="page-title">Backup & demo reset</h1><p class="page-subtitle">Protect business records, restore a saved copy, or clear only marked training data.</p></div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']);?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['last_safety_backup'])): ?>
    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="fa fa-shield-halved"></i> An automatic safety backup was created before the last operation.</span>
        <a class="btn btn-sm btn-outline-primary" href="download_safety_backup.php?file=<?=urlencode($_SESSION['last_safety_backup']);?>"><i class="fa fa-download"></i> Download safety backup</a>
    </div>
<?php endif; ?>
<?php if (!$isAdmin): ?>
    <div class="alert alert-warning"><i class="fa fa-lock"></i> Restore and Clear Demo are restricted to an administrator.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body d-flex flex-column">
            <span class="theme-option-icon"><i class="fa fa-download"></i></span>
            <h2 class="h5">Download backup</h2>
            <p class="text-muted flex-grow-1">Save all business records as one V2 JSON backup file.</p>
            <a href="download_backup.php" class="btn btn-primary"><i class="fa fa-download"></i> Download now</a>
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body d-flex flex-column">
            <span class="theme-option-icon"><i class="fa fa-upload"></i></span>
            <h2 class="h5">Restore backup</h2>
            <p class="text-muted">Replace current business records with a validated V2 backup. A safety backup is created first.</p>
            <form action="restore_backup.php" method="post" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['data_tools_csrf']);?>">
                <div class="mb-3"><label class="form-label" for="backupFile">Backup file</label><input class="form-control" id="backupFile" name="backup_file" type="file" accept="application/json,.json" required <?=$isAdmin ? '' : 'disabled';?>></div>
                <div class="mb-3"><label class="form-label" for="restoreConfirmation">Type <strong>RESTORE BACKUP</strong></label><input class="form-control" id="restoreConfirmation" name="confirmation" autocomplete="off" required <?=$isAdmin ? '' : 'disabled';?>></div>
                <button class="btn btn-warning w-100 mt-auto" <?=$isAdmin ? '' : 'disabled';?>><i class="fa fa-rotate-left"></i> Restore backup</button>
            </form>
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100 border-danger"><div class="card-body d-flex flex-column">
            <span class="theme-option-icon text-danger"><i class="fa fa-eraser"></i></span>
            <h2 class="h5">Clear demo data</h2>
            <p class="text-muted">Delete only records marked <code>DEMO-SEED</code> or customers/workers beginning with <code>Demo -</code>. Ordinary records are preserved.</p>
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex justify-content-between"><span>Customers</span><strong><?=number_format($demoPreview['customers'] ?? 0);?></strong></div>
                <div class="d-flex justify-content-between"><span>Invoices</span><strong><?=number_format($demoPreview['invoices'] ?? 0);?></strong></div>
                <div class="d-flex justify-content-between"><span>Orders</span><strong><?=number_format($demoPreview['orders'] ?? 0);?></strong></div>
                <div class="d-flex justify-content-between"><span>Returns</span><strong><?=number_format($demoPreview['returns'] ?? 0);?></strong></div>
                <div class="d-flex justify-content-between"><span>Quotations</span><strong><?=number_format($demoPreview['quotations'] ?? 0);?></strong></div>
                <div class="d-flex justify-content-between"><span>Site surveys</span><strong><?=number_format($demoPreview['site_surveys'] ?? 0);?></strong></div>
            </div>
            <form action="clear_demo_data.php" method="post" class="d-flex flex-column flex-grow-1">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['data_tools_csrf']);?>">
                <div class="mb-3"><label class="form-label" for="clearConfirmation">Type <strong>CLEAR DEMO</strong></label><input class="form-control" id="clearConfirmation" name="confirmation" autocomplete="off" required <?=$isAdmin ? '' : 'disabled';?>></div>
                <button class="btn btn-danger w-100 mt-auto" <?=(!$isAdmin || $demoMainCount === 0) ? 'disabled' : '';?>><i class="fa fa-trash"></i> Clear demo data</button>
                <?php if ($demoMainCount === 0): ?><small class="d-block text-muted mt-2">No marked demo records were found.</small><?php endif; ?>
            </form>
        </div></div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
