<?php

include_once __DIR__ . '/setting.php';
require_once __DIR__ . '/permissions.php';

$company_name = trim((string) getSetting($conn, 'company', 'company_name'));
$company_logo = trim((string) getSetting($conn, 'company', 'logo'));

if ($company_name === '') {
    $company_name = 'Win POS';
}

$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (empty($page_title)) {
    $section = trim(explode('/', trim($current_path, '/'))[0] ?? '');

    $page_titles = [
        'customers' => 'Customers',
        'site_survey' => 'Site surveys',
        'invoices' => 'Invoices',
        'orders' => 'Orders',
        'quotations' => 'Quotations',
        'factory_products' => 'Factory products',
        'glass' => 'Glass',
        'products' => 'Products',
        'inventory' => 'Returned inventory',
        'expenses' => 'Expenses',
        'audit' => 'Audit log',
        'payments' => 'Payments',
        'labour' => 'Labour',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'dashboard.php' => 'Dashboard',
    ];

    $page_title = $page_titles[$section] ?? 'Win POS';
}

function navActive(array $paths): string
{
    global $current_path;

    foreach ($paths as $path) {
        if ($current_path === $path || str_starts_with($current_path, rtrim($path, '/') . '/')) {
            return ' active';
        }
    }

    return '';
}

$can_dashboard = hasPermission('dashboard_view');
$can_customers = hasPermission('customers_view');
$can_site_survey = hasPermission('site_survey_view');
$can_invoices = hasPermission('invoices_view');
$can_orders = hasPermission('orders_view');
$can_quotations = hasPermission('quotations_view');
$can_factory = hasPermission('factory_view');
$can_products = hasPermission('products_view');
$can_inventory = hasPermission('inventory_view');
$can_returned_inventory = hasPermission('returned_inventory_view');
$can_payments = hasPermission('payments_view');
$can_expenses = hasPermission('expenses_view');
$can_labour = hasPermission('labour_view');
$can_reports = hasPermission('reports_view');
$can_settings = hasPermission('settings_view');
$can_users = hasPermission('users_view');
$can_audit = hasPermission('audit_view');
$can_backup = hasPermission('backup_manage');

$user_name = trim((string) (
    $_SESSION['full_name']
    ?? $_SESSION['user']
    ?? 'Account'
));

?>

<button
    type="button"
    class="mobile-menu-button"
    id="menuBtn"
    aria-label="Open navigation"
    aria-controls="sidebar"
    aria-expanded="false"
>
    <i class="fa fa-bars"></i>
