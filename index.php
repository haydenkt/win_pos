<?php

session_start();

if (isset($_SESSION['user'])) {
    header('Location:dashboard.php');
    exit();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/setting.php';

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

    <title>Sign in · Win POS</title>

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
</head>
<body class="login-page">

    <main class="login-shell">

        <section class="login-intro">
            <div class="login-brand">
                <span class="brand-mark"><i class="fa fa-layer-group"></i></span>
                <strong>Win POS</strong>
            </div>

            <div>
                <div class="page-kicker text-info">Business workspace</div>
                <h1>Keep every customer, sale and payment moving.</h1>
                <p>
                    A focused workspace for daily operations—without the clutter.
                </p>
            </div>

            <div class="login-feature-list">
                <span><i class="fa fa-check"></i> Customer and site-survey records</span>
                <span><i class="fa fa-check"></i> Invoices, orders and payments</span>
                <span><i class="fa fa-check"></i> Products and returned inventory</span>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <div class="mb-4">
                    <div class="page-kicker">Welcome back</div>
                    <h2>Sign in to Win POS</h2>
                    <p class="text-muted mb-0">Use your existing account.</p>
                </div>

                <form action="/auth/login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="fa fa-user"></i>
                            <input
                                id="username"
                                class="form-control"
                                name="username"
                                autocomplete="username"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fa fa-lock"></i>
                            <input
                                id="password"
                                class="form-control"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >
                        </div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">
                        Sign in
                        <i class="fa fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </section>

    </main>

    <script>
        (() => {
            const root = document.documentElement;
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

            const applyTheme = () => {
                if ((root.dataset.themeMode || 'system') === 'system') {
                    root.dataset.theme = systemTheme.matches ? 'dark' : 'light';
                }

                const themeColor = document.querySelector('meta[name="theme-color"]');
                if (themeColor) {
                    themeColor.content = root.dataset.theme === 'dark' ? '#0b1220' : '#f7f9fc';
                }
            };

            if (systemTheme.addEventListener) {
                systemTheme.addEventListener('change', applyTheme);
            } else {
                systemTheme.addListener(applyTheme);
            }

            applyTheme();
        })();
    </script>
</body>
</html>
