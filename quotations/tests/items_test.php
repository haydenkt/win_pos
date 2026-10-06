<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../items.php';
function check($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$lookup = static function ($type, $id) {
    if ($id !== 1) return null;
    return match ($type) {
        'ORDER' => ['product_name' => 'Test window', 'default_price' => 1000],
        'SALE' => ['name' => 'Test hardware', 'sale_price' => 500],
        'GLASS' => ['name' => 'Test glass', 'price' => 200],
    };
};
$post = [
    'item_type' => ['ORDER', 'SALE', 'SERVICE', 'ORDER'],
    'product_id' => [1, 1, '', ''], 'product_name' => ['', '', 'Installation', 'Custom frame'],
    'calculation_type' => ['SQFT', 'MANUAL', 'MANUAL', 'SQFT'],
    'measurement_unit' => ['MM', 'MM', 'MM', 'FT'],
    'width' => [945, 0, 0, 3.5], 'height' => [1219.2, 0, 0, 4], 'quantity' => [2, 3, 1, 1],
    'base_price' => ['1,000', 0, 0, '2,000'], 'price' => ['999999', '500', '10,000', 999999],
    'glass_type_id' => [1, '', '', ''], 'glass_price' => [200, 0, 0, 0],
];
$items = quotationParseItems($post, $lookup);
check($items[0]['width_ft'] === 3.5 && $items[0]['height_ft'] === 4.0, 'MM half-foot rounding');
check($items[0]['total'] === 33600.0 && $items[0]['unit_price'] === 1200.0, 'Base plus glass pricing');
check($items[1]['total'] === 1500.0 && $items[1]['sqft'] === 0.0, 'Manual sale pricing');
check($items[2]['total'] === 10000.0 && $items[2]['product_id'] === null, 'Custom service');
check($items[3]['total'] === 28000.0 && abs($items[3]['width_mm'] - 1066.8) < 1e-8, 'FT mode and custom order');
check(quotationFeet(['width_mm' => 945, 'height_mm' => 1219.2]) === [3.5, 4.0], 'Legacy quotation rounding');
foreach (['quantity' => 0, 'base_price' => -50, 'width' => 0, 'glass_type_id' => 999] as $field => $badValue) {
    $bad = $post; $bad[$field][0] = $badValue;
    try { quotationParseItems($bad, $lookup); throw new RuntimeException('Accepted invalid ' . $field); }
    catch (InvalidArgumentException $expected) {}
}
echo "PASS: MM/FT, glass, comma prices, manual items, custom names, legacy rows, invalid inputs.\n";

if (!in_array('--database', $argv, true)) exit;
require_once __DIR__ . '/../../config/database.php';
$conn->begin_transaction();
try {
    // Only uncommitted fixtures; every write below is rolled back even on failure.
    $tag = 'QUOTATION-TEST-' . bin2hex(random_bytes(5));
    $stmt = $conn->prepare('INSERT INTO customers (name,phone,address) VALUES (?,\'\',\'\')');
    $stmt->execute([$tag]); $customer = $conn->insert_id;
    $stmt = $conn->prepare('INSERT INTO quotations (quote_no,customer_id,quote_date,valid_until) VALUES (?,?,CURRENT_DATE(),CURRENT_DATE())');
    $stmt->execute([$tag, $customer]); $quoteId = $conn->insert_id;
    $dbItems = [$items[3], $items[2]];
    quotationInsertItems($conn, $quoteId, $dbItems);
    $stmt = $conn->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY id');
    $stmt->execute([$quoteId]); $saved = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    check(count($saved) === 2 && $saved[0]['measurement_unit'] === 'FT', 'Saved item modes');
    check((float)$saved[0]['base_price'] === 2000.0 && (float)$saved[0]['width_ft'] === 3.5, 'Saved exact quotation rate and dimensions');
    $stmt = $conn->prepare('INSERT INTO invoices (invoice_no,customer_id) VALUES (?,?)');
    $stmt->execute([$tag, $customer]); $invoiceId = $conn->insert_id;
    quotationInvoiceItems($conn, $invoiceId, $tag, $customer, '', $saved);
    $stmt = $conn->prepare('SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id');
    $stmt->execute([$invoiceId]); $converted = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    check(count($converted) === 2 && $converted[0]['item_type'] === 'ORDER' && $converted[1]['item_type'] === 'SERVICE', 'Conversion retains item types');
    check((int)$converted[0]['order_item_id'] > 0 && (float)$converted[0]['total'] === 28000.0, 'Order linkage and price');
    $stmt = $conn->prepare('INSERT INTO products (name,stock_qty,selling_price) VALUES (?,10,500)');
    $stmt->execute([$tag]); $saleId = $conn->insert_id;
    $sale = $items[1]; $sale['product_id'] = $saleId;
    quotationInsertItems($conn, $quoteId, [$sale]);
    $stmt = $conn->prepare('SELECT stock_qty FROM products WHERE id=?');
    $stmt->execute([$saleId]);
    check((float)$stmt->get_result()->fetch_assoc()['stock_qty'] === 10.0, 'Quotation does not change stock');
    quotationInvoiceItems($conn, $invoiceId, $tag, $customer, '', [$sale]);
    $stmt->execute([$saleId]);
    check((float)$stmt->get_result()->fetch_assoc()['stock_qty'] === 7.0, 'Invoice conversion deducts sale stock');
    $stock = $conn->prepare('SELECT COUNT(*) n FROM stock_history WHERE product_id=? AND source_id=?');
    $stock->execute([$saleId, $invoiceId]);
    check((int)$stock->get_result()->fetch_assoc()['n'] === 1, 'Conversion stock audit');
    $glassItem = $items[0]; $glassItem['factory_product_id'] = null; $glassItem['product_id'] = null;
    quotationInsertItems($conn, $quoteId, [$glassItem]);
    $glassCheck = $conn->prepare('SELECT * FROM quotation_items WHERE quotation_id=? AND glass_type_id IS NOT NULL');
    $glassCheck->execute([$quoteId]); $glassSaved = $glassCheck->get_result()->fetch_assoc();
    check((float)$glassSaved['glass_price'] === 200.0 && (float)$glassSaved['base_price'] === 1000.0 && (float)$glassSaved['unit_price'] === 1200.0, 'Glass/base split survives save');
    // Edit uses the same validated insert path after replacing the old items.
    $stmt = $conn->prepare('DELETE FROM quotation_items WHERE quotation_id=?');
    $stmt->execute([$quoteId]);
    quotationInsertItems($conn, $quoteId, [$items[2]]);
    $stmt = $conn->prepare('SELECT COUNT(*) n FROM quotation_items WHERE quotation_id=?');
    $stmt->execute([$quoteId]); check((int)$stmt->get_result()->fetch_assoc()['n'] === 1, 'Edit replaces items');
    echo "PASS: database save/edit, glass rates, order/service/sale conversion, stock only on conversion.\n";
} finally {
    $conn->rollback();
    echo "All database test writes rolled back.\n";
}
