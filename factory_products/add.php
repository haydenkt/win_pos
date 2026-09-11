<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('factory_view');

$page_title = 'Add factory product';
$product = [];
$categories = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');
$materials = $conn->query('SELECT id, name FROM material_types ORDER BY name ASC');

include '../includes/header.php';
include '../includes/sidebar.php';
include '_form.php';
include '../includes/footer.php';
?>
