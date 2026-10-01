<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('reports_view');

$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
$filter_error = '';

if (
    ($from !== '' && !preg_match($date_pattern, $from))
    || ($to !== '' && !preg_match($date_pattern, $to))
    || (($from === '') xor ($to === ''))
    || ($from !== '' && $from > $to)
) {
    $filter_error = 'Choose a valid From and To date.';
    $from = '';
    $to = '';
}

$date_filter = $from !== ''
    ? ' AND DATE(i.created_at) BETWEEN ? AND ? '
    : '';

function salesReportResult(mysqli $conn, string $sql, string $from, string $to): mysqli_result
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

$summary = salesReportResult(
    $conn,
    "SELECT COUNT(i.id) AS total_invoice,
            COALESCE(SUM(i.grand_total), 0) AS total_sales,
            COALESCE(SUM(i.deposit), 0) AS total_paid,
            COALESCE(SUM(i.balance), 0) AS total_balance
     FROM invoices i
     WHERE 1 = 1 $date_filter",
    $from,
    $to
)->fetch_assoc();

$type_result = salesReportResult(
    $conn,
    "SELECT UPPER(COALESCE(NULLIF(ii.item_type, ''), 'OTHER')) AS item_type,
            COUNT(ii.id) AS line_count,
            COALESCE(SUM(ii.quantity), 0) AS quantity,
            COALESCE(SUM(ii.sqft), 0) AS sqft,
            COALESCE(SUM(ii.total), 0) AS sales
     FROM invoice_items ii
     INNER JOIN invoices i ON i.id = ii.invoice_id
     WHERE 1 = 1 $date_filter
     GROUP BY UPPER(COALESCE(NULLIF(ii.item_type, ''), 'OTHER'))
     ORDER BY sales DESC",
    $from,
    $to
);

$type_totals = [
    'ORDER' => ['line_count' => 0, 'quantity' => 0, 'sqft' => 0, 'sales' => 0],
    'SALE' => ['line_count' => 0, 'quantity' => 0, 'sqft' => 0, 'sales' => 0],
    'SERVICE' => ['line_count' => 0, 'quantity' => 0, 'sqft' => 0, 'sales' => 0]
];

while ($row = $type_result->fetch_assoc()) {
    $type = (string) $row['item_type'];
    $type_totals[$type] = [
        'line_count' => (int) $row['line_count'],
        'quantity' => (float) $row['quantity'],
        'sqft' => (float) $row['sqft'],
        'sales' => (float) $row['sales']
    ];
}

$details = salesReportResult(
    $conn,
    "SELECT i.id AS invoice_id, i.invoice_no, DATE(i.created_at) AS invoice_date,
            c.name AS customer_name,
            UPPER(COALESCE(NULLIF(ii.item_type, ''), 'OTHER')) AS item_type,
            ii.product_name, ii.quantity, ii.sqft, ii.price, ii.total
     FROM invoice_items ii
     INNER JOIN invoices i ON i.id = ii.invoice_id
     LEFT JOIN customers c ON c.id = i.customer_id
     WHERE 1 = 1 $date_filter
     ORDER BY i.created_at DESC, i.id DESC, ii.id ASC",
    $from,
    $to
);

$materials = salesReportResult(
    $conn,
    "SELECT COALESCE(mt.name, 'Unassigned') AS material_name,
            COUNT(ii.id) AS total_items,
            COALESCE(SUM(ii.quantity), 0) AS total_qty,
            COALESCE(SUM(ii.sqft), 0) AS total_sqft,
            COALESCE(SUM(ii.total), 0) AS total_sales
     FROM invoice_items ii
     INNER JOIN invoices i ON i.id = ii.invoice_id
     LEFT JOIN factory_products fp
        ON ii.item_type = 'ORDER' AND ii.product_id = fp.id
     LEFT JOIN material_types mt ON mt.id = fp.material_type_id
     WHERE ii.item_type = 'ORDER' $date_filter
     GROUP BY mt.id, mt.name
     ORDER BY total_sales DESC",
    $from,
    $to
);

$products = salesReportResult(
    $conn,
    "SELECT UPPER(COALESCE(NULLIF(ii.item_type, ''), 'OTHER')) AS item_type,
            ii.product_name,
            COALESCE(SUM(ii.quantity), 0) AS qty,
            COALESCE(SUM(ii.sqft), 0) AS sqft,
            COALESCE(SUM(ii.total), 0) AS sales
     FROM invoice_items ii
     INNER JOIN invoices i ON i.id = ii.invoice_id
     WHERE 1 = 1 $date_filter
     GROUP BY UPPER(COALESCE(NULLIF(ii.item_type, ''), 'OTHER')), ii.product_name
     ORDER BY sales DESC
     LIMIT 15",
    $from,
    $to
);

$period = $from === ''
    ? 'All dates'
    : date('d M Y', strtotime($from)) . ' – ' . date('d M Y', strtotime($to));

$type_styles = [
    'ORDER' => ['label' => 'Orders', 'icon' => 'fa-ruler-combined', 'badge' => 'bg-primary'],
    'SALE' => ['label' => 'Product Sales', 'icon' => 'fa-box', 'badge' => 'bg-success'],
    'SERVICE' => ['label' => 'Services', 'icon' => 'fa-screwdriver-wrench', 'badge' => 'bg-info']
];

$page_title = 'All sales report';
include '../includes/header.php';
include '../includes/sidebar.php';

?>

<section class="page-hero">
    <div>
        <div class="page-kicker">Reports · <?=$period;?></div>
        <h1 class="page-title">All Sales Report</h1>
        <p class="page-subtitle">Orders, product sales and services together on one page.</p>
    </div>
    <a href="export_sales_excel.php?from=<?=urlencode($from);?>&to=<?=urlencode($to);?>" class="btn btn-success">
        <i class="fa fa-file-excel"></i> Export All to Excel
    </a>
