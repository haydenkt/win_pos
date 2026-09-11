<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('factory_view');

$lookup_table = 'categories';
$lookup_title = 'Factory categories';
$lookup_single = 'Category';
$lookup_icon = 'fa-list';
$lookup_description = 'Organize factory products for faster order entry.';

include '_lookup.php';
?>
