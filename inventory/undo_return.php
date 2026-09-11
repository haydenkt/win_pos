<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include_once '../config/database.php';
require_once '../includes/permissions.php';

requirePermission('returned_inventory_view');

$permission_data = loadUserPermissions();

if (empty($permission_data['is_admin'])) {
    http_response_code(403);
    die('Only an administrator can undo a return.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location:returned.php');
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token'])
    || !is_string($csrf_token)
    || !hash_equals($_SESSION['csrf_token'], $csrf_token)
) {
    $_SESSION['error'] = 'The request expired. Please try again.';
    header('Location:returned.php');
    exit();
}

$return_id = intval($_POST['id'] ?? 0);

if ($return_id <= 0) {
    $_SESSION['error'] = 'Invalid returned inventory record.';
    header('Location:returned.php');
    exit();
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        SELECT *
        FROM returned_inventory
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception('Return lookup failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $return_id);
    $stmt->execute();
    $returned_item = $stmt->get_result()->fetch_assoc();

    if (!$returned_item) {
        throw new Exception('Returned inventory record not found.');
    }

    $returned_quantity = floatval($returned_item['quantity'] ?? 0);
    $available_quantity = floatval($returned_item['quantity_available'] ?? 0);

    if (
        $returned_quantity <= 0
        || abs($available_quantity - $returned_quantity) > 0.0001
        || ($returned_item['status'] ?? '') !== 'Available'
        || !empty($returned_item['sold_invoice_id'])
    ) {
        throw new Exception(
            'This return cannot be undone because some or all of its stock has already been used or sold.'
        );
    }

    $order_item_id = intval($returned_item['order_item_id'] ?? 0);

    if ($order_item_id <= 0) {
        throw new Exception('The original order item is missing.');
    }

    $stmt = $conn->prepare("
        SELECT quantity, returned_quantity
        FROM order_items
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception('Order item lookup failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $order_item_id);
    $stmt->execute();
    $order_item = $stmt->get_result()->fetch_assoc();

    if (!$order_item) {
        throw new Exception('The original order item was not found.');
    }

    $invoice_id = intval($returned_item['invoice_id'] ?? 0);
    $invoice_no = '';

    if ($invoice_id > 0) {
        $stmt = $conn->prepare("
            SELECT invoice_no
            FROM invoices
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception('Invoice lookup failed: ' . $conn->error);
        }

        $stmt->bind_param('i', $invoice_id);
        $stmt->execute();
        $invoice = $stmt->get_result()->fetch_assoc();
        $invoice_no = $invoice['invoice_no'] ?? '';
    }

    $history_id = 0;
    $product_id = 0;

    if ($invoice_no !== '') {
        $history_note =
            'Returned from Invoice '
            . $invoice_no
            . ' / Order Item #'
            . $order_item_id;

        $stmt = $conn->prepare("
            SELECT id, product_id
            FROM stock_history
            WHERE type = 'ORDER RETURN'
            AND note = ?
            AND quantity = ?
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");

        if (!$stmt) {
            throw new Exception('Stock history lookup failed: ' . $conn->error);
        }

        $stmt->bind_param(
            'sd',
            $history_note,
            $returned_quantity
        );

        $stmt->execute();
        $history = $stmt->get_result()->fetch_assoc();

        if ($history) {
            $history_id = intval($history['id']);
            $product_id = intval($history['product_id']);
        }
    }

    if ($product_id > 0) {
        $stmt = $conn->prepare("
            SELECT id, stock_qty
            FROM products
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$stmt) {
            throw new Exception('Product lookup failed: ' . $conn->error);
        }

        $stmt->bind_param('i', $product_id);
    }
    else {
        $product_name = $returned_item['product_name'] ?? '';

        $stmt = $conn->prepare("
            SELECT id, stock_qty
            FROM products
            WHERE name = ?
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");

        if (!$stmt) {
            throw new Exception('Product lookup failed: ' . $conn->error);
        }

        $stmt->bind_param('s', $product_name);
    }

    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        throw new Exception('The related inventory product was not found.');
    }

    $product_id = intval($product['id']);
    $current_stock = floatval($product['stock_qty'] ?? 0);

    if ($current_stock + 0.0001 < $returned_quantity) {
        throw new Exception(
            'The return cannot be undone because the related product stock is lower than the returned quantity.'
        );
    }

    $stmt = $conn->prepare("
        UPDATE products
        SET stock_qty = GREATEST(stock_qty - ?, 0)
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception('Product stock update failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'di',
        $returned_quantity,
        $product_id
    );

    if (!$stmt->execute()) {
        throw new Exception('Product stock update failed: ' . $stmt->error);
    }

    $original_quantity = floatval($order_item['quantity'] ?? 0);
    $current_returned = floatval($order_item['returned_quantity'] ?? 0);
    $new_returned = max(0, $current_returned - $returned_quantity);
    $new_status =
        $original_quantity > 0
        && $new_returned >= $original_quantity
            ? 'Returned'
            : 'Not Returned';

    $stmt = $conn->prepare("
        UPDATE order_items
        SET returned_quantity = ?,
            return_status = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception('Order return update failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'dsi',
        $new_returned,
        $new_status,
        $order_item_id
    );

    if (!$stmt->execute()) {
        throw new Exception('Order return update failed: ' . $stmt->error);
    }

    if ($history_id > 0) {
        $stmt = $conn->prepare("
            DELETE FROM stock_history
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception('Stock history delete failed: ' . $conn->error);
        }

        $stmt->bind_param('i', $history_id);

        if (!$stmt->execute()) {
            throw new Exception('Stock history delete failed: ' . $stmt->error);
        }
    }

    $stmt = $conn->prepare("
        DELETE FROM returned_inventory
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception('Return delete failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $return_id);

    if (!$stmt->execute()) {
        throw new Exception('Return delete failed: ' . $stmt->error);
    }

    $conn->commit();

    $_SESSION['success'] =
        'Return undone. Product stock and the original order return quantity were restored.';

}
catch (Throwable $error) {

    $conn->rollback();
    $_SESSION['error'] = $error->getMessage();

}

header('Location:returned.php');
exit();

