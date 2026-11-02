<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:/index.php');
    exit();
}

include_once '../../config/database.php';
require_once '../../includes/permissions.php';

requirePermission('labour_view');

$page_title = 'Labour summary';

$month = trim($_GET['month'] ?? date('Y-m'));

if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    if (defined('WINPOS_LABOUR_EXPORT')) {
        http_response_code(422);
        exit('Choose a valid report month.');
    }
    $month = date('Y-m');
}

$month_start = $month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

$sql = "
    SELECT
        workers.id,
        workers.name,

        COALESCE((
            SELECT SUM(lr.earned_amount)
            FROM labour_records lr
            WHERE lr.worker_id = workers.id
            AND lr.record_date BETWEEN ? AND ?
        ), 0) AS earned,

        COALESCE((
            SELECT SUM(lt.amount)
            FROM labour_transactions lt
            WHERE lt.worker_id = workers.id
            AND lt.type = 'Cash Payment'
            AND lt.transaction_date BETWEEN ? AND ?
        ), 0) AS cash_paid,

        COALESCE((
            SELECT SUM(
                CASE
                    WHEN lt.type = 'Advance' THEN lt.amount
                    WHEN lt.type = 'Advance Repayment' THEN -lt.amount
                    ELSE 0
                END
            )
            FROM labour_transactions lt
            WHERE lt.worker_id = workers.id
            AND lt.transaction_date < ?
        ), 0) AS advance_brought_forward,

        COALESCE((
            SELECT SUM(lt.amount)
            FROM labour_transactions lt
            WHERE lt.worker_id = workers.id
            AND lt.type = 'Advance'
            AND lt.transaction_date BETWEEN ? AND ?
        ), 0) AS advance_taken,

        COALESCE((
            SELECT SUM(lt.amount)
            FROM labour_transactions lt
            WHERE lt.worker_id = workers.id
            AND lt.type = 'Advance Repayment'
            AND lt.transaction_date BETWEEN ? AND ?
        ), 0) AS advance_repaid,

        COALESCE((
            SELECT SUM(
                CASE
                    WHEN lt.type = 'Advance' THEN lt.amount
                    WHEN lt.type = 'Advance Repayment' THEN -lt.amount
                    ELSE 0
                END
            )
            FROM labour_transactions lt
            WHERE lt.worker_id = workers.id
            AND lt.transaction_date <= ?
        ), 0) AS advance_balance,

        COALESCE((
            SELECT SUM(
                CASE
                    WHEN lt.type = 'Saving Deposit' THEN lt.amount
                    WHEN lt.type = 'Saving Withdrawal' THEN -lt.amount
                    ELSE 0
                END
            )
            FROM labour_transactions lt
            WHERE lt.worker_id = workers.id
            AND lt.transaction_date <= ?
        ), 0) AS saving_balance

    FROM workers
    WHERE workers.status = 'Active'
    ORDER BY workers.name ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Labour summary query failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param(
    'sssssssssss',
    $month_start,
    $month_end,
    $month_start,
    $month_end,
    $month_start,
    $month_start,
    $month_end,
    $month_start,
    $month_end,
    $month_end,
    $month_end
);

$stmt->execute();
$result = $stmt->get_result();

$summary_rows = [];
$totals = [
    'earned' => 0,
    'cash_paid' => 0,
    'advance_brought_forward' => 0,
    'advance_taken' => 0,
    'advance_repaid' => 0,
    'advance_balance' => 0,
    'net_balance' => 0,
    'saving_balance' => 0,
];

while ($row = $result->fetch_assoc()) {
    $row['earned'] = floatval($row['earned']);
    $row['cash_paid'] = floatval($row['cash_paid']);
    $row['advance_brought_forward'] = max(
        0,
        floatval($row['advance_brought_forward'])
    );
    $row['advance_taken'] = floatval($row['advance_taken']);
    $row['advance_repaid'] = floatval($row['advance_repaid']);
    $row['advance_balance'] = max(
        0,
        floatval($row['advance_balance'])
    );
    $row['saving_balance'] = floatval($row['saving_balance']);
    $row['net_balance'] =
        $row['earned']
        - $row['cash_paid'];

    foreach ($totals as $field => $value) {
        $totals[$field] += $row[$field];
    }

    $summary_rows[] = $row;
}

