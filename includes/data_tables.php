<?php

function winPosBackupTables(): array
{
    return [
        'categories', 'customers', 'glass_types', 'material_types', 'materials',
        'products', 'workers', 'factory_products', 'orders', 'order_items',
        'production', 'material_usage', 'invoices', 'invoice_items', 'payments',
        'returns', 'return_items', 'returned_inventory', 'factory_returns',
        'quotations', 'quotation_items', 'site_surveys', 'survey_measurements',
        'labour_records', 'labour_transactions', 'expenses', 'stock_history',
        'invoice_counter'
    ];
}
