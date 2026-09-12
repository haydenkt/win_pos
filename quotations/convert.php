<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit.php';
requirePermission('quotations_manage');

$id = (int) ($_POST['id'] ?? 0);
$target = $_POST['target'] ?? '';

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['quotation_csrf'])
    || !hash_equals($_SESSION['quotation_csrf'], $_POST['csrf_token'] ?? '')
    || !in_array($target, ['order', 'invoice'], true)
) {
    $_SESSION['error'] = 'Invalid conversion request.';
    header('Location:view.php?id=' . $id);
    exit;
}

$paymentAmount = 0.0;
$paymentMethod = trim((string) ($_POST['payment_method'] ?? 'Cash'));
$paymentDate = (string) ($_POST['payment_date'] ?? date('Y-m-d'));
$referenceNo = trim((string) ($_POST['reference_no'] ?? ''));
$allowedMethods = ['Cash', 'Bank Transfer', 'Mobile Payment', 'Cheque'];

if ($target === 'invoice') {
    $rawAmount = $_POST['payment_amount'] ?? '0';
    if (!is_numeric($rawAmount) || (float) $rawAmount < 0) {
        $_SESSION['error'] = 'Enter a valid payment amount.';
        header('Location:view.php?id=' . $id);
        exit;
    }
    $paymentAmount = round((float) $rawAmount, 2);

    if (!in_array($paymentMethod, $allowedMethods, true)) {
        $_SESSION['error'] = 'Select a valid payment method.';
        header('Location:view.php?id=' . $id);
        exit;
    }

    $dateCheck = DateTime::createFromFormat('Y-m-d', $paymentDate);
    if (!$dateCheck || $dateCheck->format('Y-m-d') !== $paymentDate) {
        $_SESSION['error'] = 'Select a valid payment date.';
        header('Location:view.php?id=' . $id);
        exit;
    }
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT * FROM quotations WHERE id=? AND status<>'Converted' FOR UPDATE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $quotation = $stmt->get_result()->fetch_assoc();

    if (!$quotation) {
        throw new Exception('Quotation is missing or already converted.');
    }

    $quotationTotal = round((float) $quotation['total'], 2);
    if ($target === 'invoice' && $paymentAmount > $quotationTotal) {
        throw new Exception('Payment amount cannot be higher than the quotation total.');
    }

    $stmt = $conn->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY id');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $items = $stmt->get_result();

    if ($target === 'order') {
        $status = 'Quotation';
        $stmt = $conn->prepare('INSERT INTO orders (invoice_no,customer_id,order_status,notes) VALUES (?,?,?,?)');
        $stmt->bind_param('siss', $quotation['quote_no'], $quotation['customer_id'], $status, $quotation['notes']);
        $stmt->execute();
        $newId = $conn->insert_id;

        $insert = $conn->prepare("INSERT INTO order_items (order_id,factory_product_id,product_name,width_mm,height_mm,width_ft,height_ft,calculation_type,quantity,sqft,description,final_price_per_sqft) VALUES (?,?,?,?,?,?,?,'SQFT',?,?,?,?)");
        while ($item = $items->fetch_assoc()) {
            $widthFt = ceil(($item['width_mm'] / 304.8) * 2) / 2;
            $heightFt = ceil(($item['height_mm'] / 304.8) * 2) / 2;
            $insert->bind_param('iisddddidsd', $newId, $item['factory_product_id'], $item['product_name'], $item['width_mm'], $item['height_mm'], $widthFt, $heightFt, $item['quantity'], $item['sqft'], $item['description'], $item['unit_price']);
            $insert->execute();
        }

        $field = 'order_id';
        $url = '/orders/view.php?id=' . $newId;
        $success = 'Quotation converted to order.';
    } else {
        $sequence = (int) $conn->query('SELECT COALESCE(MAX(invoice_sequence),0)+1 n FROM invoices FOR UPDATE')->fetch_assoc()['n'];
        $invoiceNo = 'INV-' . date('Y') . '-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        $balance = max(0, $quotationTotal - $paymentAmount);
        $paymentStatus = $paymentAmount <= 0 ? 'Unpaid' : ($balance <= 0 ? 'Paid' : 'Partial');
        $invoiceStatus = $paymentAmount > 0 ? 'Confirmed' : 'Draft';

        $stmt = $conn->prepare('INSERT INTO invoices (customer_id,invoice_no,subtotal,discount,grand_total,deposit,balance,payment_status,invoice_status,notes,invoice_sequence) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('isdddddsssi', $quotation['customer_id'], $invoiceNo, $quotation['subtotal'], $quotation['discount'], $quotationTotal, $paymentAmount, $balance, $paymentStatus, $invoiceStatus, $quotation['notes'], $sequence);
        $stmt->execute();
        $newId = $conn->insert_id;

        $insert = $conn->prepare("INSERT INTO invoice_items (invoice_id,item_type,product_name,quantity,width_mm,height_mm,sqft,price,total,description) VALUES (?,'SERVICE',?,?,?,?,?,?,?,?)");
        while ($item = $items->fetch_assoc()) {
            $insert->bind_param('isiddddds', $newId, $item['product_name'], $item['quantity'], $item['width_mm'], $item['height_mm'], $item['sqft'], $item['unit_price'], $item['total'], $item['description']);
            $insert->execute();
        }

        if ($paymentAmount > 0) {
            $paymentType = $balance <= 0 ? 'Full Payment' : 'Deposit';
            $paymentNote = 'Received while converting quotation ' . $quotation['quote_no'];
            $stmt = $conn->prepare('INSERT INTO payments (invoice_id,customer_id,order_id,amount,payment_method,reference_no,payment_type,payment_date,note,created_at) VALUES (?,?,NULL,?,?,?,?,?,?,NOW())');
            $stmt->bind_param('iidsssss', $newId, $quotation['customer_id'], $paymentAmount, $paymentMethod, $referenceNo, $paymentType, $paymentDate, $paymentNote);
            $stmt->execute();
            $paymentId = $conn->insert_id;
            auditLog($conn, 'CREATE', 'payment', $paymentId, 'Added payment while converting ' . $quotation['quote_no'], null, ['invoice_id' => $newId, 'amount' => $paymentAmount, 'date' => $paymentDate]);
        }

        $field = 'invoice_id';
        $url = '/invoices/view.php?id=' . $newId;
        $success = $paymentAmount > 0
            ? 'Quotation converted to invoice and payment recorded.'
            : 'Quotation converted to unpaid invoice.';
    }

    $stmt = $conn->prepare("UPDATE quotations SET status='Converted', $field=? WHERE id=?");
    $stmt->bind_param('ii', $newId, $id);
    $stmt->execute();

    auditLog($conn, 'CONVERT', 'quotation', $id, 'Converted ' . $quotation['quote_no'] . ' to ' . $target, null, [$target . '_id' => $newId, 'payment_amount' => $paymentAmount]);
    $conn->commit();

    $_SESSION['success'] = $success;
    header('Location:' . $url);
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    $_SESSION['error'] = 'Conversion failed: ' . $error->getMessage();
    header('Location:view.php?id=' . $id);
    exit;
}
