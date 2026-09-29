<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('payments_manage');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Payment not found.';
    header('Location:index.php');
    exit();
}

$stmt = $conn->prepare(
    'SELECT
        payments.*,
        invoices.invoice_no,
        customers.name AS customer_name
     FROM payments
     LEFT JOIN invoices ON invoices.id = payments.invoice_id
     LEFT JOIN customers ON customers.id = payments.customer_id
     WHERE payments.id = ?
     LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    $_SESSION['error'] = 'Payment not found.';
    header('Location:index.php');
    exit();
}

if (empty($_SESSION['payment_edit_csrf'])) {
    $_SESSION['payment_edit_csrf'] = bin2hex(random_bytes(32));
}

$payment_methods = [
    'Cash',
    'Bank Transfer',
    'Mobile Payment'
];

$page_title = 'Edit payment method';
include '../includes/header.php';
include '../includes/sidebar.php';

?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1"><i class="fa fa-credit-card me-2"></i>Edit Payment Method</h2>
        <p class="text-muted mb-0">Update how this payment was received without changing its amount.</p>
    </div>
    <a href="index.php" class="btn btn-light">
        <i class="fa fa-arrow-left"></i> Back to Payments
    </a>
</div>

<?php if (!empty($_SESSION['error'])) { ?>
    <div class="alert alert-danger">
        <?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?>
    </div>
<?php } ?>

<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-9">
        <div class="card">
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Invoice</div>
                            <strong><?=htmlspecialchars((string) ($payment['invoice_no'] ?? '—'));?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Customer</div>
                            <strong><?=htmlspecialchars((string) ($payment['customer_name'] ?? '—'));?></strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted">Amount</div>
                            <strong><?=number_format((float) $payment['amount'], 2);?></strong>
                        </div>
                    </div>
                </div>

                <form method="post" action="update.php">
                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['payment_edit_csrf']);?>">
                    <input type="hidden" name="id" value="<?=(int) $payment['id'];?>">

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="form-select" required>
                            <?php foreach ($payment_methods as $method) { ?>
                                <option value="<?=htmlspecialchars($method);?>"
                                    <?=$payment['payment_method'] === $method ? 'selected' : '';?>>
                                    <?=htmlspecialchars($method);?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="reference_no" class="form-label">Reference Number</label>
                        <input id="reference_no" type="text" name="reference_no" class="form-control"
                               maxlength="150" value="<?=htmlspecialchars((string) ($payment['reference_no'] ?? ''));?>"
                               placeholder="Optional for bank or mobile payments">
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Payment Method
                        </button>
                        <a href="index.php" class="btn btn-light">Cancel</a>
                        <?php if ((int) ($payment['invoice_id'] ?? 0) > 0) { ?>
                            <a href="add.php?invoice_id=<?=(int) $payment['invoice_id'];?>" class="btn btn-outline-primary ms-md-auto">
                                <i class="fa fa-history"></i> Payment History
                            </a>
                        <?php } ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
