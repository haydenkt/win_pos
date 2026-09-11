<?php

if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
}

require_once __DIR__ . '/setting.php';

$app_theme_mode = getSetting($conn, 'system', 'theme_mode');
if (!in_array($app_theme_mode, ['light', 'dark', 'system'], true)) {
    $app_theme_mode = 'system';
}

?>
<!doctype html>
<html lang="en" data-theme-mode="<?= htmlspecialchars($app_theme_mode); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f9fc">

    <title><?=htmlspecialchars($page_title ?? 'Win POS');?></title>

    <script>
        (() => {
            const root = document.documentElement;
            const mode = root.dataset.themeMode || 'system';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            root.dataset.theme = mode === 'dark' || (mode === 'system' && prefersDark)
                ? 'dark'
                : 'light';
        })();
    </script>

    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/fontawesome.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/solid.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <script>
        if (localStorage.getItem('winposSidebar') === 'collapsed') {
            document.documentElement.classList.add('sidebar-pre-collapsed');
        }
    </script>
</head>
<body>
