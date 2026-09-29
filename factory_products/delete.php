<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/permissions.php';
require_once '../includes/audit.php';
requirePermission('factory_manage');

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['factory_product_csrf'])
    || !hash_equals(
        $_SESSION['factory_product_csrf'],
        (string) ($_POST['csrf_token'] ?? '')
    )
) {
    $_SESSION['error'] = 'The delete request expired. Please try again.';
    header('Location:index.php');
    exit();
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = 'Factory product not found.';
    header('Location:index.php');
    exit();
}

try {
    $stmt = $conn->prepare(
        'SELECT * FROM factory_products WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $_SESSION['error'] = 'Factory product not found.';
        header('Location:index.php');
        exit();
    }

    $stmt = $conn->prepare(
        "SELECT
            (SELECT COUNT(*) FROM order_items WHERE factory_product_id = ?)
            + (SELECT COUNT(*) FROM quotation_items WHERE factory_product_id = ?)
            + (SELECT COUNT(*) FROM invoice_items WHERE item_type = 'ORDER' AND product_id = ?)
            AS usage_count"
    );
    $stmt->bind_param('iii', $id, $id, $id);
    $stmt->execute();
    $usage_count = (int) ($stmt->get_result()->fetch_assoc()['usage_count'] ?? 0);
    $stmt->close();

    if ($usage_count > 0) {
        $_SESSION['error'] = 'This factory product is already used in sales or orders. Set it to Inactive instead of deleting it.';
        header('Location:index.php');
        exit();
    }

    $stmt = $conn->prepare('DELETE FROM factory_products WHERE id = ?');
    $stmt->bind_param('i', $id);

    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        throw new RuntimeException('Factory product deletion failed.');
    }

    $stmt->close();

    auditLog(
        $conn,
        'DELETE',
        'factory_product',
        $id,
        'Deleted factory product: ' . $product['product_name'],
        $product,
        null
    );

    $_SESSION['success'] = 'Factory product deleted.';
} catch (Throwable $error) {
    $_SESSION['error'] = 'The factory product could not be deleted. It may already be in use.';
}

header('Location:index.php');
exit();

