<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location:../index.php'); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/items.php';
requirePermission('quotations_manage');
$editing = $quotationEditing ?? false;
$id = $editing ? (int) ($_POST['id'] ?? 0) : 0;
$returnUrl = 'add.php' . ($editing ? '?id=' . $id : '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($editing && $id <= 0)
    || empty($_SESSION['quotation_csrf']) || !is_string($_POST['csrf_token'] ?? null)
    || !hash_equals($_SESSION['quotation_csrf'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'The request expired. Please try again.';
    header('Location:index.php'); exit;
}
$conn->begin_transaction();
try {
    if (($_POST['form_complete'] ?? '') !== '1') {
        throw new InvalidArgumentException('The form was incomplete. There may be more items than the server allows; reduce the number of items and try again.');
    }
    $old = null;
    if ($editing) {
        $stmt = $conn->prepare("SELECT * FROM quotations WHERE id=? AND status<>'Converted' FOR UPDATE");
        $stmt->execute([$id]);
        $old = $stmt->get_result()->fetch_assoc();
        if (!$old) throw new RuntimeException('This quotation is missing or already converted.');
    }
    $date = trim((string) ($_POST['quote_date'] ?? ''));
    $valid = trim((string) ($_POST['valid_until'] ?? '')) ?: null;
    foreach ([$date, $valid] as $dateValue) {
        if ($dateValue === null) continue;
        $parsed = DateTime::createFromFormat('!Y-m-d', $dateValue);
        if (!$parsed || $parsed->format('Y-m-d') !== $dateValue) throw new InvalidArgumentException('Enter valid quotation dates.');
    }
    if ($valid !== null && $valid < $date) throw new InvalidArgumentException('Valid until cannot be before the quotation date.');
    $status = $_POST['status'] ?? 'Draft';
    if (!in_array($status, ['Draft', 'Sent', 'Accepted', 'Rejected'], true)) throw new InvalidArgumentException('Select a valid quotation status.');
    $items = quotationParseItems($_POST, fn($type, $pid) => quotationLookup($conn, $type, $pid));
    $subtotal = round(array_sum(array_column($items, 'total')), 2);
    $discount = quotationNumber($_POST['discount'] ?? '', 'discount');
    if ($discount > $subtotal) throw new InvalidArgumentException('Discount cannot exceed the subtotal.');
    if ($subtotal > 9999999999.99) throw new InvalidArgumentException('Quotation total is too large.');
    $total = round($subtotal - $discount, 2);
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $customerId = (int) ($_POST['customer_id'] ?? 0);
    if (($_POST['customer_type'] ?? 'existing') === 'new') {
        $name = trim((string) ($_POST['new_name'] ?? ''));
        $phone = trim((string) ($_POST['new_phone'] ?? ''));
        $address = trim((string) ($_POST['new_address'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($phone) > 50) throw new InvalidArgumentException('Enter a valid customer name (up to 100 characters) and phone.');
        $stmt = $conn->prepare('INSERT INTO customers (name,phone,address) VALUES (?,?,?)');
        $stmt->execute([$name, $phone, $address]);
        $customerId = $conn->insert_id;
    } else {
        $stmt = $conn->prepare('SELECT id FROM customers WHERE id=?');
        $stmt->execute([$customerId]);
        if (!$stmt->get_result()->fetch_assoc()) throw new InvalidArgumentException('Please select an existing customer.');
    }
    if ($editing) {
        $quoteNo = $old['quote_no'];
        $stmt = $conn->prepare('UPDATE quotations SET customer_id=?,quote_date=?,valid_until=?,subtotal=?,discount=?,total=?,notes=?,status=? WHERE id=?');
        $stmt->execute([$customerId, $date, $valid, $subtotal, $discount, $total, $notes, $status, $id]);
        $stmt = $conn->prepare('DELETE FROM quotation_items WHERE quotation_id=?');
        $stmt->execute([$id]);
    } else {
        // Reserve the auto-increment id first; do not race on MAX(id) for document numbers.
        $quoteNo = 'PENDING-' . bin2hex(random_bytes(12));
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $stmt = $conn->prepare('INSERT INTO quotations (quote_no,customer_id,quote_date,valid_until,subtotal,discount,total,notes,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$quoteNo, $customerId, $date, $valid, $subtotal, $discount, $total, $notes, $status, $userId]);
        $id = $conn->insert_id;
        $quoteNo = 'QUO-' . date('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare('UPDATE quotations SET quote_no=? WHERE id=?');
        $stmt->execute([$quoteNo, $id]);
    }
    quotationInsertItems($conn, $id, $items);
    auditLog($conn, $editing ? 'UPDATE' : 'CREATE', 'quotation', $id, ($editing ? 'Updated ' : 'Created ') . $quoteNo,
        $old ? ['customer_id' => $old['customer_id'], 'total' => $old['total']] : null,
        ['customer_id' => $customerId, 'total' => $total]);
    $conn->commit();
    $_SESSION['clear_quotation_draft'] = $editing ? 'win_pos_quotation_draft_' . $id : 'win_pos_quotation_draft_new';
    $_SESSION['success'] = 'Quotation ' . $quoteNo . ($editing ? ' updated.' : ' created.');
    header('Location:view.php?id=' . $id); exit;
} catch (Throwable $error) {
    $conn->rollback();
    $_SESSION['error'] = 'Quotation could not be saved: ' . $error->getMessage();
    header('Location:' . $returnUrl); exit;
}
