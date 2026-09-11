<?php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requirePermission('expenses_view');

$can_manage = hasPermission('expenses_manage');
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$category = trim($_GET['category'] ?? '');

$sql = "SELECT e.*, u.username FROM expenses e LEFT JOIN users u ON u.id=e.created_by WHERE DATE_FORMAT(e.expense_date, '%Y-%m')=?";
$types = 's';
$params = [$month];
if ($category !== '') {
    $sql .= ' AND e.category=?';
    $types .= 's';
    $params[] = $category;
}
$sql .= ' ORDER BY e.expense_date DESC, e.id DESC';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$expenses = $stmt->get_result();

$total_stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m')=?");
$total_stmt->bind_param('s', $month);
$total_stmt->execute();
$month_total = (float) ($total_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$categories = $conn->query("SELECT DISTINCT category FROM expenses WHERE category IS NOT NULL AND category<>'' ORDER BY category");

if (empty($_SESSION['expense_csrf'])) {
    $_SESSION['expense_csrf'] = bin2hex(random_bytes(32));
}
$page_title = 'Expenses';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-hero">
    <div>
        <div class="page-kicker">Finance</div>
        <h1 class="page-title">Expenses</h1>
        <p class="page-subtitle">Record operating costs and keep monthly profit accurate.</p>
    </div>
    <a href="/reports/profit.php?month=<?=htmlspecialchars($month);?>" class="btn btn-outline-primary"><i class="fa fa-chart-line"></i> Profit report</a>
</div>

<?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']);?></div><?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?></div><?php endif; ?>

<div class="row g-4">
    <?php if ($can_manage): ?>
    <div class="col-12 col-xl-4">
        <div class="card sticky-xl-top" style="top:105px">
            <div class="card-header">Add expense</div>
            <div class="card-body">
                <form action="save.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['expense_csrf']);?>">
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="<?=date('Y-m-d');?>" required></div>
                    <div class="mb-3"><label class="form-label">Category</label><input name="category" class="form-control" list="expenseCategories" placeholder="Transport, utilities…" required></div>
                    <datalist id="expenseCategories"><option>Materials purchase</option><option>Transport</option><option>Utilities</option><option>Rent</option><option>Maintenance</option><option>Office</option><option>Other</option></datalist>
                    <div class="mb-3"><label class="form-label">Supplier</label><input name="supplier" class="form-control" placeholder="Optional"></div>
                    <div class="mb-3"><label class="form-label">Amount</label><input type="number" min="0.01" step="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3"><label class="form-label">Receipt or photo</label><input type="file" name="receipt" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf"><div class="form-text">JPG, PNG, WebP or PDF up to 5 MB.</div></div>
                    <button class="btn btn-primary w-100"><i class="fa fa-plus"></i> Add expense</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-12 <?=$can_manage ? 'col-xl-8' : '';?>">
        <div class="row g-3 mb-3">
            <div class="col-sm-5"><div class="card metric-card"><div class="card-body"><div class="metric-label">Expenses for <?=date('F Y', strtotime($month.'-01'));?></div><div class="metric-value"><?=number_format($month_total, 2);?></div></div></div></div>
            <div class="col-sm-7">
                <form class="card card-body d-flex flex-sm-row gap-2" method="get">
                    <input type="month" name="month" value="<?=htmlspecialchars($month);?>" class="form-control">
                    <select name="category" class="form-select"><option value="">All categories</option><?php while($item=$categories->fetch_assoc()): ?><option value="<?=htmlspecialchars($item['category']);?>" <?=$category===$item['category']?'selected':'';?>><?=htmlspecialchars($item['category']);?></option><?php endwhile; ?></select>
                    <button class="btn btn-outline-primary">Filter</button>
                </form>
            </div>
        </div>

        <div class="card"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>Date</th><th>Expense</th><th>Supplier</th><th class="text-end">Amount</th><th>Receipt</th><?php if($can_manage):?><th></th><?php endif;?></tr></thead>
            <tbody>
            <?php if ($expenses->num_rows===0): ?><tr><td colspan="6"><div class="empty-state"><i class="fa fa-receipt"></i><div>No expenses found for this month.</div></div></td></tr><?php endif; ?>
            <?php while($row=$expenses->fetch_assoc()): ?>
                <tr><td><?=date('d M Y',strtotime($row['expense_date']));?></td><td><strong><?=htmlspecialchars($row['category']);?></strong><?php if($row['description']):?><small class="d-block text-muted"><?=htmlspecialchars($row['description']);?></small><?php endif;?></td><td><?=htmlspecialchars($row['supplier'] ?: '—');?></td><td class="text-end fw-bold"><?=number_format((float)$row['amount'],2);?></td><td><?php if($row['receipt_path']):?><a class="btn btn-sm btn-light" target="_blank" href="/uploads/expense_receipts/<?=rawurlencode($row['receipt_path']);?>"><i class="fa fa-paperclip"></i></a><?php else:?>—<?php endif;?></td>
                <?php if($can_manage):?><td class="text-end"><form method="post" action="delete.php" onsubmit="return confirm('Delete this expense?')"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['expense_csrf']);?>"><input type="hidden" name="id" value="<?=$row['id'];?>"><button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa fa-trash"></i></button></form></td><?php endif;?></tr>
            <?php endwhile; ?>
            </tbody>
        </table></div></div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
