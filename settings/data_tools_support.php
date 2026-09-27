<?php

require_once __DIR__ . '/../includes/data_tables.php';

const WIN_POS_PRIVATE_BACKUP_PREFIX = "<?php exit; ?>\n";

function winPosIsAdmin(): bool
{
    $permissionData = loadUserPermissions();
    return !empty($permissionData['is_admin']);
}

function winPosCreateBackupPayload(mysqli $conn): array
{
    $data = [
        'format' => 'win-pos-backup',
        'version' => 2,
        'created_at' => date(DATE_ATOM),
        'tables' => [],
    ];

    foreach (winPosBackupTables() as $table) {
        $data['tables'][$table] = [];
        $result = $conn->query('SELECT * FROM `' . $table . '`');
        while ($row = $result->fetch_assoc()) {
            $data['tables'][$table][] = $row;
        }
    }

    return $data;
}

function winPosSaveSafetyBackup(mysqli $conn, string $reason): string
{
    $directory = dirname(__DIR__) . '/storage/private_backups';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the private backup directory.');
    }

    $safeReason = preg_replace('/[^a-z0-9-]+/i', '-', strtolower($reason));
    $filename = $safeReason . '-' . date('Y-m-d-His') . '-' . bin2hex(random_bytes(3)) . '.backup.php';
    $payload = json_encode(winPosCreateBackupPayload($conn), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    if (file_put_contents($directory . '/' . $filename, WIN_POS_PRIVATE_BACKUP_PREFIX . $payload, LOCK_EX) === false) {
        throw new RuntimeException('Could not write the automatic safety backup.');
    }

    $files = glob($directory . '/*.backup.php') ?: [];
    usort($files, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
    foreach (array_slice($files, 10) as $oldFile) {
        @unlink($oldFile);
    }

    return $filename;
}

function winPosPrivateBackupPath(string $filename): ?string
{
    if (!preg_match('/^[a-z0-9-]+\.backup\.php$/i', $filename)) {
        return null;
    }

    $path = dirname(__DIR__) . '/storage/private_backups/' . $filename;
    return is_file($path) ? $path : null;
}

function winPosValidateBackup(array $backup): void
{
    if (($backup['format'] ?? '') !== 'win-pos-backup') {
        throw new RuntimeException('This is not a Win POS backup file.');
    }

    if ((int) ($backup['version'] ?? 0) < 2 || !isset($backup['tables']) || !is_array($backup['tables'])) {
        throw new RuntimeException('This backup is outdated or incomplete. Download a new V2 backup first.');
    }

    $missing = array_values(array_diff(winPosBackupTables(), array_keys($backup['tables'])));
    if ($missing) {
        throw new RuntimeException('Backup is missing required tables: ' . implode(', ', $missing));
    }
}

function winPosRestoreBackup(mysqli $conn, array $backup, bool $commitChanges = true): array
{
    winPosValidateBackup($backup);
    $tables = winPosBackupTables();
    $restored = [];
    $columns = [];

    foreach ($tables as $table) {
        $columns[$table] = [];
        $result = $conn->query('SHOW COLUMNS FROM `' . $table . '`');
        while ($column = $result->fetch_assoc()) {
            $columns[$table][$column['Field']] = true;
        }
    }

    $conn->begin_transaction();
    try {
        foreach (array_reverse($tables) as $table) {
            $conn->query('DELETE FROM `' . $table . '`');
        }

        foreach ($tables as $table) {
            $rows = $backup['tables'][$table];
            if (!is_array($rows)) {
                throw new RuntimeException('Invalid table data for ' . $table . '.');
            }

            $restored[$table] = 0;
            foreach ($rows as $row) {
                if (!is_array($row) || array_is_list($row) || !$row) {
                    throw new RuntimeException('Invalid row data in ' . $table . '.');
                }

                $unknownColumns = array_diff(array_keys($row), array_keys($columns[$table]));
                if ($unknownColumns) {
                    throw new RuntimeException('Unknown columns in ' . $table . ': ' . implode(', ', $unknownColumns));
                }

                $columnNames = array_keys($row);
                $quotedColumns = array_map(static fn(string $name): string => '`' . $name . '`', $columnNames);
                $placeholders = implode(',', array_fill(0, count($columnNames), '?'));
                $stmt = $conn->prepare(
                    'INSERT INTO `' . $table . '` (' . implode(',', $quotedColumns) . ') VALUES (' . $placeholders . ')'
                );
                $stmt->execute(array_values($row));
                $stmt->close();
                $restored[$table]++;
            }
        }

        if ($commitChanges) {
            $conn->commit();
        } else {
            $conn->rollback();
        }
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }

    return $restored;
}

function winPosSelectIds(mysqli $conn, string $sql): array
{
    $ids = [];
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int) $row['id'];
    }
    return array_values(array_unique(array_filter($ids)));
}

