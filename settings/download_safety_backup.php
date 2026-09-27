<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/data_tools_support.php';
requirePermission('backup_manage');

if (!winPosIsAdmin()) {
    http_response_code(403);
    die('Administrator access required.');
}

$filename = basename((string) ($_GET['file'] ?? ''));
$path = winPosPrivateBackupPath($filename);
if ($path === null) {
    http_response_code(404);
    die('Safety backup not found.');
}

$contents = file_get_contents($path);
if ($contents === false || !str_starts_with($contents, WIN_POS_PRIVATE_BACKUP_PREFIX)) {
    http_response_code(500);
    die('Safety backup is unreadable.');
}

$downloadName = preg_replace('/\.backup\.php$/', '.json', $filename);
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
echo substr($contents, strlen(WIN_POS_PRIVATE_BACKUP_PREFIX));
exit;
