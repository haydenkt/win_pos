<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
requirePermission('quotations_view');
$rows = $conn->query('SELECT q.*, c.name customer_name FROM quotations q JOIN customers c ON c.id=q.customer_id ORDER BY q.id DESC');
$page_title = 'Quotations';
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../includes/sidebar.php';
?>
<div class="page-hero">
    <div>
        <div class="page-kicker">Sales</div>
        <h1 class="page-title">Quotations</h1>
        <p class="page-subtitle">Prepare sqft estimates before creating orders or invoices.</p>
    </div>
    <?php if (hasPermission('quotations_manage')): ?>
        <a href="add.php" class="btn btn-primary"><i class="fa fa-plus"></i> New quotation</a>
    <?php endif; ?>
</div>
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']);?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?></div>
<?php endif; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Quotation</th><th>Customer</th><th>Date</th><th>Valid until</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows->num_rows === 0): ?>
                <tr><td colspan="7"><div class="empty-state">No quotations found.</div></td></tr>
            <?php endif; ?>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td><strong><?=htmlspecialchars($row['quote_no']);?></strong></td>
                    <td><?=htmlspecialchars($row['customer_name']);?></td>
                    <td><?=date('d M Y', strtotime($row['quote_date']));?></td>
                    <td><?=$row['valid_until'] ? date('d M Y', strtotime($row['valid_until'])) : '—';?></td>
                    <td><span class="badge text-bg-secondary"><?=htmlspecialchars($row['status']);?></span></td>
                    <td class="text-end fw-bold"><?=number_format((float)$row['total'], 2);?></td>
                    <td class="text-end text-nowrap">
                        <a href="print.php?id=<?=$row['id'];?>" target="_blank" class="btn btn-sm btn-light" title="Print quotation"><i class="fa fa-print"></i></a>
                        <a href="view.php?id=<?=$row['id'];?>" class="btn btn-sm btn-light">View</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
