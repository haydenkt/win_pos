<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('factory_view');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Factory product not found.';
    header('Location:index.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM factory_products WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error'] = 'Factory product not found.';
    header('Location:index.php');
    exit();
}

$page_title = 'Edit factory product';
$categories = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');
$materials = $conn->query('SELECT id, name FROM material_types ORDER BY name ASC');

include '../includes/header.php';
include '../includes/sidebar.php';
include '_form.php';
include '../includes/footer.php';
?>
