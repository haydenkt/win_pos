<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('factory_view');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location:index.php');
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$product_name = trim((string) ($_POST['product_name'] ?? ''));
$category_id = (int) ($_POST['category_id'] ?? 0);
$material_type_id = (int) ($_POST['material_type_id'] ?? 0);
$calculation_type = (string) ($_POST['calculation_type'] ?? 'SQFT');
$default_price = max(0, (float) ($_POST['default_price'] ?? 0));
$status = (string) ($_POST['status'] ?? 'Active');
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($id <= 0 || $product_name === '' || !in_array($calculation_type, ['SQFT', 'MANUAL'], true) || !in_array($status, ['Active', 'Inactive'], true)) {
    $_SESSION['error'] = 'Please enter valid factory product details.';
    header('Location:' . ($id > 0 ? 'edit.php?id=' . $id : 'index.php'));
    exit();
}

$sql = "
    UPDATE factory_products
    SET category_id = NULLIF(?, 0),
        material_type_id = NULLIF(?, 0),
        product_name = ?,
        calculation_type = ?,
        default_price = ?,
        status = ?,
        notes = ?
    WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iissdssi', $category_id, $material_type_id, $product_name, $calculation_type, $default_price, $status, $notes, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Factory product updated.';
    header('Location:index.php');
} else {
    $_SESSION['error'] = 'The factory product could not be updated.';
    header('Location:edit.php?id=' . $id);
}

$stmt->close();
exit();
?>
