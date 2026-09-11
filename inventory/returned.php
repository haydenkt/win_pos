<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include_once '../config/database.php';
require_once '../includes/permissions.php';

requirePermission('returned_inventory_view');

$permission_data = loadUserPermissions();
$can_undo_return = !empty($permission_data['is_admin']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

if (!in_array($status, ['Available', 'Sold'], true)) {
    $status = '';
}

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(ri.product_name LIKE ? OR ri.category LIKE ? OR invoices.invoice_no LIKE ?)';
    $search_value = '%' . $search . '%';
    $params = [$search_value, $search_value, $search_value];
    $types = 'sss';
}

if ($status === 'Available') {
    $where[] = "ri.quantity_available > 0 AND ri.status = 'Available'";
}
elseif ($status === 'Sold') {
    $where[] = "(ri.quantity_available <= 0 OR ri.status <> 'Available')";
}

$where_sql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

$summary_query = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total_rows,
        COALESCE(SUM(quantity), 0) AS total_returned,
        COALESCE(SUM(quantity_available), 0) AS total_available,
        COALESCE(SUM(quantity_available * selling_price), 0) AS available_value
    FROM returned_inventory
");

if (!$summary_query) {
    die('Returned inventory summary failed: ' . htmlspecialchars($conn->error));
}

$summary = mysqli_fetch_assoc($summary_query);

$sql = "
    SELECT
        ri.*,
        invoices.invoice_no,
        sold_invoice.invoice_no AS sold_invoice_no
    FROM returned_inventory ri
    LEFT JOIN invoices
        ON invoices.id = ri.invoice_id
    LEFT JOIN invoices sold_invoice
        ON sold_invoice.id = ri.sold_invoice_id
    $where_sql
    ORDER BY ri.id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Returned inventory query failed: ' . htmlspecialchars($conn->error));
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

include '../includes/header.php';
include '../includes/sidebar.php';

?>

