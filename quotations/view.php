<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requirePermission('quotations_view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT q.*, c.name customer_name, c.phone, c.address FROM quotations q JOIN customers c ON c.id=q.customer_id WHERE q.id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$quote = $stmt->get_result()->fetch_assoc();

if (!$quote) {
    http_response_code(404);
    die('Quotation not found.');
}

$stmt = $conn->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY id');
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();

if (empty($_SESSION['quotation_csrf'])) {
    $_SESSION['quotation_csrf'] = bin2hex(random_bytes(32));
}

$page_title = $quote['quote_no'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-hero">
    <div>
        <h1 class="page-title"><?=htmlspecialchars($quote['quote_no']);?></h1>
        <p class="page-subtitle"><?=htmlspecialchars($quote['customer_name']);?> · <?=date('d M Y', strtotime($quote['quote_date']));?></p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a class="btn btn-outline-primary" href="print.php?id=<?=$id;?>" target="_blank"><i class="fa fa-print"></i> Print quotation</a>
        <span class="badge text-bg-primary fs-6"><?=htmlspecialchars($quote['status']);?></span>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']);?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Product</th><th>Dimensions</th><th class="text-end">Qty</th><th class="text-end">Sqft</th><th class="text-end">Rate</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            <?php while ($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><strong><?=htmlspecialchars($item['product_name']);?></strong><?php if ($item['description']): ?><small class="d-block text-muted"><?=htmlspecialchars($item['description']);?></small><?php endif; ?></td>
                    <td><?=number_format((float) $item['width_mm'], 0);?> × <?=number_format((float) $item['height_mm'], 0);?> mm</td>
                    <td class="text-end"><?=$item['quantity'];?></td>
                    <td class="text-end"><?=number_format((float) $item['sqft'], 2);?></td>
                    <td class="text-end"><?=number_format((float) $item['unit_price'], 2);?></td>
                    <td class="text-end fw-bold"><?=number_format((float) $item['total'], 2);?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end"><?=number_format((float) $quote['subtotal'], 2);?></td></tr>
                <tr><td colspan="5" class="text-end">Discount</td><td class="text-end"><?=number_format((float) $quote['discount'], 2);?></td></tr>
                <tr><th colspan="5" class="text-end">Total</th><th class="text-end"><?=number_format((float) $quote['total'], 2);?></th></tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if ($quote['notes']): ?>
    <div class="card card-body mb-4"><strong class="mb-2">Notes</strong><?=nl2br(htmlspecialchars($quote['notes']));?></div>
<?php endif; ?>

<?php if (hasPermission('quotations_manage') && $quote['status'] !== 'Converted'): ?>
<div class="card card-body">
    <h2 class="h5">Convert quotation</h2>
    <p class="text-muted">Create the next business document using these items and prices.</p>
    <div class="row g-3">
        <div class="col-lg-4">
            <form action="convert.php" method="post" class="border rounded-3 p-3 h-100">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['quotation_csrf']);?>">
                <input type="hidden" name="id" value="<?=$id;?>">
                <input type="hidden" name="target" value="order">
                <h3 class="h6">Create an order</h3>
                <p class="small text-muted">Send the quotation to factory workflow.</p>
                <button class="btn btn-outline-primary w-100"><i class="fa fa-clipboard-list"></i> Convert to order</button>
            </form>
        </div>
        <div class="col-lg-8">
            <form action="convert.php" method="post" class="border rounded-3 p-3 h-100">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['quotation_csrf']);?>">
                <input type="hidden" name="id" value="<?=$id;?>">
                <input type="hidden" name="target" value="invoice">
                <div class="d-flex justify-content-between gap-3 mb-3">
                    <div><h3 class="h6 mb-1">Create invoice and receive payment</h3><p class="small text-muted mb-0">Payment is optional. Enter zero to create an unpaid invoice.</p></div>
                    <strong class="text-nowrap"><?=number_format((float) $quote['total'], 2);?></strong>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="paymentAmount">Payment amount</label>
                        <input class="form-control" id="paymentAmount" name="payment_amount" type="number" min="0" max="<?=htmlspecialchars((string) $quote['total']);?>" step="0.01" value="0" required>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-light payment-shortcut" data-amount="0">No payment</button>
                            <button type="button" class="btn btn-sm btn-light payment-shortcut" data-amount="<?=htmlspecialchars((string) round((float) $quote['total'] / 2, 2));?>">50%</button>
                            <button type="button" class="btn btn-sm btn-light payment-shortcut" data-amount="<?=htmlspecialchars((string) $quote['total']);?>">Pay full</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="paymentMethod">Payment method</label>
                        <select class="form-select" id="paymentMethod" name="payment_method"><option value="Cash">Cash</option><option value="Bank Transfer">Bank transfer</option><option value="Mobile Payment">Mobile payment</option><option value="Cheque">Cheque</option></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="paymentDate">Payment date</label>
                        <input class="form-control" id="paymentDate" name="payment_date" type="date" value="<?=date('Y-m-d');?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="referenceNo">Reference number <span class="text-muted">(optional)</span></label>
                        <input class="form-control" id="referenceNo" name="reference_no" maxlength="100" placeholder="Transfer or receipt reference">
                    </div>
                </div>
                <button class="btn btn-primary w-100 mt-3"><i class="fa fa-file-invoice"></i> Convert to invoice</button>
            </form>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.payment-shortcut').forEach((button) => {
    button.addEventListener('click', () => {
        document.getElementById('paymentAmount').value = button.dataset.amount;
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
