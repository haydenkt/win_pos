<?php
$is_edit = !empty($product);
$form_action = $is_edit ? 'update.php' : 'save.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">
            <i class="fa <?=$is_edit ? 'fa-pen' : 'fa-plus';?> me-2"></i>
            <?=$is_edit ? 'Edit factory product' : 'Add factory product';?>
        </h2>
        <p class="text-muted mb-0">Set the default way this product is priced on orders and invoices.</p>
    </div>
    <a href="index.php" class="btn btn-light"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="post" action="<?=$form_action;?>">
            <?php if ($is_edit) { ?>
                <input type="hidden" name="id" value="<?=(int) $product['id'];?>">
            <?php } ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <label for="product_name" class="form-label">Product name</label>
                    <input id="product_name" type="text" name="product_name" class="form-control"
                           value="<?=htmlspecialchars($product['product_name'] ?? '');?>"
                           placeholder="Example: Sliding window" maxlength="150" required autofocus>
                </div>

                <div class="col-lg-4">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <?php foreach (['Active', 'Inactive'] as $option) { ?>
                            <option value="<?=$option;?>" <?=($product['status'] ?? 'Active') === $option ? 'selected' : '';?>><?=$option;?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label for="category_id" class="form-label">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">No category</option>
                        <?php while ($category = $categories->fetch_assoc()) { ?>
                            <option value="<?=(int) $category['id'];?>" <?=((int) ($product['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '';?>>
                                <?=htmlspecialchars($category['name']);?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label for="material_type_id" class="form-label">Profile / frame type</label>
                    <select id="material_type_id" name="material_type_id" class="form-select">
                        <option value="">No profile or frame type</option>
                        <?php while ($material = $materials->fetch_assoc()) { ?>
                            <option value="<?=(int) $material['id'];?>" <?=((int) ($product['material_type_id'] ?? 0) === (int) $material['id']) ? 'selected' : '';?>>
                                <?=htmlspecialchars($material['name']);?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="form-text">This is only a product description; raw-material inventory is not enabled.</div>
                </div>

                <div class="col-lg-6">
                    <label for="calculation_type" class="form-label">Pricing method</label>
                    <select id="calculation_type" name="calculation_type" class="form-select">
                        <option value="SQFT" <?=($product['calculation_type'] ?? 'SQFT') === 'SQFT' ? 'selected' : '';?>>Price per square foot</option>
                        <option value="MANUAL" <?=($product['calculation_type'] ?? '') === 'MANUAL' ? 'selected' : '';?>>Manual unit price</option>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label id="priceLabel" for="default_price" class="form-label">Default price per sqft</label>
                    <input id="default_price" type="number" name="default_price" class="form-control"
                           min="0" step="0.01" value="<?=htmlspecialchars((string) ($product['default_price'] ?? '0'));?>" required>
                </div>

                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional details"><?=htmlspecialchars($product['notes'] ?? '');?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="index.php" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> <?=$is_edit ? 'Save changes' : 'Add product';?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const pricingMethod = document.getElementById('calculation_type');
const priceLabel = document.getElementById('priceLabel');

function updatePriceLabel() {
    priceLabel.textContent = pricingMethod.value === 'SQFT'
        ? 'Default price per sqft'
        : 'Default unit price';
}

pricingMethod.addEventListener('change', updatePriceLabel);
updatePriceLabel();
</script>
