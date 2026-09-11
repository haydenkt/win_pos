<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('factory_view');

$lookup_table = 'material_types';
$lookup_title = 'Material types';
$lookup_single = 'Material type';
$lookup_icon = 'fa-layer-group';
$lookup_description = 'Manage the profile or frame types shown on factory products.';

include '_lookup.php';
?>
