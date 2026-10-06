<?php
// Shared quotation validation and persistence; no output or side effects on include.
function quotationPrice(array $row): float
{
    foreach (['default_price', 'sqft_price', 'price_per_sqft', 'base_price', 'selling_price', 'sale_price', 'price'] as $column) {
        if (isset($row[$column]) && $row[$column] !== '') return (float) $row[$column];
    }
    return 0.0;
}

function quotationNumber($value, string $label): float
{
    if (!is_scalar($value)) throw new InvalidArgumentException('Invalid ' . $label . '.');
    $raw = str_replace(',', '', trim((string) $value));
    if ($raw === '') return 0.0;
    if (!is_numeric($raw) || !is_finite((float) $raw) || (float) $raw < 0) {
        throw new InvalidArgumentException('Enter a valid ' . $label . '.');
    }
    return (float) $raw;
}

function quotationFeet(array $item): array
{
    if (($item['calculation_type'] ?? 'SQFT') === 'MANUAL') return [0.0, 0.0];
    return [
        (float) ($item['width_ft'] ?? ceil(((float) $item['width_mm'] / 304.8 * 2) - 1e-9) / 2),
        (float) ($item['height_ft'] ?? ceil(((float) $item['height_mm'] / 304.8 * 2) - 1e-9) / 2),
    ];
}

function quotationLookup(mysqli $conn, string $type, int $id): ?array
{
    $table = ['ORDER' => 'factory_products', 'SALE' => 'products', 'GLASS' => 'glass_types'][$type] ?? null;
    if (!$table || $id <= 0) return null;
    $stmt = $conn->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/** The same MM half-foot rounding and manual quantity pricing as Add Invoice. */
function quotationParseItems(array $post, callable $lookup): array
{
    $types = $post['item_type'] ?? [];
    if (!is_array($types) || !$types || count($types) > 500) {
        throw new InvalidArgumentException('Add between 1 and 500 complete quotation items.');
    }
    $items = [];
    foreach ($types as $i => $type) {
        $label = 'item ' . ($i + 1);
        $mode = $post['calculation_type'][$i] ?? '';
        $unit = $post['measurement_unit'][$i] ?? 'MM';
        if (!in_array($type, ['ORDER', 'SALE', 'SERVICE'], true)
            || !in_array($mode, ['SQFT', 'MANUAL'], true)
            || !in_array($unit, ['MM', 'FT'], true)) {
            throw new InvalidArgumentException('Invalid type, mode, or unit for ' . $label . '.');
        }
        $pid = (int) ($post['product_id'][$i] ?? 0);
        $product = $type !== 'SERVICE' && $pid > 0 ? $lookup($type, $pid) : null;
        if (($pid > 0 && $type !== 'SERVICE' && !$product) || ($type === 'SALE' && !$product)) {
            throw new InvalidArgumentException('Select an existing product for ' . $label . '.');
        }
        $name = trim((string) ($post['product_name'][$i] ?? ''));
        if ($name === '') $name = $product['product_name'] ?? $product['name'] ?? '';
        if ($name === '' || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Enter a product name (up to 150 characters) for ' . $label . '.');
        }
        $quantity = quotationNumber($post['quantity'][$i] ?? '', 'quantity for ' . $label);
        if ($quantity < 1 || floor($quantity) !== $quantity || $quantity > 1000000) {
            throw new InvalidArgumentException('Quantity must be a positive whole number for ' . $label . '.');
        }
        $widthMm = $heightMm = $widthFt = $heightFt = $sqft = $glassPrice = 0.0;
        $glassId = null;
        $glassName = null;
        if ($mode === 'SQFT') {
            $width = quotationNumber($post['width'][$i] ?? '', 'width for ' . $label);
            $height = quotationNumber($post['height'][$i] ?? '', 'height for ' . $label);
            if ($width <= 0 || $height <= 0) throw new InvalidArgumentException('Enter width and height for ' . $label . '.');
            $widthMm = $unit === 'FT' ? $width * 304.8 : $width;
            $heightMm = $unit === 'FT' ? $height * 304.8 : $height;
            $widthFt = $unit === 'FT' ? $width : ceil(($width / 304.8 * 2) - 1e-9) / 2;
            $heightFt = $unit === 'FT' ? $height : ceil(($height / 304.8 * 2) - 1e-9) / 2;
            $base = quotationNumber($post['base_price'][$i] ?? '', 'original price for ' . $label);
            $glassId = (int) ($post['glass_type_id'][$i] ?? 0) ?: null;
            if ($glassId) {
                $glass = $lookup('GLASS', $glassId);
                if (!$glass) throw new InvalidArgumentException('Selected glass was not found for ' . $label . '.');
                $glassName = $glass['name'];
                $glassPrice = quotationNumber($post['glass_price'][$i] ?? quotationPrice($glass), 'glass price');
            }
            $rate = $base + $glassPrice;
            $sqft = $widthFt * $heightFt * $quantity;
            $total = round($sqft * $rate, 2);
        } else {
            $base = $rate = quotationNumber($post['price'][$i] ?? '', 'price for ' . $label);
            $total = round($quantity * $rate, 2);
        }
        if ($total > 9999999999.99 || $widthMm > 99999999.99 || $heightMm > 99999999.99) {
            throw new InvalidArgumentException('Measurements or total are too large for ' . $label . '.');
        }
        $items[] = [
            'item_type' => $type, 'product_id' => $product ? $pid : null,
            'factory_product_id' => $type === 'ORDER' && $product ? $pid : null,
            'product_name' => $name, 'description' => trim((string) ($post['description'][$i] ?? '')),
            'calculation_type' => $mode, 'measurement_unit' => $unit,
            'width_mm' => $widthMm, 'height_mm' => $heightMm, 'width_ft' => $widthFt, 'height_ft' => $heightFt,
            'quantity' => (int) $quantity, 'sqft' => $sqft, 'base_price' => $base,
            'glass_type_id' => $glassId, 'glass_name' => $glassName, 'glass_price' => $glassPrice,
            'unit_price' => $rate, 'total' => $total,
        ];
    }
    return $items;
}

function quotationInsertItems(mysqli $conn, int $id, array $items): void
{
    $columns = ['item_type', 'product_id', 'factory_product_id', 'product_name', 'description',
        'calculation_type', 'measurement_unit', 'width_mm', 'height_mm', 'width_ft', 'height_ft',
        'quantity', 'sqft', 'base_price', 'glass_type_id', 'glass_name', 'glass_price', 'unit_price', 'total'];
    $stmt = $conn->prepare('INSERT INTO quotation_items (quotation_id,' . implode(',', $columns)
        . ') VALUES (' . implode(',', array_fill(0, count($columns) + 1, '?')) . ')');
    foreach ($items as $item) {
        $values = [$id];
        foreach ($columns as $column) $values[] = $item[$column];
        $stmt->execute($values);
    }
}

function quotationOrderItem(mysqli $conn, int $orderId, array $item): int
{
    [$wf, $hf] = quotationFeet($item);
    $pid = $item['factory_product_id'] ?: null;
    $product = $pid ? quotationLookup($conn, 'ORDER', (int) $pid) : null;
    $stmt = $conn->prepare('INSERT INTO order_items (order_id,category_id,material_type_id,factory_product_id,product_name,calculation_type,width,height,width_mm,height_mm,width_ft,height_ft,quantity,sqft,description,glass_type_id,glass_price,final_price_per_sqft) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$orderId, $product['category_id'] ?? null, $product['material_type_id'] ?? null, $pid,
        $item['product_name'], $item['calculation_type'] ?? 'SQFT', $wf, $hf, $item['width_mm'], $item['height_mm'],
        $wf, $hf, $item['quantity'], $item['sqft'], $item['description'], $item['glass_type_id'] ?? null,
        $item['glass_price'] ?? 0, $item['unit_price']]);
    return $conn->insert_id;
}

