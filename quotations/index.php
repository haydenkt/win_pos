<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
requirePermission('quotations_view');
if (empty($_SESSION['quotation_csrf'])) {
    $_SESSION['quotation_csrf'] = bin2hex(random_bytes(32));
}
$perPage = 50;
$page = max(1, (int) ($_GET['page'] ?? 1));
$count = $conn->query('SELECT COUNT(*) AS total FROM quotations q JOIN customers c ON c.id=q.customer_id')->fetch_assoc();
$totalRows = (int) $count['total'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$stmt = $conn->prepare('SELECT q.id, q.quote_no, q.quote_date, q.valid_until, q.status, q.total, c.name customer_name FROM quotations q JOIN customers c ON c.id=q.customer_id ORDER BY q.id DESC LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $perPage, $offset);
$stmt->execute();
$rows = $stmt->get_result();
$stmt->close();
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
            <thead><tr><th>Quotation</th><th>Customer</th><th>Date</th><th>Valid until</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Actions</th></tr></thead>
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
                        <a href="view.php?id=<?=$row['id'];?>" class="btn btn-sm btn-info" title="View quotation"><i class="fa fa-eye"></i></a>
                        <?php if (hasPermission('quotations_manage') && $row['status'] !== 'Converted'): ?>
                            <a href="add.php?id=<?=$row['id'];?>" class="btn btn-sm btn-primary" title="Edit quotation"><i class="fa fa-pen"></i></a>
                            <form action="delete.php" method="post" class="d-inline" onsubmit="return confirm('Delete this quotation? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['quotation_csrf']);?>">
                                <input type="hidden" name="id" value="<?=(int)$row['id'];?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete quotation"><i class="fa fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-muted">Showing <?=$totalRows ? $offset + 1 : 0;?>–<?=min($offset + $perPage, $totalRows);?> of <?=$totalRows;?> quotations</span>
        <nav aria-label="Quotation pages" class="d-flex align-items-center gap-2">
            <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-primary" href="?page=<?=$page - 1;?>">Previous</a><?php endif; ?>
            <span>Page <?=$page;?> of <?=$totalPages;?></span>
            <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-outline-primary" href="?page=<?=$page + 1;?>">Next</a><?php endif; ?>
        </nav>
    </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