<div class="container-fluid">

    <?php if (!empty($_SESSION['success'])) { ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            <?=htmlspecialchars($_SESSION['success']);?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php } ?>

    <?php if (!empty($_SESSION['error'])) { ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            <?=htmlspecialchars($_SESSION['error']);?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php } ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">
            <i class="fa fa-rotate-left"></i>
            Returned Inventory
        </h2>

        <a href="/products/index.php" class="btn btn-outline-secondary">
            <i class="fa fa-box"></i>
            Products
        </a>
    </div>

    <div class="row g-3 mb-3">

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted mb-1">Return Records</div>
                    <div class="fs-4 fw-bold">
                        <?=number_format(floatval($summary['total_rows'] ?? 0));?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted mb-1">Total Returned</div>
                    <div class="fs-4 fw-bold">
                        <?=number_format(floatval($summary['total_returned'] ?? 0), 2);?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted mb-1">Available Quantity</div>
                    <div class="fs-4 fw-bold text-success">
                        <?=number_format(floatval($summary['total_available'] ?? 0), 2);?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted mb-1">Available Value</div>
                    <div class="fs-4 fw-bold">
                        <?=number_format(floatval($summary['available_value'] ?? 0), 2);?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card mb-3">
        <div class="card-body">

            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label" for="search">Search</label>
                    <input
                        id="search"
                        type="search"
                        name="search"
                        class="form-control"
                        placeholder="Product, category or invoice number"
                        value="<?=htmlspecialchars($search);?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="Available" <?=$status === 'Available' ? 'selected' : '';?>>
                            Available
                        </option>
                        <option value="Sold" <?=$status === 'Sold' ? 'selected' : '';?>>
                            Sold / unavailable
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i>
                        Filter
                    </button>
                </div>
            </form>

        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Returned</th>
                            <th>Available</th>
                            <th>Unit Price</th>
                            <th>Available Value</th>
                            <th>Source Invoice</th>
                            <th>Status</th>
                            <th>Returned Date</th>
                            <?php if ($can_undo_return) { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>

                    <tbody>

                    <?php

                    $number = 1;

                    while ($row = $result->fetch_assoc()) {

                        $width_ft = floatval($row['width_ft'] ?? 0);
                        $height_ft = floatval($row['height_ft'] ?? 0);
                        $width_mm = floatval($row['width_mm'] ?? 0);
                        $height_mm = floatval($row['height_mm'] ?? 0);
                        $available = floatval($row['quantity_available'] ?? 0);
                        $unit_price = floatval($row['selling_price'] ?? 0);
                        $is_available =
                            $available > 0
                            && ($row['status'] ?? '') === 'Available';

                    ?>

                        <tr>
                            <td><?=$number++;?></td>

                            <td>
                                <strong><?=htmlspecialchars($row['product_name'] ?? '');?></strong>

                                <?php if (!empty($row['description'])) { ?>
                                    <div class="small text-muted mt-1">
                                        <?=nl2br(htmlspecialchars($row['description']));?>
                                    </div>
                                <?php } ?>
                            </td>

                            <td><?=htmlspecialchars($row['category'] ?: '-');?></td>

                            <td class="text-nowrap">
                                <?php

                                if ($width_ft > 0 || $height_ft > 0) {
                                    echo number_format($width_ft, 2)
                                        . ' × '
                                        . number_format($height_ft, 2)
                                        . ' ft';
                                }
                                elseif ($width_mm > 0 || $height_mm > 0) {
                                    echo number_format($width_mm, 0)
                                        . ' × '
                                        . number_format($height_mm, 0)
                                        . ' mm';
                                }
                                else {
                                    echo '-';
                                }

                                ?>
                            </td>

                            <td><?=number_format(floatval($row['quantity'] ?? 0), 2);?></td>

                            <td>
                                <strong class="<?=$available > 0 ? 'text-success' : 'text-muted';?>">
                                    <?=number_format($available, 2);?>
                                </strong>
                            </td>

                            <td class="text-nowrap"><?=number_format($unit_price, 2);?></td>

                            <td class="text-nowrap">
                                <?=number_format($available * $unit_price, 2);?>
                            </td>

                            <td>
                                <?php if (!empty($row['invoice_id'])) { ?>
                                    <a href="/invoices/view.php?id=<?=intval($row['invoice_id']);?>">
                                        <?=htmlspecialchars($row['invoice_no'] ?: ('#' . $row['invoice_id']));?>
                                    </a>
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>

                            <td>
                                <?php if ($is_available) { ?>
                                    <span class="badge bg-success">Available</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Sold / unavailable</span>

                                    <?php if (!empty($row['sold_invoice_id'])) { ?>
                                        <div class="small mt-1">
                                            <a href="/invoices/view.php?id=<?=intval($row['sold_invoice_id']);?>">
                                                <?=htmlspecialchars(
                                                    $row['sold_invoice_no']
                                                    ?: ('Invoice #' . $row['sold_invoice_id'])
                                                );?>
                                            </a>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </td>

                            <td class="text-nowrap">
                                <?php

                                $returned_date = $row['returned_at']
                                    ?? $row['created_at']
                                    ?? '';

                                echo $returned_date !== ''
                                    ? htmlspecialchars(date('d M Y, H:i', strtotime($returned_date)))
                                    : '-';

                                ?>
                            </td>

                            <?php if ($can_undo_return) { ?>
                                <td>
                                    <?php if ($is_available) { ?>
                                        <form
                                            method="POST"
                                            action="/inventory/undo_return.php"
                                            onsubmit="return confirm('Undo this return? Product stock and return history will be reversed.');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?=htmlspecialchars($_SESSION['csrf_token']);?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?=intval($row['id']);?>"
                                            >
                                            <button type="submit" class="btn btn-outline-danger btn-sm text-nowrap">
                                                <i class="fa fa-rotate-left"></i>
                                                Undo Return
                                            </button>
                                        </form>
                                    <?php } else { ?>
                                        <span class="text-muted">Not reversible</span>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>

                    <?php } ?>

                    <?php if ($result->num_rows === 0) { ?>
                        <tr>
                            <td colspan="<?=$can_undo_return ? 12 : 11;?>" class="text-center py-5 text-muted">
                                <i class="fa fa-box-open fa-2x mb-2"></i>
                                <div>No returned inventory matches these filters.</div>
                            </td>
                        </tr>
                    <?php } ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
