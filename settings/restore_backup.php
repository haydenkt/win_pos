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

function restoreFailure(string $message): never
{
    $_SESSION['error'] = $message;
    header('Location:data_tools.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    restoreFailure('Invalid restore request.');
}
if (!winPosIsAdmin()) {
    restoreFailure('Only an administrator can restore a backup.');
}
if (empty($_SESSION['data_tools_csrf']) || !hash_equals($_SESSION['data_tools_csrf'], $_POST['csrf_token'] ?? '')) {
    restoreFailure('The restore request expired. Refresh the page and try again.');
}
if (trim((string) ($_POST['confirmation'] ?? '')) !== 'RESTORE BACKUP') {
    restoreFailure('Type RESTORE BACKUP exactly to confirm.');
}
if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    restoreFailure('Choose a valid backup file.');
}
if ((int) $_FILES['backup_file']['size'] <= 0 || (int) $_FILES['backup_file']['size'] > 50 * 1024 * 1024) {
    restoreFailure('The backup file must be between 1 byte and 50 MB.');
}

try {
    $json = file_get_contents($_FILES['backup_file']['tmp_name']);
    if ($json === false) {
        throw new RuntimeException('Could not read the uploaded backup.');
    }
    $backup = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($backup)) {
        throw new RuntimeException('The backup content is invalid.');
    }

    winPosValidateBackup($backup);
    $safetyBackup = winPosSaveSafetyBackup($conn, 'pre-restore');
    $restored = winPosRestoreBackup($conn, $backup);
    try {
        auditLog($conn, 'RESTORE', 'backup', $backup['created_at'] ?? null, 'Restored a Win POS V2 backup', null, ['rows' => array_sum($restored), 'safety_backup' => $safetyBackup]);
    } catch (Throwable $auditError) {
        // A logging problem must not report a successful restore as failed.
    }

    $_SESSION['last_safety_backup'] = $safetyBackup;
    $_SESSION['success'] = 'Backup restored successfully. ' . number_format(array_sum($restored)) . ' records were loaded.';
} catch (Throwable $error) {
    $_SESSION['error'] = 'Restore failed. No database changes were kept. ' . $error->getMessage();
}

header('Location:data_tools.php');
exit;