</button>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar" aria-label="Primary navigation">

    <div class="sidebar-brand">
        <div class="brand-mark">
            <?php if (
                $company_logo !== ''
                && file_exists(__DIR__ . '/../uploads/logo/' . $company_logo)
            ) { ?>
                <img
                    src="/uploads/logo/<?=htmlspecialchars($company_logo);?>"
                    alt=""
                >
            <?php } else { ?>
                <i class="fa fa-layer-group"></i>
            <?php } ?>
        </div>

        <div class="brand-copy menu-text">
            <strong><?=htmlspecialchars($company_name);?></strong>
            <span>Business workspace</span>
        </div>

        <button
            type="button"
            class="sidebar-close"
            id="sidebarCloseBtn"
            aria-label="Close navigation"
        >
            <i class="fa fa-times"></i>
        </button>
    </div>

    <nav class="sidebar-nav">

        <?php if ($can_dashboard) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">Overview</div>
                <a class="nav-item<?=navActive(['/dashboard.php']);?>" href="/dashboard.php">
                    <i class="fa fa-chart-pie"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </div>
        <?php } ?>

        <?php if ($can_customers || $can_site_survey) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">Customers & work</div>

                <?php if ($can_customers) { ?>
                    <a class="nav-item<?=navActive(['/customers']);?>" href="/customers/index.php">
                        <i class="fa fa-users"></i>
                        <span class="menu-text">Customers</span>
                    </a>
                <?php } ?>

                <?php if ($can_site_survey) { ?>
                    <a class="nav-item<?=navActive(['/site_survey']);?>" href="/site_survey/index.php">
                        <i class="fa fa-ruler-combined"></i>
                        <span class="menu-text">Site surveys</span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if ($can_invoices || $can_orders || $can_quotations) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">Sales</div>

                <?php if ($can_invoices) { ?>
                    <a class="nav-item<?=navActive(['/invoices']);?>" href="/invoices/index.php">
                        <i class="fa fa-receipt"></i>
                        <span class="menu-text">Invoices</span>
                    </a>
                <?php } ?>

                <?php if ($can_orders) { ?>
                    <a class="nav-item<?=navActive(['/orders']);?>" href="/orders/index.php">
                        <i class="fa fa-clipboard-list"></i>
                        <span class="menu-text">Orders</span>
                    </a>
                <?php } ?>

                <?php if ($can_quotations) { ?>
                    <a class="nav-item<?=navActive(['/quotations']);?>" href="/quotations/index.php">
                        <i class="fa fa-file-signature"></i>
                        <span class="menu-text">Quotations</span>
                    </a>
                <?php } ?>

            </div>
        <?php } ?>

        <?php if ($can_factory) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">Factory</div>

                <a class="nav-item<?=navActive(['/factory_products/index.php', '/factory_products/add.php', '/factory_products/edit.php']);?>" href="/factory_products/index.php">
                    <i class="fa fa-ruler-combined"></i>
                    <span class="menu-text">Factory products</span>
                </a>

                <a class="nav-item<?=navActive(['/factory_products/categories.php']);?>" href="/factory_products/categories.php">
                    <i class="fa fa-list"></i>
                    <span class="menu-text">Categories</span>
                </a>

                <a class="nav-item<?=navActive(['/glass']);?>" href="/glass/index.php">
                    <i class="fa fa-window-maximize"></i>
                    <span class="menu-text">Glass</span>
                </a>

                <a class="nav-item<?=navActive(['/factory_products/material_type.php']);?>" href="/factory_products/material_type.php">
                    <i class="fa fa-layer-group"></i>
                    <span class="menu-text">Material types</span>
                </a>
            </div>
        <?php } ?>

        <?php if ($can_inventory || $can_products || $can_returned_inventory) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">Inventory</div>

                <?php if ($can_products) { ?>
                    <a class="nav-item<?=navActive(['/products']);?>" href="/products/index.php">
                        <i class="fa fa-box"></i>
                        <span class="menu-text">Products</span>
                    </a>
                <?php } ?>

                <?php if ($can_returned_inventory) { ?>
                    <a class="nav-item<?=navActive(['/inventory']);?>" href="/inventory/returned.php">
                        <i class="fa fa-rotate-left"></i>
                        <span class="menu-text">Returned inventory</span>
                    </a>
                <?php } ?>

                <?php if ($can_inventory) { ?>
                    <a class="nav-item<?=navActive(['/inventory/history.php']);?>" href="/inventory/history.php">
                        <i class="fa fa-clock-rotate-left"></i>
                        <span class="menu-text">Stock movements</span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if ($can_payments || $can_expenses || $can_labour) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">Finance & people</div>

                <?php if ($can_payments) { ?>
                    <a class="nav-item<?=navActive(['/payments']);?>" href="/payments/index.php">
                        <i class="fa fa-wallet"></i>
                        <span class="menu-text">Payments</span>
                    </a>
                <?php } ?>

                <?php if ($can_expenses) { ?>
                    <a class="nav-item<?=navActive(['/expenses']);?>" href="/expenses/index.php">
                        <i class="fa fa-receipt"></i>
                        <span class="menu-text">Expenses</span>
                    </a>
                <?php } ?>

                <?php if ($can_labour) { ?>
                    <a class="nav-item<?=navActive(['/labour/index.php']);?>" href="/labour/index.php">
                        <i class="fa fa-gauge-high"></i>
                        <span class="menu-text">Labour overview</span>
                    </a>

                    <a class="nav-item<?=navActive(['/labour/workers']);?>" href="/labour/workers/index.php">
                        <i class="fa fa-user-group"></i>
                        <span class="menu-text">Workers</span>
                    </a>

                    <a class="nav-item<?=navActive(['/labour/records']);?>" href="/labour/records/index.php">
                        <i class="fa fa-calendar-check"></i>
                        <span class="menu-text">Daily records</span>
                    </a>

                    <a class="nav-item<?=navActive(['/labour/transactions']);?>" href="/labour/transactions/index.php">
                        <i class="fa fa-money-bill-transfer"></i>
                        <span class="menu-text">Transactions</span>
                    </a>

                    <a class="nav-item<?=navActive(['/labour/summary']);?>" href="/labour/summary/index.php">
                        <i class="fa fa-chart-column"></i>
                        <span class="menu-text">Labour summary</span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if ($can_reports) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">Insights</div>
                <a class="nav-item<?=navActive(['/reports']);?>" href="/reports/index.php">
                    <i class="fa fa-chart-line"></i>
                    <span class="menu-text">Reports</span>
                </a>
            </div>
        <?php } ?>

        <?php if ($can_settings || $can_users) { ?>
            <div class="nav-group">
                <div class="nav-label menu-text">System</div>

                <?php if ($can_settings) { ?>
                    <a class="nav-item<?=navActive(['/settings/index.php', '/settings/company.php', '/settings/invoice.php', '/settings/system.php']);?>" href="/settings/index.php">
                        <i class="fa fa-sliders"></i>
                        <span class="menu-text">Settings</span>
                    </a>
                <?php } ?>

                <?php if ($can_users) { ?>
                    <a class="nav-item<?=navActive(['/settings/users', '/settings/roles']);?>" href="/settings/users/index.php">
                        <i class="fa fa-user-shield"></i>
                        <span class="menu-text">Users & roles</span>
                    </a>
                <?php } ?>

                <?php if ($can_audit) { ?>
                    <a class="nav-item<?=navActive(['/audit']);?>" href="/audit/index.php">
                        <i class="fa fa-shield-halved"></i>
                        <span class="menu-text">Audit log</span>
                    </a>
                <?php } ?>

                <?php if ($can_backup) { ?>
                    <a class="nav-item<?=navActive(['/settings/data_tools.php']);?>" href="/settings/data_tools.php">
                        <i class="fa fa-database"></i>
                        <span class="menu-text">Backup & reset</span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>

    </nav>

    <div class="sidebar-footer">
        <a class="account-card" href="/settings/users/profile.php">
            <span class="account-avatar">
                <?=htmlspecialchars(strtoupper(substr($user_name, 0, 1)));?>
            </span>
            <span class="account-copy menu-text">
                <strong><?=htmlspecialchars($user_name);?></strong>
                <small>View profile</small>
            </span>
        </a>

        <a class="logout-link" href="/auth/logout.php" title="Log out">
            <i class="fa fa-arrow-right-from-bracket"></i>
            <span class="menu-text">Log out</span>
        </a>
    </div>

    <button
        type="button"
        class="sidebar-collapse-button"
        id="sidebarCollapseBtn"
        aria-label="Collapse navigation"
        title="Collapse navigation"
    >
        <i class="fa fa-chevron-left"></i>
    </button>

</aside>

<main class="content">
    <header class="app-topbar">
        <div>
            <div class="topbar-eyebrow">Workspace</div>
            <div class="topbar-title"><?=htmlspecialchars($page_title ?? 'Dashboard');?></div>
        </div>

        <div class="topbar-actions">
            <?php if ($can_invoices) { ?>
                <a
                    href="/invoices/add.php"
                    class="btn btn-new-invoice"
                    aria-label="Create new invoice"
                    title="New invoice"
                >
                    <i class="fa fa-file-circle-plus" aria-hidden="true"></i>
                    <span>New invoice</span>
                </a>
            <?php } ?>
        </div>
    </header>

    <div class="content-body">