/** Called inside conversion's transaction; quoting alone never changes stock. */
function quotationInvoiceItems(mysqli $conn, int $invoiceId, string $invoiceNo, int $customerId, string $notes, array $items): void
{
    $orderId = null;
    foreach ($items as $item) {
        if (($item['item_type'] ?? 'ORDER') === 'ORDER') {
            $stmt = $conn->prepare("INSERT INTO orders (invoice_no,customer_id,order_status,notes) VALUES (?,?,'Quotation',?)");
            $stmt->execute([$invoiceNo, $customerId, $notes]);
            $orderId = $conn->insert_id;
            break;
        }
    }
    $insert = $conn->prepare('INSERT INTO invoice_items (invoice_id,order_item_id,item_type,product_id,product_name,quantity,width_mm,height_mm,width_ft,height_ft,width,height,sqft,price,total,description,glass_type_id,glass_price,final_price_per_sqft) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($items as $item) {
        $type = $item['item_type'] ?? 'ORDER';
        $pid = $item['product_id'] ?? ($type === 'ORDER' ? $item['factory_product_id'] : null);
        $orderItemId = $type === 'ORDER' ? quotationOrderItem($conn, $orderId, $item) : null;
        [$wf, $hf] = quotationFeet($item);
        $insert->execute([$invoiceId, $orderItemId, $type, $pid, $item['product_name'], $item['quantity'],
            $item['width_mm'], $item['height_mm'], $wf, $hf, $wf, $hf, $item['sqft'], $item['unit_price'],
            $item['total'], $item['description'], $item['glass_type_id'] ?? null, $item['glass_price'] ?? 0, $item['unit_price']]);
        if ($type === 'SALE') {
            $stmt = $conn->prepare('SELECT stock_qty FROM products WHERE id=? FOR UPDATE');
            $stmt->execute([$pid]);
            $product = $stmt->get_result()->fetch_assoc();
            if (!$product || (float) $product['stock_qty'] < (int) $item['quantity']) {
                throw new RuntimeException('Insufficient stock for ' . $item['product_name'] . '.');
            }
            $balance = (float) $product['stock_qty'] - (int) $item['quantity'];
            $stmt = $conn->prepare('UPDATE products SET stock_qty=? WHERE id=?');
            $stmt->execute([$balance, $pid]);
            $stmt = $conn->prepare("INSERT INTO stock_history (product_id,type,quantity,balance_after,note,source_type,source_id,user_id) VALUES (?,'OUT',?,?,?,'INVOICE',?,?)");
            $stmt->execute([$pid, $item['quantity'], $balance, 'Invoice ' . $invoiceNo, $invoiceId, (int) ($_SESSION['user_id'] ?? 0)]);
        }
    }
}
