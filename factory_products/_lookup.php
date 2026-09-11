<?php

if (!isset($lookup_table, $lookup_title, $lookup_icon, $lookup_description)) {
    exit('Invalid factory setup page.');
}

$allowed_tables = ['categories', 'material_types'];

if (!in_array($lookup_table, $allowed_tables, true)) {
    exit('Invalid factory setup page.');
}

if (empty($_SESSION['factory_setup_token'])) {
    $_SESSION['factory_setup_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['token'] ?? '');

    if (!hash_equals($_SESSION['factory_setup_token'], $token)) {
        $_SESSION['error'] = 'Your session expired. Please try again.';
        header('Location:' . basename($_SERVER['PHP_SELF']));
        exit();
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name === '') {
            $_SESSION['error'] = 'Please enter a name.';
        } elseif (mb_strlen($name) > 150) {
            $_SESSION['error'] = 'The name is too long.';
        } else {
            $stmt = $conn->prepare("INSERT INTO {$lookup_table} (name) VALUES (?)");
            $stmt->bind_param('s', $name);

            if ($stmt->execute()) {
                $_SESSION['success'] = $lookup_single . ' added.';
            } else {
                $_SESSION['error'] = 'That name could not be added. It may already exist.';
            }

            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM {$lookup_table} WHERE id = ?");
                $stmt->bind_param('i', $id);
                $deleted = $stmt->execute();
                $stmt->close();

                if ($deleted) {
                    $_SESSION['success'] = $lookup_single . ' deleted.';
                } else {
                    $_SESSION['error'] = 'This item cannot be deleted.';
                }
            } catch (Throwable $error) {
                $_SESSION['error'] = 'This item is already used by a factory product and cannot be deleted.';
            }
        }
    }

    header('Location:' . basename($_SERVER['PHP_SELF']));
    exit();
}

$items = $conn->query("SELECT id, name FROM {$lookup_table} ORDER BY name ASC");
$page_title = $lookup_title;

include '../includes/header.php';
include '../includes/sidebar.php';

?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1"><i class="fa <?=$lookup_icon;?> me-2"></i><?=htmlspecialchars($lookup_title);?></h2>
        <p class="text-muted mb-0"><?=htmlspecialchars($lookup_description);?></p>
    </div>
    <a href="index.php" class="btn btn-light"><i class="fa fa-arrow-left"></i> Factory products</a>
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

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body p-4">
                <h5 class="mb-3">Add <?=htmlspecialchars(strtolower($lookup_single));?></h5>
                <form method="post">
                    <input type="hidden" name="token" value="<?=htmlspecialchars($_SESSION['factory_setup_token']);?>">
                    <input type="hidden" name="action" value="add">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" type="text" name="name" class="form-control" maxlength="150" required autofocus>
                    <button type="submit" class="btn btn-primary mt-3 w-100">
                        <i class="fa fa-plus"></i> Add <?=htmlspecialchars(strtolower($lookup_single));?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?=htmlspecialchars($lookup_single);?></th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items->num_rows === 0) { ?>
                                <tr><td colspan="2" class="text-center text-muted py-5">No items yet.</td></tr>
                            <?php } ?>

                            <?php while ($item = $items->fetch_assoc()) { ?>
                                <tr>
                                    <td><strong><?=htmlspecialchars($item['name']);?></strong></td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this item?');">
                                            <input type="hidden" name="token" value="<?=htmlspecialchars($_SESSION['factory_setup_token']);?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?=(int) $item['id'];?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
