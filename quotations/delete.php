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
    $_SESSION['error'] = 'The delete request expired.';
    header('Location:index.php');
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT * FROM quotations WHERE id=? AND status<>'Converted' FOR UPDATE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $quotation = $stmt->get_result()->fetch_assoc();
    if (!$quotation) {
        throw new RuntimeException('Converted quotations cannot be deleted.');
    }

    $stmt = $conn->prepare('DELETE FROM quotation_items WHERE quotation_id=?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }

    $stmt = $conn->prepare('DELETE FROM quotations WHERE id=?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }

    auditLog($conn, 'DELETE', 'quotation', $id, 'Deleted quotation ' . $quotation['quote_no'], $quotation, null);
    $conn->commit();
    $_SESSION['success'] = 'Quotation ' . $quotation['quote_no'] . ' deleted.';
} catch (Throwable $error) {
    $conn->rollback();
    $_SESSION['error'] = 'Quotation could not be deleted: ' . $error->getMessage();
}

header('Location:index.php');
exit;