</section>

<?php if ($filter_error !== '') { ?>
    <div class="alert alert-danger"><?=htmlspecialchars($filter_error);?></div>
<?php } ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="from" class="form-label">From</label>
                <input id="from" type="date" name="from" class="form-control" value="<?=htmlspecialchars($from);?>">
            </div>
            <div class="col-md-4">
                <label for="to" class="form-label">To</label>
                <input id="to" type="date" name="to" class="form-control" value="<?=htmlspecialchars($to);?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="fa fa-search"></i> View Report</button>
                <a href="sales.php" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Invoices</div><div class="metric-value"><?=number_format((int) $summary['total_invoice']);?></div><div class="metric-meta">Issued in this period</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Total Sales</div><div class="metric-value"><?=number_format((float) $summary['total_sales'], 2);?></div><div class="metric-meta">Invoice grand total</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Paid</div><div class="metric-value text-success"><?=number_format((float) $summary['total_paid'], 2);?></div><div class="metric-meta">Payments received</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Outstanding</div><div class="metric-value text-danger"><?=number_format((float) $summary['total_balance'], 2);?></div><div class="metric-meta">Customer balance</div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($type_styles as $type => $style) { $type_total = $type_totals[$type]; ?>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div><div class="small text-muted text-uppercase"><?=htmlspecialchars($style['label']);?></div><h3 class="mb-0"><?=number_format($type_total['sales'], 2);?></h3></div>
                    <span class="badge <?=$style['badge'];?>"><i class="fa <?=$style['icon'];?>"></i> <?=$type;?></span>
                </div>
                <div class="d-flex flex-wrap gap-3 small text-muted">
                    <span><?=number_format($type_total['line_count']);?> items</span>
                    <span><?=number_format($type_total['quantity'], 2);?> qty</span>
                    <?php if ($type === 'ORDER') { ?><span><?=number_format($type_total['sqft'], 2);?> sqft</span><?php } ?>
                </div>
                <div class="small text-muted mt-2">Item totals before invoice-level discounts.</div>
            </div></div>
        </div>
    <?php } ?>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fa fa-list me-2"></i>All Sales Details</strong>
        <span class="text-muted small">ORDER · SALE · SERVICE</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Type</th><th>Product / Service</th><th class="text-end">Qty</th><th class="text-end">SQFT</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                <?php if ($details->num_rows === 0) { ?><tr><td colspan="9" class="text-center text-muted py-5">No sales found for this period.</td></tr><?php } ?>
                <?php while ($row = $details->fetch_assoc()) { $style = $type_styles[$row['item_type']] ?? ['badge' => 'bg-secondary']; ?>
                    <tr>
                        <td><?=date('d M Y', strtotime($row['invoice_date']));?></td>
                        <td><a href="/invoices/view.php?id=<?=(int) $row['invoice_id'];?>"><?=htmlspecialchars((string) $row['invoice_no']);?></a></td>
                        <td><?=htmlspecialchars((string) ($row['customer_name'] ?: '—'));?></td>
                        <td><span class="badge <?=$style['badge'];?>"><?=htmlspecialchars((string) $row['item_type']);?></span></td>
                        <td><strong><?=htmlspecialchars((string) $row['product_name']);?></strong></td>
                        <td class="text-end"><?=number_format((float) $row['quantity'], 2);?></td>
                        <td class="text-end"><?=number_format((float) $row['sqft'], 2);?></td>
                        <td class="text-end"><?=number_format((float) $row['price'], 2);?></td>
                        <td class="text-end fw-bold"><?=number_format((float) $row['total'], 2);?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><strong><i class="fa fa-layer-group me-2"></i>Order Sales by Material</strong></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Material</th><th class="text-end">Items</th><th class="text-end">Qty</th><th class="text-end">SQFT</th><th class="text-end">Sales</th></tr></thead>
                <tbody>
                    <?php if ($materials->num_rows === 0) { ?><tr><td colspan="5" class="text-center text-muted py-4">No order sales.</td></tr><?php } ?>
                    <?php while ($row = $materials->fetch_assoc()) { ?><tr><td><?=htmlspecialchars((string) $row['material_name']);?></td><td class="text-end"><?=number_format((int) $row['total_items']);?></td><td class="text-end"><?=number_format((float) $row['total_qty'], 2);?></td><td class="text-end"><?=number_format((float) $row['total_sqft'], 2);?></td><td class="text-end fw-bold"><?=number_format((float) $row['total_sales'], 2);?></td></tr><?php } ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><strong><i class="fa fa-ranking-star me-2"></i>Top Products and Services</strong></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Type</th><th>Product / Service</th><th class="text-end">Qty</th><th class="text-end">SQFT</th><th class="text-end">Sales</th></tr></thead>
                <tbody>
                    <?php if ($products->num_rows === 0) { ?><tr><td colspan="5" class="text-center text-muted py-4">No products or services.</td></tr><?php } ?>
                    <?php while ($row = $products->fetch_assoc()) { $style = $type_styles[$row['item_type']] ?? ['badge' => 'bg-secondary']; ?>
                        <tr><td><span class="badge <?=$style['badge'];?>"><?=htmlspecialchars((string) $row['item_type']);?></span></td><td><?=htmlspecialchars((string) $row['product_name']);?></td><td class="text-end"><?=number_format((float) $row['qty'], 2);?></td><td class="text-end"><?=number_format((float) $row['sqft'], 2);?></td><td class="text-end fw-bold"><?=number_format((float) $row['sales'], 2);?></td></tr>
                    <?php } ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, form, .btn { display: none !important; }
    .card { break-inside: avoid; }
}
</style>

<?php include '../includes/footer.php'; ?>
