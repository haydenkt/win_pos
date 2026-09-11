<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';

requirePermission('settings_view');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: system.php');
    exit;
}

$submitted_token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['system_settings_token'] ?? '';

if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    header('Location: system.php');
    exit;
}

$theme_mode = $_POST['theme_mode'] ?? '';
if (!in_array($theme_mode, ['light', 'dark', 'system'], true)) {
    $_SESSION['error'] = 'Please choose a valid appearance.';
    header('Location: system.php');
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO settings (setting_group, setting_key, setting_value)
     VALUES ('system', 'theme_mode', ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);
$stmt->bind_param('s', $theme_mode);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Appearance updated successfully.';
} else {
    $_SESSION['error'] = 'The appearance could not be saved. Please try again.';
}

$stmt->close();
header('Location: system.php');
exit;
