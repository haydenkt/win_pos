<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/data_tools_support.php';
requirePermission('backup_manage');

function clearDemoFailure(string $message): never
{
    $_SESSION['error'] = $message;
    header('Location:data_tools.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    clearDemoFailure('Invalid clear-demo request.');
}
if (!winPosIsAdmin()) {
    clearDemoFailure('Only an administrator can clear demo data.');
}
if (empty($_SESSION['data_tools_csrf']) || !hash_equals($_SESSION['data_tools_csrf'], $_POST['csrf_token'] ?? '')) {
    clearDemoFailure('The clear-demo request expired. Refresh the page and try again.');
}
if (trim((string) ($_POST['confirmation'] ?? '')) !== 'CLEAR DEMO') {
    clearDemoFailure('Type CLEAR DEMO exactly to confirm.');
}

try {
    $preview = winPosDemoPreview($conn);
    if (array_sum($preview) === 0) {
        throw new RuntimeException('No marked demo data was found.');
    }

    $safetyBackup = winPosSaveSafetyBackup($conn, 'pre-clear-demo');
    $deleted = winPosClearDemoData($conn);
    try {
        auditLog($conn, 'DELETE', 'demo_data', null, 'Cleared marked demo data', $preview, ['deleted' => $deleted, 'safety_backup' => $safetyBackup]);
    } catch (Throwable $auditError) {
        // A logging problem must not report a successful clear operation as failed.
    }

    $_SESSION['last_safety_backup'] = $safetyBackup;
    $_SESSION['success'] = 'Demo data cleared safely. ' . number_format(array_sum($deleted)) . ' marked records were removed.';
} catch (Throwable $error) {
    $_SESSION['error'] = 'Clear Demo failed. No partial changes were kept. ' . $error->getMessage();
}

header('Location:data_tools.php');
exit;
