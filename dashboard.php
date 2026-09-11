<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:index.php');
    exit();
}

include_once 'config/database.php';
require_once 'includes/permissions.php';

requirePermission('dashboard_view');

$page_title = 'Dashboard';

function dashboardValue(mysqli $conn, string $sql, string $field = 'total'): float
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return floatval($row[$field] ?? 0);
}

$customer_count = dashboardValue(
    $conn,
    'SELECT COUNT(*) AS total FROM customers'
);

$open_orders = dashboardValue(
    $conn,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status NOT IN ('Completed', 'Cancelled')"
);

$month_sales = dashboardValue(
    $conn,
    "SELECT COALESCE(SUM(grand_total), 0) AS total
     FROM invoices
     WHERE created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')"
);

$outstanding_balance = dashboardValue(
    $conn,
    'SELECT COALESCE(SUM(balance), 0) AS total FROM invoices'
);

$available_returns = dashboardValue(
    $conn,
    "SELECT COALESCE(SUM(quantity_available), 0) AS total
     FROM returned_inventory
     WHERE status = 'Available'"
);

$recent_invoices = mysqli_query($conn, "
    SELECT
        invoices.id,
        invoices.invoice_no,
        invoices.grand_total,
        invoices.balance,
        invoices.created_at,
        customers.name AS customer_name
    FROM invoices
    LEFT JOIN customers
        ON customers.id = invoices.customer_id
    ORDER BY invoices.id DESC
    LIMIT 7
");

$recent_orders = mysqli_query($conn, "
    SELECT
        orders.id,
        orders.invoice_no,
        orders.order_status,
        orders.created_at,
        customers.name AS customer_name
    FROM orders
    LEFT JOIN customers
        ON customers.id = orders.customer_id
    ORDER BY orders.id DESC
    LIMIT 6
");

include 'includes/header.php';
include 'includes/sidebar.php';

?>

<section class="page-hero">
    <div>
        <div class="page-kicker">Business overview</div>
        <h1 class="page-title">Good to see you, <?=htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']);?>.</h1>
        <p class="page-subtitle">
            Sales, orders, customer activity and stock in one focused workspace.
        </p>
    </div>

    <div class="text-muted">
        <i class="fa fa-calendar-day me-2"></i>
        <?=date('l, d F Y');?>
    </div>
</section>

<div class="row g-3">

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#2563eb">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-users"></i></div>
                <div class="metric-label">Customers</div>
                <div class="metric-value"><?=number_format($customer_count);?></div>
                <div class="metric-meta">Total customer records</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#14b8a6">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-clipboard-list"></i></div>
                <div class="metric-label">Open orders</div>
                <div class="metric-value"><?=number_format($open_orders);?></div>
                <div class="metric-meta">Still active or pending</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#7c3aed">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-chart-line"></i></div>
                <div class="metric-label">Sales this month</div>
                <div class="metric-value"><?=number_format($month_sales);?></div>
                <div class="metric-meta">Invoice grand total</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#dc2626">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-wallet"></i></div>
                <div class="metric-label">Outstanding</div>
                <div class="metric-value"><?=number_format($outstanding_balance);?></div>
                <div class="metric-meta"><?=number_format($available_returns, 2);?> returned items available</div>
            </div>
        </div>
    </div>

</div>

<section class="mt-4">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
            <div class="page-kicker">Shortcuts</div>
            <h2 class="h5 mb-0">Start common work</h2>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/invoices/add.php">
                <i class="fa fa-file-circle-plus"></i>
                <span>
                    <strong>New invoice</strong>
                    <small>Create a sale or order</small>
                </span>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/customers/add.php">
                <i class="fa fa-user-plus"></i>
                <span>
                    <strong>Add customer</strong>
                    <small>Save contact details</small>
                </span>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/site_survey/add.php">
                <i class="fa fa-ruler-combined"></i>
                <span>
                    <strong>Site survey</strong>
                    <small>Record measurements</small>
                </span>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/products/index.php">
                <i class="fa fa-box"></i>
                <span>
                    <strong>View products</strong>
                    <small>Check stock and pricing</small>
                </span>
            </a>
        </div>
    </div>
</section>

<div class="row g-4 mt-1">

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent invoices</span>
                <a href="/invoices/index.php" class="small text-decoration-none">View all</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if ($recent_invoices && $recent_invoices->num_rows > 0) { ?>
                        <?php while ($invoice = $recent_invoices->fetch_assoc()) { ?>
                            <tr>
                                <td>
                                    <a
                                        class="fw-semibold text-decoration-none"
                                        href="/invoices/view.php?id=<?=intval($invoice['id']);?>"
                                    >
                                        <?=htmlspecialchars($invoice['invoice_no'] ?? ('#' . $invoice['id']));?>
                                    </a>
                                </td>
                                <td><?=htmlspecialchars($invoice['customer_name'] ?? 'Walk-in customer');?></td>
                                <td class="text-nowrap"><?=date('d M Y', strtotime($invoice['created_at']));?></td>
                                <td class="text-end text-nowrap"><?=number_format(floatval($invoice['grand_total']));?></td>
                                <td class="text-end text-nowrap">
                                    <span class="<?=floatval($invoice['balance']) > 0 ? 'text-danger' : 'text-success';?>">
                                        <?=number_format(floatval($invoice['balance']));?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa fa-receipt"></i>
                                    <div>No invoices yet.</div>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Latest orders</span>
                <a href="/orders/index.php" class="small text-decoration-none">View all</a>
            </div>

            <div class="card-body">
                <div class="d-grid gap-3">

                    <?php if ($recent_orders && $recent_orders->num_rows > 0) { ?>
                        <?php while ($order = $recent_orders->fetch_assoc()) { ?>
                            <a
                                href="/orders/view.php?id=<?=intval($order['id']);?>"
                                class="d-flex align-items-center justify-content-between gap-3 text-decoration-none text-reset"
                            >
                                <span class="min-w-0">
                                    <strong class="d-block text-truncate">
                                        <?=htmlspecialchars($order['customer_name'] ?? 'Customer');?>
                                    </strong>
                                    <small class="text-muted">
                                        <?=htmlspecialchars($order['invoice_no'] ?? ('Order #' . $order['id']));?>
                                    </small>
                                </span>

                                <span class="badge bg-light text-dark border">
                                    <?=htmlspecialchars($order['order_status'] ?? 'Open');?>
                                </span>
                            </a>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="empty-state">
                            <i class="fa fa-clipboard-list"></i>
                            <div>No orders yet.</div>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
