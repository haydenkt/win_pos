<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requirePermission('payments_view');
$can_manage = hasPermission('payments_manage');

// Use the business's local calendar day, not the hosting server's timezone.
$timezone = new DateTimeZone('Asia/Yangon');
$today = new DateTimeImmutable('today', $timezone);
$rawDate = is_string($_GET['date'] ?? null) ? $_GET['date'] : $today->format('Y-m-d');
$selectedDay = DateTimeImmutable::createFromFormat('!Y-m-d', $rawDate, $timezone);
$dateError = '';
if (!$selectedDay || $selectedDay->format('Y-m-d') !== $rawDate) {
    $selectedDay = $today;
    $dateError = 'Please choose a valid date. Showing today’s payments.';
}
$date = $selectedDay->format('Y-m-d');
$nextDate = $selectedDay->modify('+1 day')->format('Y-m-d');
$search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';

$sql = 'SELECT payments.*, invoices.invoice_no, customers.name AS customer_name,
               customers.phone AS customer_phone
        FROM payments
        LEFT JOIN invoices ON payments.invoice_id=invoices.id
        LEFT JOIN customers ON payments.customer_id=customers.id
        WHERE payments.payment_date >= ? AND payments.payment_date < ?';
$params = [$date, $nextDate];
if ($search !== '') {
    $sql .= ' AND (invoices.invoice_no LIKE ? OR customers.name LIKE ? OR customers.phone LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY payments.id DESC';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dailyTotal = array_sum(array_column($payments, 'amount'));
$page_title = 'Payments';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h2><i class="fa fa-money-bill"></i> Payments</h2>
<p class="text-muted">Payments for <?=htmlspecialchars($selectedDay->format('d M Y'));?>. Choose a date to view another day.</p>

<?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $color): ?>
    <?php if (!empty($_SESSION[$key])): ?>
        <div class="alert alert-<?=$color;?> alert-dismissible fade show mt-3" role="alert">
            <?=htmlspecialchars($_SESSION[$key]); unset($_SESSION[$key]);?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
<?php if ($dateError): ?><div class="alert alert-warning" role="alert"><?=htmlspecialchars($dateError);?></div><?php endif; ?>

<div class="card mt-3">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="paymentDate">Payment date</label>
                <input type="date" id="paymentDate" name="date" class="form-control" value="<?=htmlspecialchars($date);?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="paymentSearch">Search</label>
                <input type="search" id="paymentSearch" name="search" class="form-control" placeholder="Search invoice, customer, phone..." value="<?=htmlspecialchars($search);?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Search</button>
                <a href="index.php" class="btn btn-light" data-reset-filters>Today</a>
            </div>
        </form>
    </div>
</div>
<div class="d-flex flex-wrap justify-content-between gap-2 my-3">
    <span class="text-muted"><?=count($payments);?> payment(s)<?=$search !== '' ? ' matching your search' : '';?> on <?=htmlspecialchars($selectedDay->format('d M Y'));?></span>
    <strong>Total: <?=number_format($dailyTotal, 2);?> MMK</strong>
</div>
<div class="card mt-3">
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mb-0">
            <thead><tr><th>#</th><th>Invoice No</th><th>Customer</th><th>Amount</th><th>Payment Type</th><th>Method</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $index => $row): ?>
                <tr>
                    <td><?=$index + 1;?></td>
                    <td><?=htmlspecialchars((string)($row['invoice_no'] ?? '—'));?></td>
                    <td><?=htmlspecialchars((string)($row['customer_name'] ?? '—'));?><br><small><?=htmlspecialchars((string)($row['customer_phone'] ?? ''));?></small></td>
                    <td><?=number_format((float)$row['amount']);?></td>
                    <td><?=htmlspecialchars((string)$row['payment_type']);?></td>
                    <td><?=htmlspecialchars((string)$row['payment_method']);?></td>
                    <td><?=date('d-m-Y', strtotime($row['payment_date']));?></td>
                    <td>
                        <div class="d-flex gap-1 flex-nowrap">
                        <?php if ($can_manage): ?>
                            <a href="edit.php?id=<?=(int)$row['id'];?>" class="btn btn-warning btn-sm" title="Edit payment method"><i class="fa fa-edit"></i></a>
                        <?php endif; ?>
                        <a href="../invoices/view.php?id=<?=(int)$row['invoice_id'];?>" class="btn btn-info btn-sm" title="View invoice"><i class="fa fa-eye"></i></a>
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="delete.php?id=<?=(int)$row['id'];?>" class="btn btn-danger btn-sm" title="Delete payment" onclick="return confirm('Delete this payment?');"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$payments): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No payments found for this day<?=$search !== '' ? ' matching your search' : '';?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
