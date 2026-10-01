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
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || $id <= 0
    || empty($_SESSION['quotation_csrf'])
    || !hash_equals($_SESSION['quotation_csrf'], $_POST['csrf_token'] ?? '')
) {
    $_SESSION['error'] = 'The update request expired.';
    header('Location:index.php');
    exit;
}

$customerId = (int) ($_POST['customer_id'] ?? 0);
$quoteDate = trim((string) ($_POST['quote_date'] ?? ''));
$validUntilRaw = trim((string) ($_POST['valid_until'] ?? ''));
$validUntil = $validUntilRaw === '' ? null : $validUntilRaw;
$discount = max(0, (float) ($_POST['discount'] ?? 0));
$notes = trim((string) ($_POST['notes'] ?? ''));
$productIds = $_POST['factory_product_id'] ?? [];
$datePattern = '/^\d{4}-\d{2}-\d{2}$/';

if (
    $customerId <= 0
    || !preg_match($datePattern, $quoteDate)
    || ($validUntil !== null && !preg_match($datePattern, $validUntil))
    || !is_array($productIds)
    || count($productIds) === 0
) {
    $_SESSION['error'] = 'Customer, quotation date, and at least one item are required.';
    header('Location:add.php?id=' . $id);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT * FROM quotations WHERE id=? AND status<>'Converted' FOR UPDATE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $oldQuotation = $stmt->get_result()->fetch_assoc();
    if (!$oldQuotation) {
        throw new RuntimeException('This quotation is missing or already converted.');
    }

    $items = [];
    $subtotal = 0.0;
    $lookup = $conn->prepare('SELECT product_name FROM factory_products WHERE id=?');

    foreach ($productIds as $index => $rawProductId) {
        $productId = (int) $rawProductId;
        $width = max(0, (float) ($_POST['width_mm'][$index] ?? 0));
        $height = max(0, (float) ($_POST['height_mm'][$index] ?? 0));
        $quantity = max(1, (int) ($_POST['quantity'][$index] ?? 1));
        $unitPrice = max(0, (float) ($_POST['unit_price'][$index] ?? 0));
        $description = trim((string) ($_POST['description'][$index] ?? ''));

        if ($productId <= 0 || $width <= 0 || $height <= 0) {
            continue;
        }

        $lookup->bind_param('i', $productId);
        $lookup->execute();
        $product = $lookup->get_result()->fetch_assoc();
        if (!$product) {
            throw new RuntimeException('A selected factory product was not found.');
        }

        $widthFeet = ceil(($width / 304.8) * 2) / 2;
        $heightFeet = ceil(($height / 304.8) * 2) / 2;
        $squareFeet = $widthFeet * $heightFeet * $quantity;
        $lineTotal = $squareFeet * $unitPrice;
        $items[] = [$productId, $product['product_name'], $description, $width, $height, $quantity, $squareFeet, $unitPrice, $lineTotal];
        $subtotal += $lineTotal;
    }

    if (!$items) {
        throw new RuntimeException('At least one complete quotation item is required.');
    }

    $total = max(0, $subtotal - $discount);
    $stmt = $conn->prepare('UPDATE quotations SET customer_id=?, quote_date=?, valid_until=?, subtotal=?, discount=?, total=?, notes=? WHERE id=?');
    $stmt->bind_param('issdddsi', $customerId, $quoteDate, $validUntil, $subtotal, $discount, $total, $notes, $id);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }

    $stmt = $conn->prepare('DELETE FROM quotation_items WHERE quotation_id=?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }

    $insert = $conn->prepare('INSERT INTO quotation_items (quotation_id,factory_product_id,product_name,description,width_mm,height_mm,quantity,sqft,unit_price,total) VALUES (?,?,?,?,?,?,?,?,?,?)');
    foreach ($items as $item) {
        [$productId, $productName, $description, $width, $height, $quantity, $squareFeet, $unitPrice, $lineTotal] = $item;
        $insert->bind_param('iissddiddd', $id, $productId, $productName, $description, $width, $height, $quantity, $squareFeet, $unitPrice, $lineTotal);
        if (!$insert->execute()) {
            throw new RuntimeException($insert->error);
        }
    }

    auditLog(
        $conn,
        'UPDATE',
        'quotation',
        $id,
        'Updated quotation ' . $oldQuotation['quote_no'],
        ['customer_id' => $oldQuotation['customer_id'], 'total' => $oldQuotation['total']],
        ['customer_id' => $customerId, 'total' => $total]
    );

    $conn->commit();
    $_SESSION['clear_quotation_draft'] = 'win_pos_quotation_draft_' . $id;
    $_SESSION['success'] = 'Quotation ' . $oldQuotation['quote_no'] . ' updated.';
    header('Location:view.php?id=' . $id);
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    $_SESSION['error'] = 'Quotation could not be updated: ' . $error->getMessage();
    header('Location:add.php?id=' . $id);
    exit;
}
