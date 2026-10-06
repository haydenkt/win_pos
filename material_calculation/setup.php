<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/schema.php';
requirePermission('factory_manage');
ensureMaterialCalculationSchema($conn);

if (empty($_SESSION['material_calculation_csrf'])) {
    $_SESSION['material_calculation_csrf'] = bin2hex(random_bytes(32));
}

$templates = $conn->query('SELECT * FROM material_calculation_templates ORDER BY name');
$editId = (int) ($_GET['id'] ?? 0);
$template = ['id' => 0, 'name' => '', 'description' => '', 'status' => 'Active'];
$formulas = [];

if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM material_calculation_templates WHERE id=?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $saved = $stmt->get_result()->fetch_assoc();
    if ($saved) {
        $template = $saved;
        $stmt = $conn->prepare('SELECT * FROM material_calculation_formulas WHERE template_id=? ORDER BY sort_order, id');
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $formulas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if (!$formulas) {
    $formulas[] = ['material_name' => '', 'material_name_en' => '', 'unit_label' => 'ft', 'width_factor' => 0, 'height_factor' => 0, 'area_factor' => 0, 'fixed_amount' => 0, 'usable_length_per_piece' => 18, 'heading_rule' => 'overall'];
}

$page_title = 'Material calculation setup';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-hero">
    <div>
        <h1 class="page-title">Calculation Setup</h1>
        <p class="page-subtitle">Create product templates and define how each required material is calculated.</p>
    </div>
    <a href="setup.php" class="btn btn-outline-primary"><i class="fa fa-plus me-1"></i> New product template</a>
</div>

<?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']);?></div><?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-xl-3">
        <div class="card">
            <div class="card-header"><strong>Product templates</strong></div>
            <div class="list-group list-group-flush">
                <?php while ($row = $templates->fetch_assoc()): ?>
                    <a href="setup.php?id=<?=(int)$row['id'];?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?=$editId === (int)$row['id'] ? 'active' : '';?>">
                        <span><?=htmlspecialchars($row['name']);?></span>
                        <?php if ($row['status'] !== 'Active'): ?><small>Inactive</small><?php endif; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-9">
        <form action="save_template.php" method="post" id="templateForm">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['material_calculation_csrf']);?>">
            <input type="hidden" name="id" value="<?=(int)$template['id'];?>">

            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Product details</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Product name</label><input name="name" class="form-control" value="<?=htmlspecialchars($template['name']);?>" placeholder="Example: U-PVC Slide" required></div>
                        <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="Active" <?=$template['status']==='Active'?'selected':'';?>>Active</option><option value="Inactive" <?=$template['status']==='Inactive'?'selected':'';?>>Inactive</option></select></div>
                        <div class="col-12"><label class="form-label">Description</label><input name="description" class="form-control" value="<?=htmlspecialchars($template['description']);?>" placeholder="Optional explanation"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1">Material formulas</h2>
                        <small class="text-body-secondary d-block">Result = (W factor × width) + (H factor × height) + (Area factor × width × height) + fixed</small>
                        <small class="text-body-secondary">Use 18 for an approximately 19′–19.5′ stock piece when waste is included. Use 0 when piece conversion does not apply.</small>
                        <small class="text-body-secondary d-block">Heading rule decides whether this material uses the overall height, lower-window height, top fixed-glass perimeter, or heading crossbar.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addMaterial"><i class="fa fa-plus me-1"></i> Add material</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="min-width:1480px">
                        <thead><tr><th>Material name</th><th>English name</th><th>Unit</th><th>W factor</th><th>H factor</th><th>Area factor</th><th>Fixed</th><th>Usable / piece</th><th>Heading rule</th><th></th></tr></thead>
                        <tbody id="formulaRows">
                        <?php foreach ($formulas as $formula): ?>
                            <tr class="formula-row">
                                <td><input name="material_name[]" class="form-control" value="<?=htmlspecialchars($formula['material_name']);?>" required></td>
                                <td><input name="material_name_en[]" class="form-control" value="<?=htmlspecialchars($formula['material_name_en']);?>"></td>
                                <td><input name="unit_label[]" class="form-control" value="<?=htmlspecialchars($formula['unit_label']);?>" required></td>
                                <td><input type="number" name="width_factor[]" class="form-control" step="0.0001" value="<?=htmlspecialchars((string)$formula['width_factor']);?>"></td>
                                <td><input type="number" name="height_factor[]" class="form-control" step="0.0001" value="<?=htmlspecialchars((string)$formula['height_factor']);?>"></td>
                                <td><input type="number" name="area_factor[]" class="form-control" step="0.0001" value="<?=htmlspecialchars((string)$formula['area_factor']);?>"></td>
                                <td><input type="number" name="fixed_amount[]" class="form-control" step="0.0001" value="<?=htmlspecialchars((string)$formula['fixed_amount']);?>"></td>
                                <td><input type="number" name="usable_length_per_piece[]" class="form-control" min="0" step="0.5" value="<?=htmlspecialchars((string)$formula['usable_length_per_piece']);?>" title="Usable length from one stock piece after waste"></td>
                                <td>
                                    <select name="heading_rule[]" class="form-select">
                                        <option value="overall" <?=$formula['heading_rule']==='overall'?'selected':'';?>>Use overall height</option>
                                        <option value="lower" <?=$formula['heading_rule']==='lower'?'selected':'';?>>Use lower height</option>
                                        <option value="lower_plus_perimeter" <?=$formula['heading_rule']==='lower_plus_perimeter'?'selected':'';?>>Lower + top perimeter</option>
                                        <option value="crossbar_only" <?=$formula['heading_rule']==='crossbar_only'?'selected':'';?>>Heading crossbar only</option>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-material" title="Remove"><i class="fa fa-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-2">
                <?php if ((int)$template['id'] > 0): ?>
                    <button type="submit" class="btn btn-outline-danger me-auto" form="deleteTemplateForm"><i class="fa fa-trash me-1"></i> Delete template</button>
                <?php endif; ?>
                <button class="btn btn-primary px-4"><i class="fa fa-save me-1"></i> Save template</button>
            </div>
        </form>

        <?php if ((int)$template['id'] > 0): ?>
            <form action="delete_template.php" method="post" id="deleteTemplateForm" onsubmit="return confirm('Delete this product template and all of its formulas?');">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['material_calculation_csrf']);?>">
                <input type="hidden" name="id" value="<?=(int)$template['id'];?>">
            </form>
        <?php endif; ?>
    </div>