if (defined('WINPOS_LABOUR_EXPORT')) {
    require_once __DIR__ . '/../../includes/simple_xlsx.php';
    $headers = ['#', 'Worker', 'Earned', 'Cash Paid', 'Advance B/F', 'New Advance', 'Repaid', 'Outstanding', 'Salary Balance', 'Savings'];
    $fields = ['earned', 'cash_paid', 'advance_brought_forward', 'advance_taken', 'advance_repaid', 'advance_balance', 'net_balance', 'saving_balance'];
    $exportRows = [
        [['value' => 'Win POS — Labour monthly report', 'type' => 'string', 'style' => 1]],
        [['value' => 'Month: ' . $month, 'type' => 'string']],
        [['value' => 'Salary balance = earned minus cash paid; advances are shown separately.', 'type' => 'string']],
        [['value' => 'Outstanding advances and savings are balances as of ' . $month_end . '. Active workers only.', 'type' => 'string']],
        [],
        array_map(static fn($label) => ['value' => $label, 'type' => 'string', 'style' => 3], $headers),
    ];
    foreach ($summary_rows as $index => $row) {
        $cells = [['value' => $index + 1, 'type' => 'number'], ['value' => $row['name'], 'type' => 'string']];
        foreach ($fields as $field) {
            $cells[] = ['value' => $row[$field], 'type' => 'number', 'style' => 4];
        }
        $exportRows[] = $cells;
    }
    if (!$summary_rows) {
        $exportRows[] = [['value' => 'No active workers found.', 'type' => 'string']];
    }
    $totalCells = [['value' => 'Total', 'style' => 2], ['value' => '', 'style' => 2]];
    foreach ($fields as $field) {
        $totalCells[] = ['value' => $totals[$field], 'type' => 'number', 'style' => 5];
    }
    $exportRows[] = $totalCells;
    SimpleXlsx::download('Win_POS_Labour_Monthly_Report_' . $month . '.xlsx', [[
        'name' => 'Labour monthly report',
        'rows' => $exportRows,
        'widths' => [7, 30, 18, 18, 20, 18, 18, 20, 20, 18],
        'merges' => ['A1:J1', 'A2:J2', 'A3:J3', 'A4:J4'],
    ]]);
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

?>

<section class="page-hero">
    <div>
        <div class="page-kicker">Finance & people</div>
        <h1 class="page-title">Labour summary</h1>
        <p class="page-subtitle">
            Advances remain outstanding across months until an Advance Repayment is recorded.
        </p>
    </div>
</section>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="month">Summary month</label>
                <input
                    id="month"
                    type="month"
                    name="month"
                    class="form-control"
                    value="<?=htmlspecialchars($month);?>"
                >
            </div>

            <div class="col-sm-auto">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-search"></i>
                    View month
                </button>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-success" type="submit" formaction="export_excel.php">
                    <i class="fa fa-file-excel"></i>
                    Export Excel
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#7c3aed">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-money-check-dollar"></i></div>
                <div class="metric-label">Earned this month</div>
                <div class="metric-value"><?=number_format($totals['earned']);?></div>
                <div class="metric-meta"><?=date('F Y', strtotime($month_start));?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#2563eb">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-bank"></i></div>
                <div class="metric-label">Cash paid this month</div>
                <div class="metric-value"><?=number_format($totals['cash_paid']);?></div>
                <div class="metric-meta">Recorded cash payments</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#dc2626">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-arrow-trend-up"></i></div>
                <div class="metric-label">Outstanding advances</div>
                <div class="metric-value"><?=number_format($totals['advance_balance']);?></div>
                <div class="metric-meta">Carried forward until repaid</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card" style="--metric-color:#14b8a6">
            <div class="card-body">
                <div class="metric-icon"><i class="fa fa-piggy-bank"></i></div>
                <div class="metric-label">Savings balance</div>
                <div class="metric-value"><?=number_format($totals['saving_balance']);?></div>
                <div class="metric-meta">Balance through month end</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Worker balances · <?=date('F Y', strtotime($month_start));?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Worker</th>
                    <th class="text-end">Earned</th>
                    <th class="text-end">Cash Paid</th>
                    <th class="text-end">Advance B/F</th>
                    <th class="text-end">New Advance</th>
                    <th class="text-end">Repaid</th>
                    <th class="text-end">Outstanding</th>
                    <th class="text-end">Salary Balance</th>
                    <th class="text-end">Savings</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($summary_rows) { ?>
                    <?php foreach ($summary_rows as $index => $row) { ?>
                        <tr>
                            <td><?=$index + 1;?></td>
                            <td>
                                <a
                                    class="fw-semibold text-decoration-none"
                                    href="/labour/workers/view.php?id=<?=intval($row['id']);?>"
                                >
                                    <?=htmlspecialchars($row['name']);?>
                                </a>
                            </td>
                            <td class="text-end"><?=number_format($row['earned'], 2);?></td>
                            <td class="text-end"><?=number_format($row['cash_paid'], 2);?></td>
                            <td class="text-end"><?=number_format($row['advance_brought_forward'], 2);?></td>
                            <td class="text-end"><?=number_format($row['advance_taken'], 2);?></td>
                            <td class="text-end text-success"><?=number_format($row['advance_repaid'], 2);?></td>
                            <td class="text-end">
                                <strong class="<?=$row['advance_balance'] > 0 ? 'text-danger' : 'text-success';?>">
                                    <?=number_format($row['advance_balance'], 2);?>
                                </strong>
                            </td>
                            <td class="text-end">
                                <strong class="<?=$row['net_balance'] < 0 ? 'text-danger' : '';?>">
                                    <?=number_format($row['net_balance'], 2);?>
                                </strong>
                            </td>
                            <td class="text-end"><?=number_format($row['saving_balance'], 2);?></td>
                        </tr>
                    <?php } ?>

                    <tr class="fw-bold">
                        <td colspan="2">Total</td>
                        <td class="text-end"><?=number_format($totals['earned'], 2);?></td>
                        <td class="text-end"><?=number_format($totals['cash_paid'], 2);?></td>
                        <td class="text-end"><?=number_format($totals['advance_brought_forward'], 2);?></td>
                        <td class="text-end"><?=number_format($totals['advance_taken'], 2);?></td>
                        <td class="text-end text-success"><?=number_format($totals['advance_repaid'], 2);?></td>
                        <td class="text-end text-danger"><?=number_format($totals['advance_balance'], 2);?></td>
                        <td class="text-end"><?=number_format($totals['net_balance'], 2);?></td>
                        <td class="text-end"><?=number_format($totals['saving_balance'], 2);?></td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <i class="fa fa-user-group"></i>
                                <div>No active workers found.</div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
