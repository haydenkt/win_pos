<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/permissions.php';
require_once __DIR__.'/../includes/simple_xlsx.php';
requirePermission('reports_view');

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$validDate = static fn(string $date): bool => $date === '' || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
if (!$validDate($from) || !$validDate($to) || (($from === '') xor ($to === '')) || ($from !== '' && $from > $to)) {
    http_response_code(422);
    die('Choose a valid From and To date before exporting.');
}

$dateWhere = $from !== '' ? ' WHERE DATE(i.created_at) BETWEEN ? AND ? ' : '';
function reportQuery(mysqli $conn, string $sql, string $from, string $to): mysqli_result
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    if ($from !== '') {
        $stmt->bind_param('ss', $from, $to);
    }
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
    return $stmt->get_result();
}

$summary = reportQuery($conn, "SELECT COUNT(i.id) total_invoice, COALESCE(SUM(i.grand_total),0) total_sales, COALESCE(SUM(i.deposit),0) total_paid, COALESCE(SUM(i.balance),0) total_balance FROM invoices i $dateWhere", $from, $to)->fetch_assoc();
$details = reportQuery($conn, "SELECT i.invoice_no, DATE(i.created_at) invoice_date, c.name customer_name, ii.item_type, ii.product_name, ii.quantity, ii.sqft, ii.price, ii.total FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id LEFT JOIN customers c ON c.id=i.customer_id $dateWhere ORDER BY i.created_at, i.id, ii.id", $from, $to);
$materials = reportQuery($conn, "SELECT COALESCE(mt.name,'Other / non-factory') material_name, COUNT(ii.id) total_items, COALESCE(SUM(ii.quantity),0) qty, COALESCE(SUM(ii.sqft),0) sqft, COALESCE(SUM(ii.total),0) sales FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id LEFT JOIN factory_products fp ON ii.item_type='ORDER' AND ii.product_id=fp.id LEFT JOIN material_types mt ON mt.id=fp.material_type_id $dateWhere GROUP BY mt.id, mt.name ORDER BY sales DESC", $from, $to);
$products = reportQuery($conn, "SELECT ii.product_name, COALESCE(SUM(ii.quantity),0) qty, COALESCE(SUM(ii.sqft),0) sqft, COALESCE(SUM(ii.total),0) sales FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id $dateWhere GROUP BY ii.product_name ORDER BY sales DESC LIMIT 10", $from, $to);

$period = $from === '' ? 'All dates' : date('d M Y', strtotime($from)).' to '.date('d M Y', strtotime($to));
$title = [['value'=>'Win POS Sales Report','style'=>1],null,null,null];
$tableHeader = static fn(array $labels): array => array_map(static fn($label)=>['value'=>$label,'style'=>3],$labels);

$summaryRows = [
    $title,
    [['value'=>'Period','style'=>2],['value'=>$period]],
    [],
    $tableHeader(['Metric','Amount']),
    [['value'=>'Invoices'],['value'=>(int)$summary['total_invoice'],'type'=>'number','style'=>6]],
    [['value'=>'Total sales'],['value'=>(float)$summary['total_sales'],'type'=>'number','style'=>4]],
    [['value'=>'Paid'],['value'=>(float)$summary['total_paid'],'type'=>'number','style'=>4]],
    [['value'=>'Outstanding balance'],['value'=>(float)$summary['total_balance'],'type'=>'number','style'=>4]],
];

$detailRows = [$title, [['value'=>'Period','style'=>2],['value'=>$period]], [], $tableHeader(['Invoice','Date','Customer','Type','Product','Quantity','Sqft','Unit price','Total'])];
while ($row = $details->fetch_assoc()) {
    $detailRows[] = [
        ['value'=>$row['invoice_no']], ['value'=>$row['invoice_date'],'type'=>'date','style'=>7],
        ['value'=>$row['customer_name'] ?: '—'], ['value'=>$row['item_type']], ['value'=>$row['product_name']],
        ['value'=>(float)$row['quantity'],'type'=>'number','style'=>6], ['value'=>(float)$row['sqft'],'type'=>'number','style'=>4],
        ['value'=>(float)$row['price'],'type'=>'number','style'=>4], ['value'=>(float)$row['total'],'type'=>'number','style'=>4],
    ];
}

$materialRows = [$title, [['value'=>'Period','style'=>2],['value'=>$period]], [], $tableHeader(['Material type','Items','Quantity','Sqft','Sales'])];
while ($row = $materials->fetch_assoc()) {
    $materialRows[] = [['value'=>$row['material_name']],['value'=>(int)$row['total_items'],'type'=>'number','style'=>6],['value'=>(float)$row['qty'],'type'=>'number','style'=>6],['value'=>(float)$row['sqft'],'type'=>'number','style'=>4],['value'=>(float)$row['sales'],'type'=>'number','style'=>4]];
}

$productRows = [$title, [['value'=>'Period','style'=>2],['value'=>$period]], [], $tableHeader(['Product','Quantity','Sqft','Sales'])];
while ($row = $products->fetch_assoc()) {
    $productRows[] = [['value'=>$row['product_name']],['value'=>(float)$row['qty'],'type'=>'number','style'=>6],['value'=>(float)$row['sqft'],'type'=>'number','style'=>4],['value'=>(float)$row['sales'],'type'=>'number','style'=>4]];
}

$sheets = [
    ['name'=>'Summary','rows'=>$summaryRows,'widths'=>[28,22,14,14]],
    ['name'=>'Sales detail','rows'=>$detailRows,'widths'=>[20,14,24,14,32,12,12,16,16]],
    ['name'=>'Material sales','rows'=>$materialRows,'widths'=>[28,12,14,14,18]],
    ['name'=>'Top products','rows'=>$productRows,'widths'=>[34,14,14,18]],
];

$filename = 'Win_POS_Sales_Report_'.($from === '' ? date('Y-m-d') : $from.'_to_'.$to).'.xlsx';
SimpleXlsx::download($filename, $sheets);
exit;