function winPosIdList(array $ids): string
{
    return $ids ? implode(',', array_map('intval', $ids)) : '0';
}

function winPosDemoDataSet(mysqli $conn): array
{
    $data = [];
    $data['customers'] = winPosSelectIds($conn, "SELECT id FROM customers WHERE name LIKE 'Demo - %' OR address LIKE 'Training address %'");
    $data['invoices'] = winPosSelectIds($conn, "SELECT id FROM invoices WHERE notes LIKE 'DEMO-SEED-%'");
    $data['orders'] = winPosSelectIds($conn, "SELECT id FROM orders WHERE notes LIKE 'DEMO-SEED-%'");
    $data['returns'] = winPosSelectIds($conn, "SELECT id FROM returns WHERE reason LIKE 'DEMO-SEED-%'");
    $data['quotations'] = winPosSelectIds($conn, "SELECT id FROM quotations WHERE notes LIKE 'DEMO-SEED-%'");
    $data['site_surveys'] = winPosSelectIds($conn, "SELECT id FROM site_surveys WHERE notes LIKE 'DEMO-SEED-%'");
    $data['expenses'] = winPosSelectIds($conn, "SELECT id FROM expenses WHERE description LIKE 'DEMO-SEED-%' OR supplier LIKE 'Demo - %'");
    $data['workers'] = winPosSelectIds($conn, "SELECT id FROM workers WHERE name LIKE 'Demo - %'");

    $data['invoice_items'] = winPosSelectIds($conn, 'SELECT id FROM invoice_items WHERE invoice_id IN (' . winPosIdList($data['invoices']) . ')');
    $data['order_items'] = winPosSelectIds($conn, 'SELECT id FROM order_items WHERE order_id IN (' . winPosIdList($data['orders']) . ')');
    $data['payments'] = winPosSelectIds($conn, "SELECT id FROM payments WHERE invoice_id IN (" . winPosIdList($data['invoices']) . ") OR note LIKE 'DEMO-SEED-%'");
    $data['return_items'] = winPosSelectIds($conn, 'SELECT id FROM return_items WHERE return_id IN (' . winPosIdList($data['returns']) . ')');
    $data['returned_inventory'] = winPosSelectIds($conn, 'SELECT id FROM returned_inventory WHERE invoice_id IN (' . winPosIdList($data['invoices']) . ') OR sold_invoice_id IN (' . winPosIdList($data['invoices']) . ') OR order_item_id IN (' . winPosIdList($data['order_items']) . ')');
    $data['factory_returns'] = winPosSelectIds($conn, 'SELECT id FROM factory_returns WHERE original_invoice_id IN (' . winPosIdList($data['invoices']) . ') OR sold_invoice_id IN (' . winPosIdList($data['invoices']) . ') OR original_order_id IN (' . winPosIdList($data['orders']) . ')');
    $data['quotation_items'] = winPosSelectIds($conn, 'SELECT id FROM quotation_items WHERE quotation_id IN (' . winPosIdList($data['quotations']) . ')');
    $data['survey_measurements'] = winPosSelectIds($conn, 'SELECT id FROM survey_measurements WHERE survey_id IN (' . winPosIdList($data['site_surveys']) . ')');
    $data['labour_records'] = winPosSelectIds($conn, "SELECT id FROM labour_records WHERE worker_id IN (" . winPosIdList($data['workers']) . ") OR note LIKE 'DEMO-SEED-%'");
    $data['labour_transactions'] = winPosSelectIds($conn, "SELECT id FROM labour_transactions WHERE worker_id IN (" . winPosIdList($data['workers']) . ") OR note LIKE 'DEMO-SEED-%'");
    $data['stock_history'] = winPosSelectIds($conn, "SELECT id FROM stock_history WHERE (source_type='RETURN' AND source_id IN (" . winPosIdList($data['returns']) . ")) OR note LIKE 'DEMO-SEED-%'");

    return $data;
}

