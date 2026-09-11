<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include_once '../config/database.php';
require_once '../includes/permissions.php';

requirePermission('labour_view');

$page_title = 'Labour overview';

function labourValue(mysqli $conn, string $sql): float
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return floatval($row['total'] ?? 0);
}

$active_workers = labourValue(
    $conn,
    "SELECT COUNT(*) AS total FROM workers WHERE status = 'Active'"
);

$today_records = labourValue(
    $conn,
    'SELECT COUNT(*) AS total
     FROM labour_records
     WHERE work_date = CURRENT_DATE'
);

$today_earnings = labourValue(
    $conn,
    'SELECT COALESCE(SUM(earned_amount), 0) AS total
     FROM labour_records
     WHERE work_date = CURRENT_DATE'
);

$savings_balance = labourValue(
    $conn,
    "SELECT COALESCE(SUM(
        CASE
            WHEN type = 'Saving Deposit' THEN amount
            WHEN type = 'Saving Withdrawal' THEN -amount
            ELSE 0
        END
    ), 0) AS total
    FROM labour_transactions"
);

$recent_workers = mysqli_query($conn, "
    SELECT id, name, phone, position, daily_rate, status
    FROM workers
    ORDER BY id DESC
    LIMIT 6
");

include '../includes/header.php';
include '../includes/sidebar.php';

?>

<section class="page-hero">
    <div>
        <div class="page-kicker">Finance & people</div>
        <h1 class="page-title">Labour overview</h1>
        <p class="page-subtitle">
            Manage workers, daily attendance, earnings, savings and transactions.
        </p>
    </div>

    <a href="/labour/workers/add.php" class="btn btn-primary">
        <i class="fa fa-user-plus"></i>
        Add worker
    </a>
</section>

<div class="row g-3">

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#2563eb">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-user-group"></i></div>
                <div class="metric-label">Active workers</div>
                <div class="metric-value"><?=number_format($active_workers);?></div>
                <div class="metric-meta">Currently active</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#14b8a6">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-calendar-check"></i></div>
                <div class="metric-label">Today's records</div>
                <div class="metric-value"><?=number_format($today_records);?></div>
                <div class="metric-meta">Attendance entries</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#7c3aed">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-money-check-dollar"></i></div>
                <div class="metric-label">Today's earnings</div>
                <div class="metric-value"><?=number_format($today_earnings);?></div>
                <div class="metric-meta">Recorded worker earnings</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#d97706">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-piggy-bank"></i></div>
                <div class="metric-label">Savings balance</div>
                <div class="metric-value"><?=number_format($savings_balance);?></div>
                <div class="metric-meta">Deposits less withdrawals</div>
            </div>
        </div>
    </div>

</div>

<section class="mt-4">
    <div class="page-kicker">Labour tools</div>
    <h2 class="h5 mb-3">Choose what you want to manage</h2>

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/labour/workers/index.php">
                <i class="fa fa-user-group"></i>
                <span>
                    <strong>Workers</strong>
                    <small>Profiles, rates and status</small>
                </span>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/labour/records/index.php">
                <i class="fa fa-calendar-check"></i>
                <span>
                    <strong>Daily records</strong>
                    <small>Attendance and earnings</small>
                </span>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/labour/transactions/index.php">
                <i class="fa fa-money-bill-transfer"></i>
                <span>
                    <strong>Transactions</strong>
                    <small>Cash, savings and advances</small>
                </span>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a class="quick-action" href="/labour/summary/index.php">
                <i class="fa fa-chart-column"></i>
                <span>
                    <strong>Summary</strong>
                    <small>Worker balances and totals</small>
                </span>
            </a>
        </div>
    </div>
</section>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recently added workers</span>
        <a href="/labour/workers/index.php" class="small text-decoration-none">View all</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Position</th>
                    <th>Phone</th>
                    <th class="text-end">Daily rate</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

            <?php if ($recent_workers && $recent_workers->num_rows > 0) { ?>
                <?php while ($worker = $recent_workers->fetch_assoc()) { ?>
                    <tr>
                        <td><strong><?=htmlspecialchars($worker['name'] ?? '');?></strong></td>
                        <td><?=htmlspecialchars($worker['position'] ?: '-');?></td>
                        <td><?=htmlspecialchars($worker['phone'] ?: '-');?></td>
                        <td class="text-end"><?=number_format(floatval($worker['daily_rate'] ?? 0));?></td>
                        <td>
                            <span class="badge <?=$worker['status'] === 'Active' ? 'bg-success' : 'bg-secondary';?>">
                                <?=htmlspecialchars($worker['status'] ?? 'Inactive');?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a
                                href="/labour/workers/view.php?id=<?=intval($worker['id']);?>"
                                class="btn btn-outline-primary btn-sm"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fa fa-user-group"></i>
                            <div>No workers yet.</div>
                        </div>
                    </td>
                </tr>
            <?php } ?>

            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
