<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location:../index.php'); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/schema.php';
requirePermission('factory_manage');
ensureMaterialCalculationSchema($conn);

$id = (int) ($_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0 || empty($_SESSION['material_calculation_csrf']) || !hash_equals($_SESSION['material_calculation_csrf'], $_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'The delete request expired.';
    header('Location:setup.php');
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare('SELECT name FROM material_calculation_templates WHERE id=? FOR UPDATE');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    if (!$template) throw new RuntimeException('Product template not found.');
    $stmt = $conn->prepare('DELETE FROM material_calculation_formulas WHERE template_id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt = $conn->prepare('DELETE FROM material_calculation_templates WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    auditLog($conn, 'DELETE', 'material_calculation_template', $id, 'Deleted material calculation template ' . $template['name'], $template, null);
    $conn->commit();
    $_SESSION['success'] = 'Product template deleted.';
} catch (Throwable $error) {
    $conn->rollback();
    $_SESSION['error'] = 'Template could not be deleted: ' . $error->getMessage();
}
header('Location:setup.php');
exit;