function winPosDemoPreview(mysqli $conn): array
{
    $data = winPosDemoDataSet($conn);
    return array_map('count', $data);
}

function winPosDeleteIds(mysqli $conn, string $table, array $ids): int
{
    if (!$ids) {
        return 0;
    }
    $conn->query('DELETE FROM `' . $table . '` WHERE id IN (' . winPosIdList($ids) . ')');
    return $conn->affected_rows;
}

function winPosClearDemoData(mysqli $conn, bool $commitChanges = true): array
{
    $data = winPosDemoDataSet($conn);
    $deleted = [];
    $returnIds = winPosIdList($data['returns']);

    $conn->begin_transaction();
    try {
        $stockResult = $conn->query(
            "SELECT product_id, SUM(quantity) quantity FROM stock_history "
            . "WHERE source_type='RETURN' AND source_id IN ($returnIds) AND product_id IS NOT NULL GROUP BY product_id"
        );
        $adjustStock = $conn->prepare('UPDATE products SET stock_qty=GREATEST(0, COALESCE(stock_qty,0)-?) WHERE id=?');
        while ($stock = $stockResult->fetch_assoc()) {
            $quantity = (float) $stock['quantity'];
            $productId = (int) $stock['product_id'];
            $adjustStock->bind_param('di', $quantity, $productId);
            $adjustStock->execute();
        }

        foreach ([
            'stock_history', 'return_items', 'returned_inventory', 'factory_returns',
            'payments', 'invoice_items', 'returns', 'invoices', 'order_items', 'orders',
            'quotation_items', 'quotations', 'survey_measurements', 'site_surveys',
            'labour_records', 'labour_transactions', 'expenses'
        ] as $table) {
            $deleted[$table] = winPosDeleteIds($conn, $table, $data[$table]);
        }

        $workerIds = winPosIdList($data['workers']);
        $conn->query(
            "DELETE FROM workers WHERE id IN ($workerIds) "
            . 'AND NOT EXISTS (SELECT 1 FROM labour_records WHERE labour_records.worker_id=workers.id) '
            . 'AND NOT EXISTS (SELECT 1 FROM labour_transactions WHERE labour_transactions.worker_id=workers.id)'
        );
        $deleted['workers'] = $conn->affected_rows;

        $customerIds = winPosIdList($data['customers']);
        $conn->query(
            "DELETE FROM customers WHERE id IN ($customerIds) "
            . 'AND NOT EXISTS (SELECT 1 FROM invoices WHERE invoices.customer_id=customers.id) '
            . 'AND NOT EXISTS (SELECT 1 FROM orders WHERE orders.customer_id=customers.id) '
            . 'AND NOT EXISTS (SELECT 1 FROM quotations WHERE quotations.customer_id=customers.id) '
            . 'AND NOT EXISTS (SELECT 1 FROM site_surveys WHERE site_surveys.customer_id=customers.id) '
            . 'AND NOT EXISTS (SELECT 1 FROM returns WHERE returns.customer_id=customers.id) '
            . 'AND NOT EXISTS (SELECT 1 FROM payments WHERE payments.customer_id=customers.id)'
        );
        $deleted['customers'] = $conn->affected_rows;

        if ($commitChanges) {
            $conn->commit();
        } else {
            $conn->rollback();
        }
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }

    return $deleted;
}
