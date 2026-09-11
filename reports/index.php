<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include_once '../config/database.php';
require_once '../includes/permissions.php';

requirePermission('reports_view');

$page_title = 'Reports';

include '../includes/header.php';
include '../includes/sidebar.php';

?>

<section class="page-hero">
    <div>
        <div class="page-kicker">Insights</div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">
            Review sales, payment activity and customer performance.
        </p>
    </div>
</section>

<div class="row g-4">

    <div class="col-md-6 col-xl-4">
        <a href="/reports/sales.php" class="quick-action">
            <i class="fa fa-chart-column"></i>
            <span>
                <strong>Sales report</strong>
                <small>Invoices, deposits and outstanding balances</small>
            </span>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="/reports/payments.php" class="quick-action">
            <i class="fa fa-wallet"></i>
            <span>
                <strong>Payment report</strong>
                <small>Received payments and transaction history</small>
            </span>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="/reports/customers.php" class="quick-action">
            <i class="fa fa-users"></i>
            <span>
                <strong>Customer report</strong>
                <small>Sales activity grouped by customer</small>
            </span>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="/reports/profit.php" class="quick-action">
            <i class="fa fa-chart-line"></i>
            <span>
                <strong>Monthly profit</strong>
                <small>Revenue less expenses and paid labour</small>
            </span>
        </a>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
