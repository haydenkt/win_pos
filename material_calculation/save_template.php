<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location:../index.php'); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/schema.php';
requirePermission('factory_manage');
ensureMaterialCalculationSchema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['material_calculation_csrf']) || !hash_equals($_SESSION['material_calculation_csrf'], $_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'The request expired.';
    header('Location:setup.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
$materialNames = $_POST['material_name'] ?? [];

if ($name === '' || !is_array($materialNames)) {
    $_SESSION['error'] = 'Product name and at least one material are required.';
    header('Location:setup.php' . ($id > 0 ? '?id=' . $id : ''));
    exit;
}

$formulas = [];
foreach ($materialNames as $index => $materialName) {
    $materialName = trim((string) $materialName);
    if ($materialName === '') continue;
    $headingRule = (string) ($_POST['heading_rule'][$index] ?? 'overall');
    if (!in_array($headingRule, ['overall', 'lower', 'lower_plus_perimeter', 'crossbar_only'], true)) $headingRule = 'overall';
    $formulas[] = [
        $materialName,
        trim((string) ($_POST['material_name_en'][$index] ?? '')),
        substr(trim((string) ($_POST['unit_label'][$index] ?? 'ft')), 0, 20) ?: 'ft',
        (float) ($_POST['width_factor'][$index] ?? 0),
        (float) ($_POST['height_factor'][$index] ?? 0),
        (float) ($_POST['area_factor'][$index] ?? 0),
        (float) ($_POST['fixed_amount'][$index] ?? 0),
        max(0, (float) ($_POST['usable_length_per_piece'][$index] ?? 0)),
        $headingRule,
    ];
}

if (!$formulas) {
    $_SESSION['error'] = 'Add at least one material formula.';
    header('Location:setup.php' . ($id > 0 ? '?id=' . $id : ''));
    exit;
}

$conn->begin_transaction();
try {
    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE material_calculation_templates SET name=?, description=?, status=? WHERE id=?');
        $stmt->bind_param('sssi', $name, $description, $status, $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $check = $conn->prepare('SELECT id FROM material_calculation_templates WHERE id=?');
            $check->bind_param('i', $id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) throw new RuntimeException('Product template not found.');
        }
        $stmt = $conn->prepare('DELETE FROM material_calculation_formulas WHERE template_id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $action = 'UPDATE';
    } else {
        $stmt = $conn->prepare('INSERT INTO material_calculation_templates (name, description, status) VALUES (?,?,?)');
        $stmt->bind_param('sss', $name, $description, $status);
        $stmt->execute();
        $id = $conn->insert_id;
        $action = 'CREATE';
    }

    $stmt = $conn->prepare('INSERT INTO material_calculation_formulas (template_id, material_name, material_name_en, unit_label, width_factor, height_factor, area_factor, fixed_amount, usable_length_per_piece, heading_rule, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($formulas as $sort => $formula) {
        [$materialName, $englishName, $unit, $width, $height, $area, $fixed, $usablePerPiece, $headingRule] = $formula;
        $sortOrder = $sort + 1;
        $stmt->bind_param('isssdddddsi', $id, $materialName, $englishName, $unit, $width, $height, $area, $fixed, $usablePerPiece, $headingRule, $sortOrder);
        $stmt->execute();
    }

    auditLog($conn, $action, 'material_calculation_template', $id, ucfirst(strtolower($action)) . ' material calculation template ' . $name, null, ['name' => $name, 'formula_count' => count($formulas)]);
    $conn->commit();
    $_SESSION['success'] = 'Material calculation template saved.';
    header('Location:setup.php?id=' . $id);
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    $_SESSION['error'] = 'Template could not be saved: ' . $error->getMessage();
    header('Location:setup.php' . ($id > 0 ? '?id=' . $id : ''));
    exit;
}
