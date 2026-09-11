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
    header('Location:add.php');
    exit();
}

$product_name = trim((string) ($_POST['product_name'] ?? ''));
$category_id = (int) ($_POST['category_id'] ?? 0);
$material_type_id = (int) ($_POST['material_type_id'] ?? 0);
$calculation_type = (string) ($_POST['calculation_type'] ?? 'SQFT');
$default_price = max(0, (float) ($_POST['default_price'] ?? 0));
$status = (string) ($_POST['status'] ?? 'Active');
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($product_name === '' || !in_array($calculation_type, ['SQFT', 'MANUAL'], true) || !in_array($status, ['Active', 'Inactive'], true)) {
    $_SESSION['error'] = 'Please enter valid factory product details.';
    header('Location:add.php');
    exit();
}

$sql = "
    INSERT INTO factory_products
        (category_id, material_type_id, product_name, calculation_type, default_price, status, notes)
    VALUES
        (NULLIF(?, 0), NULLIF(?, 0), ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iissdss', $category_id, $material_type_id, $product_name, $calculation_type, $default_price, $status, $notes);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Factory product added. It is now available in sqft orders and invoices.';
    header('Location:index.php');
} else {
    $_SESSION['error'] = 'The factory product could not be added.';
    header('Location:add.php');
}

$stmt->close();
exit();
?>
