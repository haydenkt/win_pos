<?php

function winPosBackupTables(): array
{
    return [
        'categories', 'customers', 'expenses', 'factory_products', 'factory_returns',
        'glass_types', 'invoice_counter', 'invoice_items', 'invoices', 'labour_records',
        'labour_transactions', 'material_types', 'order_items', 'orders', 'payments',
        'products', 'quotation_items', 'quotations', 'return_items', 'returned_inventory',
        'site_surveys', 'stock_history', 'survey_measurements', 'workers'
    ];
}
