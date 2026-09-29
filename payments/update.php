<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/permissions.php';
require_once '../includes/audit.php';
requirePermission('payments_manage');

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['payment_edit_csrf'])
    || !hash_equals(
        $_SESSION['payment_edit_csrf'],
        (string) ($_POST['csrf_token'] ?? '')
    )
) {
    $_SESSION['error'] = 'The update request expired. Please try again.';
    header('Location:index.php');
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$payment_method = trim((string) ($_POST['payment_method'] ?? ''));
$reference_no = trim((string) ($_POST['reference_no'] ?? ''));
$allowed_methods = [
    'Cash',
    'Bank Transfer',
    'Mobile Payment'
];

if (
    $id <= 0
    || !in_array($payment_method, $allowed_methods, true)
    || mb_strlen($reference_no) > 150
) {
    $_SESSION['error'] = 'Please enter valid payment details.';
    header('Location:' . ($id > 0 ? 'edit.php?id=' . $id : 'index.php'));
    exit();
}

$stmt = $conn->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$old_payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$old_payment) {
    $_SESSION['error'] = 'Payment not found.';
    header('Location:index.php');
    exit();
}

$stmt = $conn->prepare(
    'UPDATE payments
     SET payment_method = ?, reference_no = ?
     WHERE id = ?'
);
$stmt->bind_param('ssi', $payment_method, $reference_no, $id);

if ($stmt->execute()) {
    auditLog(
        $conn,
        'UPDATE',
        'payment',
        $id,
        'Updated payment method for payment #' . $id,
        $old_payment,
        [
            'payment_method' => $payment_method,
            'reference_no' => $reference_no
        ]
    );

    $_SESSION['success'] = 'Payment method updated.';
    header('Location:index.php');
} else {
    $_SESSION['error'] = 'The payment method could not be updated.';
    header('Location:edit.php?id=' . $id);
}

$stmt->close();
exit();

