<?php

function ensureV2PermissionCatalog(mysqli $conn): void
{
    $permissions = [
        'dashboard_view' => 'View dashboard',
        'customers_view' => 'View customers',
        'customers_manage' => 'Create, edit and delete customers',
        'invoices_view' => 'View and print invoices',
        'invoices_manage' => 'Create, edit and delete invoices',
        'orders_view' => 'View factory orders',
        'orders_manage' => 'Create and manage factory orders',
        'quotations_view' => 'View and print quotations',
        'quotations_manage' => 'Create quotations and convert them to orders or invoices',
        'factory_view' => 'View factory products, categories, glass and material types',
        'factory_manage' => 'Create and manage factory products and setup data',
        'products_view' => 'View inventory products',
        'products_manage' => 'Create and manage inventory products',
        'inventory_view' => 'View stock movements',
        'inventory_manage' => 'Manage stock quantities and movements',
        'returned_inventory_view' => 'View returned inventory',
        'payments_view' => 'View payments and receipts',
        'payments_manage' => 'Create and delete payments',
        'expenses_view' => 'View expenses',
        'expenses_manage' => 'Create, edit and delete expenses',
        'labour_view' => 'View workers, attendance, advances and savings',
        'labour_manage' => 'Manage workers, attendance, advances and savings',
        'site_survey_view' => 'View site surveys',
        'site_survey_manage' => 'Create and manage site surveys',
        'reports_view' => 'View and export reports',
        'settings_view' => 'View system and company settings',
        'settings_manage' => 'Change system, company and invoice settings',
        'users_view' => 'View users and roles',
        'users_manage' => 'Create and manage users, roles and permissions',
        'audit_view' => 'View the audit log',
        'backup_manage' => 'Download backups and use data tools',
    ];

    $stmt = $conn->prepare(
        'INSERT INTO permissions (permission_name, description) VALUES (?, ?) '
        . 'ON DUPLICATE KEY UPDATE description=VALUES(description)'
    );

    foreach ($permissions as $name => $description) {
        $stmt->bind_param('ss', $name, $description);
        $stmt->execute();
    }
}

function v2PermissionDisplayName(string $permissionName): string
{
    $names = [
        'dashboard_view' => 'View Dashboard',
        'customers_view' => 'View Customers',
        'customers_manage' => 'Manage Customers',
        'invoices_view' => 'View Invoices',
        'invoices_manage' => 'Manage Invoices',
        'orders_view' => 'View Orders',
        'orders_manage' => 'Manage Orders',
        'quotations_view' => 'View Quotations',
        'quotations_manage' => 'Manage Quotations',
        'factory_view' => 'View Factory Setup',
        'factory_manage' => 'Manage Factory Setup',
        'products_view' => 'View Products',
        'products_manage' => 'Manage Products',
        'inventory_view' => 'View Stock Movements',
        'inventory_manage' => 'Manage Inventory',
        'returned_inventory_view' => 'View Returned Inventory',
        'payments_view' => 'View Payments',
        'payments_manage' => 'Manage Payments',
        'expenses_view' => 'View Expenses',
        'expenses_manage' => 'Manage Expenses',
        'labour_view' => 'View Labour',
        'labour_manage' => 'Manage Labour',
        'site_survey_view' => 'View Site Surveys',
        'site_survey_manage' => 'Manage Site Surveys',
        'reports_view' => 'View Reports',
        'settings_view' => 'View Settings',
        'settings_manage' => 'Manage Settings',
        'users_view' => 'View Users & Roles',
        'users_manage' => 'Manage Users & Roles',
        'audit_view' => 'View Audit Log',
        'backup_manage' => 'Manage Backup & Reset',
    ];

    return $names[$permissionName] ?? ucwords(str_replace('_', ' ', $permissionName));
}
