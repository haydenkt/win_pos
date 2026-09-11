<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/setting.php';

requirePermission('settings_view');

$theme_mode = getSetting($conn, 'system', 'theme_mode');
if (!in_array($theme_mode, ['light', 'dark', 'system'], true)) {
    $theme_mode = 'system';
}

if (empty($_SESSION['system_settings_token'])) {
    $_SESSION['system_settings_token'] = bin2hex(random_bytes(32));
}

$page_title = 'System settings';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$theme_options = [
    'light' => [
        'icon' => 'fa-sun',
        'title' => 'Light',
        'description' => 'Always use the bright interface.',
    ],
    'dark' => [
        'icon' => 'fa-moon',
        'title' => 'Dark',
        'description' => 'Always use the dark interface.',
    ],
    'system' => [
        'icon' => 'fa-desktop',
        'title' => 'System',
        'description' => "Follow each device's appearance setting.",
    ],
];
?>

<div class="container-fluid px-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="page-title mb-1">System settings</h1>
                <p class="page-subtitle mb-0">Choose how Version 2 looks across the application.</p>
            </div>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card settings-panel">
            <div class="card-header">
                <h2 class="h5 mb-1">Appearance</h2>
                <p class="text-muted small mb-0">This becomes the default theme for everyone using this installation.</p>
            </div>
            <div class="card-body p-4">
                <form action="save_system.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['system_settings_token']); ?>">

                    <fieldset>
                        <legend class="visually-hidden">Application theme</legend>
                        <div class="row g-3">
                            <?php foreach ($theme_options as $value => $option): ?>
                                <div class="col-12 col-md-4">
                                    <label class="theme-option h-100">
                                        <input
                                            type="radio"
                                            name="theme_mode"
                                            value="<?= htmlspecialchars($value); ?>"
                                            <?= $theme_mode === $value ? 'checked' : ''; ?>
                                        >
                                        <span class="theme-option-card h-100">
                                            <span class="theme-option-icon">
                                                <i class="fa <?= htmlspecialchars($option['icon']); ?>"></i>
                                            </span>
                                            <strong><?= htmlspecialchars($option['title']); ?></strong>
                                            <small><?= htmlspecialchars($option['description']); ?></small>
                                            <span class="theme-option-check" aria-hidden="true">
                                                <i class="fa fa-check"></i>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-check-circle me-2"></i>Save appearance
                        </button>
                    </div>
                </form>
            </div>
        </div>
</div>

<script>
(() => {
    const root = document.documentElement;
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

    document.querySelectorAll('input[name="theme_mode"]').forEach((input) => {
        input.addEventListener('change', () => {
            const mode = input.value;
            root.dataset.themeMode = mode;
            root.dataset.theme = mode === 'dark' || (mode === 'system' && systemTheme.matches)
                ? 'dark'
                : 'light';

            const themeColor = document.querySelector('meta[name="theme-color"]');
            if (themeColor) {
                themeColor.content = root.dataset.theme === 'dark' ? '#0b1220' : '#f7f9fc';
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