</div>

<template id="formulaTemplate">
    <tr class="formula-row">
        <td><input name="material_name[]" class="form-control" required></td>
        <td><input name="material_name_en[]" class="form-control"></td>
        <td><input name="unit_label[]" class="form-control" value="ft" required></td>
        <td><input type="number" name="width_factor[]" class="form-control" step="0.0001" value="0"></td>
        <td><input type="number" name="height_factor[]" class="form-control" step="0.0001" value="0"></td>
        <td><input type="number" name="area_factor[]" class="form-control" step="0.0001" value="0"></td>
        <td><input type="number" name="fixed_amount[]" class="form-control" step="0.0001" value="0"></td>
        <td><input type="number" name="usable_length_per_piece[]" class="form-control" min="0" step="0.5" value="18" title="Usable length from one stock piece after waste"></td>
        <td><select name="heading_rule[]" class="form-select"><option value="overall">Use overall height</option><option value="lower">Use lower height</option><option value="lower_plus_perimeter">Lower + top perimeter</option><option value="crossbar_only">Heading crossbar only</option></select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-material"><i class="fa fa-trash"></i></button></td>
    </tr>
</template>

<script>
(() => {
    const rows = document.getElementById('formulaRows');
    const template = document.getElementById('formulaTemplate');
    const bindRemove = (button) => button.addEventListener('click', () => {
        if (rows.children.length === 1) {
            button.closest('tr').querySelectorAll('input').forEach((input) => {
                input.value = input.name === 'unit_label[]' ? 'ft' : (input.name === 'usable_length_per_piece[]' ? '18' : (input.type === 'number' ? '0' : ''));
            });
            return;
        }
        button.closest('tr').remove();
    });
    rows.querySelectorAll('.remove-material').forEach(bindRemove);
    document.getElementById('addMaterial').addEventListener('click', () => {
        const row = template.content.firstElementChild.cloneNode(true);
        bindRemove(row.querySelector('.remove-material'));
        rows.appendChild(row);
        row.querySelector('input').focus();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
