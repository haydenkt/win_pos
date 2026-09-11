<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit();
}

include '../config/database.php';
require_once '../includes/permissions.php';
requirePermission('factory_view');

$page_title = 'Factory products';
$search = trim((string) ($_GET['search'] ?? ''));
$status = (string) ($_GET['status'] ?? 'All');
$allowed_statuses = ['All', 'Active', 'Inactive'];

if (!in_array($status, $allowed_statuses, true)) {
    $status = 'All';
}

$sql = "
    SELECT
        fp.*,
        c.name AS category_name,
        mt.name AS material_name
    FROM factory_products fp
    LEFT JOIN categories c ON c.id = fp.category_id
    LEFT JOIN material_types mt ON mt.id = fp.material_type_id
    WHERE (? = '' OR fp.product_name LIKE CONCAT('%', ?, '%'))
      AND (? = 'All' OR fp.status = ?)
    ORDER BY fp.status ASC, fp.product_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $search, $search, $status, $status);
$stmt->execute();
$products = $stmt->get_result();

include '../includes/header.php';
include '../includes/sidebar.php';

?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1"><i class="fa fa-ruler-combined me-2"></i>Factory products</h2>
        <p class="text-muted mb-0">Products that can be priced automatically by square foot.</p>
    </div>

    <div class="d-flex gap-2">
        <a href="/orders/add.php" class="btn btn-outline-primary">
            <i class="fa fa-clipboard-list"></i> New sqft order
        </a>
        <a href="add.php" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add factory product
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])) { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']);?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>

<?php if (!empty($_SESSION['error'])) { ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-lg-6">
                <label class="form-label" for="search">Search products</label>
                <input id="search" type="search" name="search" class="form-control"
                       value="<?=htmlspecialchars($search);?>" placeholder="Product name">
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <?php foreach ($allowed_statuses as $option) { ?>
                        <option value="<?=$option;?>" <?=$status === $option ? 'selected' : '';?>><?=$option;?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="fa fa-search"></i> Search</button>
                <a href="index.php" class="btn btn-light">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Profile / type</th>
                        <th>Pricing</th>
                        <th class="text-end">Default price</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows === 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa fa-ruler-combined fa-2x mb-3 d-block"></i>
                                No factory products found.
                            </td>
                        </tr>
                    <?php } ?>

                    <?php while ($product = $products->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <strong><?=htmlspecialchars($product['product_name']);?></strong>
                                <?php if (trim((string) $product['notes']) !== '') { ?>
                                    <div class="small text-muted mt-1"><?=htmlspecialchars($product['notes']);?></div>
                                <?php } ?>
                            </td>
                            <td><?=htmlspecialchars($product['category_name'] ?? '—');?></td>
                            <td><?=htmlspecialchars($product['material_name'] ?? '—');?></td>
                            <td>
                                <span class="badge <?=$product['calculation_type'] === 'SQFT' ? 'bg-info' : 'bg-secondary';?>">
                                    <?=$product['calculation_type'] === 'SQFT' ? 'Per sqft' : 'Manual';?>
                                </span>
                            </td>
                            <td class="text-end"><?=number_format((float) $product['default_price'], 2);?></td>
                            <td>
                                <span class="badge <?=$product['status'] === 'Active' ? 'bg-success' : 'bg-secondary';?>">
                                    <?=htmlspecialchars($product['status']);?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="edit.php?id=<?=(int) $product['id'];?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-pen"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$stmt->close();
include '../includes/footer.php';
?>
