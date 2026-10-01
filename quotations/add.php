<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location:../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
requirePermission('quotations_manage');

$customers = $conn->query('SELECT id, name, phone FROM customers ORDER BY name');
$factoryResult = $conn->query("SELECT id, product_name, default_price FROM factory_products WHERE status='Active' ORDER BY product_name");
$factoryProducts = [];
while ($product = $factoryResult->fetch_assoc()) {
    $factoryProducts[] = [
        'id' => (int) $product['id'],
        'name' => $product['product_name'],
        'price' => (float) $product['default_price'],
    ];
}

$editId = (int) ($_GET['id'] ?? 0);
$isEdit = $editId > 0;
$quotation = [
    'customer_id' => 0,
    'quote_date' => date('Y-m-d'),
    'valid_until' => date('Y-m-d', strtotime('+30 days')),
    'discount' => '',
    'notes' => '',
];
$initialItems = [];

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM quotations WHERE id=? AND status<>'Converted'");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $savedQuotation = $stmt->get_result()->fetch_assoc();

    if (!$savedQuotation) {
        $_SESSION['error'] = 'This quotation cannot be edited because it is missing or already converted.';
        header('Location:index.php');
        exit;
    }

    $quotation = $savedQuotation;
    $stmt = $conn->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY id');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $savedItems = $stmt->get_result();

    while ($item = $savedItems->fetch_assoc()) {
        $initialItems[] = [
            'productId' => (int) $item['factory_product_id'],
            'productName' => $item['product_name'],
            'description' => $item['description'],
            'width' => (float) $item['width_mm'],
            'height' => (float) $item['height_mm'],
            'quantity' => (int) $item['quantity'],
            'rate' => (float) $item['unit_price'],
        ];

        $knownProduct = false;
        foreach ($factoryProducts as $factoryProduct) {
            if ($factoryProduct['id'] === (int) $item['factory_product_id']) {
                $knownProduct = true;
                break;
            }
        }
        if (!$knownProduct) {
            $factoryProducts[] = [
                'id' => (int) $item['factory_product_id'],
                'name' => $item['product_name'],
                'price' => (float) $item['unit_price'],
            ];
        }
    }
}

if (empty($_SESSION['quotation_csrf'])) {
    $_SESSION['quotation_csrf'] = bin2hex(random_bytes(32));
}

$page_title = $isEdit ? 'Edit quotation' : 'New quotation';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .quote-item {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.85rem;
        overflow: visible;
    }
    .quote-item + .quote-item { margin-top: 1rem; }
    .quote-item-head {
        align-items: center;
        background: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
        border-radius: 0.85rem 0.85rem 0 0;
        display: flex;
        justify-content: space-between;
        padding: 0.7rem 0.9rem;
    }
    .quote-item-number {
        align-items: center;
        background: var(--bs-primary);
        border-radius: 50%;
        color: #fff;
        display: inline-flex;
        font-size: 0.78rem;
        font-weight: 700;
        height: 1.8rem;
        justify-content: center;
        margin-right: 0.5rem;
        width: 1.8rem;
    }
    .quote-table { min-width: 980px; }
    .quote-product-cell { min-width: 270px; width: 32%; }
    .quote-measure-cell { min-width: 120px; }
    .quote-qty-cell { min-width: 90px; }
    .quote-total-cell { min-width: 135px; }
    .product-picker { position: relative; }
    .product-results {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.65rem;
        box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.14);
        display: none;
        left: 0;
        max-height: 260px;
        overflow-y: auto;
        position: absolute;
        right: 0;
        top: calc(100% + 0.35rem);
        z-index: 1080;
    }
    .product-results.show { display: block; }
    .product-result {
        background: transparent;
        border: 0;
        border-bottom: 1px solid var(--bs-border-color);
        color: var(--bs-body-color);
        display: flex;
        gap: 0.75rem;
        justify-content: space-between;
        padding: 0.7rem 0.8rem;
        text-align: left;
        width: 100%;
    }
    .product-result:last-child { border-bottom: 0; }
    .product-result:hover, .product-result:focus { background: var(--bs-tertiary-bg); }
    .product-result-price { color: var(--bs-secondary-color); font-size: 0.8rem; white-space: nowrap; }
    .quote-summary-row {
        align-items: center;
        display: flex;
        justify-content: space-between;
        padding: 0.45rem 0;
    }
    .quote-summary-total {
        border-top: 1px solid var(--bs-border-color);
        font-size: 1.15rem;
        margin-top: 0.5rem;
        padding-top: 1rem;
    }
    @media (max-width: 767.98px) {
        .page-hero { align-items: flex-start; }
        .quote-actions .btn { width: 100%; }
    }
</style>

<div class="page-hero">
    <div>
        <a href="index.php" class="btn btn-sm btn-outline-secondary mb-3">
            <i class="fa fa-arrow-left me-1"></i> Back to quotations
        </a>
        <h1 class="page-title"><?= $isEdit ? 'Edit ' . htmlspecialchars($quotation['quote_no']) : 'New quotation'; ?></h1>
        <p class="page-subtitle"><?= $isEdit ? 'Update the customer, measurements, prices, or notes.' : 'Prepare a professional estimate with automatic square-foot pricing.'; ?></p>
    </div>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="alert alert-success d-none" id="heldNotice" role="status">
    <i class="fa fa-pause-circle me-2"></i>Quotation held safely on this device. You can leave this page and continue later.
</div>

<form action="<?= $isEdit ? 'update.php' : 'save.php'; ?>" method="post" id="quoteForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['quotation_csrf']); ?>">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $editId; ?>"><?php endif; ?>
    <input type="hidden" name="draft_key" value="<?= $isEdit ? 'win_pos_quotation_draft_' . $editId : 'win_pos_quotation_draft_new'; ?>">

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa fa-user me-2"></i>Customer &amp; quotation details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select" required>
                        <option value="">Choose customer</option>
                        <?php while ($customer = $customers->fetch_assoc()): ?>
                            <option value="<?= (int) $customer['id']; ?>" <?= (int) $quotation['customer_id'] === (int) $customer['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($customer['name'] . ($customer['phone'] ? ' · ' . $customer['phone'] : '')); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="quote_date">Quotation date <span class="text-danger">*</span></label>
                    <input type="date" name="quote_date" id="quote_date" value="<?= htmlspecialchars((string) $quotation['quote_date']); ?>" class="form-control" required>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="valid_until">Valid until</label>
                    <input type="date" name="valid_until" id="valid_until" value="<?= htmlspecialchars((string) $quotation['valid_until']); ?>" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between gap-3">
            <h5 class="mb-0"><i class="fa fa-list me-2"></i>Quotation items</h5>
            <span class="badge text-bg-primary" id="itemCount">1 item</span>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 mb-3">
                <i class="fa fa-circle-info me-2"></i>Measurements are rounded up to the nearest half foot for billing.
            </div>
            <div id="items"></div>
            <button type="button" id="addRow" class="btn btn-outline-primary w-100 mt-3">
                <i class="fa fa-plus me-2"></i>Add item
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa fa-note-sticky me-2"></i>Notes</h5></div>
                <div class="card-body">
                    <label class="form-label" for="notes">Customer notes or quotation terms</label>
                    <textarea name="notes" id="notes" rows="7" class="form-control" placeholder="Add installation details, delivery terms, exclusions, or other information..."><?= htmlspecialchars((string) $quotation['notes']); ?></textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fa fa-calculator me-2"></i>Summary</h5></div>
                <div class="card-body">
                    <div class="quote-summary-row">
                        <span class="text-body-secondary">Subtotal</span>
                        <strong><span id="subtotalText">0.00</span> MMK</strong>
                    </div>
                    <div class="my-3">
                        <label class="form-label" for="discount">Discount</label>
                        <div class="input-group">
                            <input type="number" min="0" step="500" name="discount" id="discount" class="form-control" placeholder="0" value="<?= (float) $quotation['discount'] > 0 ? htmlspecialchars((string) $quotation['discount']) : ''; ?>">
                            <span class="input-group-text">MMK</span>
                        </div>
                        <div class="invalid-feedback">Discount cannot be more than the subtotal.</div>
                    </div>
                    <div class="quote-summary-row quote-summary-total">
                        <span>Total</span>
                        <strong class="text-primary"><span id="totalText">0.00</span> MMK</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="quote-actions d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4">
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="button" class="btn btn-warning" id="holdQuotation">
            <i class="fa fa-pause me-2"></i><?= $isEdit ? 'Hold changes' : 'Hold quotation'; ?>
        </button>
        <button type="submit" class="btn btn-primary px-4">
            <i class="fa fa-save me-2"></i><?= $isEdit ? 'Update quotation' : 'Save quotation'; ?>
        </button>
    </div>
</form>

<template id="itemTemplate">
    <section class="quote-item">
        <div class="quote-item-head">
            <strong><span class="quote-item-number">1</span>Item <span class="item-label">1</span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove" title="Delete item">
                <i class="fa fa-trash me-1"></i><span class="d-none d-sm-inline">Delete</span>
            </button>
        </div>
        <div class="table-responsive">
            <table class="table quote-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="quote-product-cell">Product</th>
                        <th class="quote-measure-cell">Width (mm)</th>
                        <th class="quote-measure-cell">Height (mm)</th>
                        <th class="quote-qty-cell">Qty</th>
                        <th class="quote-total-cell">Billable sqft</th>
                        <th class="quote-total-cell">Price / sqft</th>
                        <th class="quote-total-cell">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="quote-product-cell">
                            <div class="product-picker">
                                <input type="search" class="form-control product-search" placeholder="Search factory product..." autocomplete="off" required>
                                <input type="hidden" name="factory_product_id[]" class="product-id">
                                <div class="product-results" role="listbox"></div>
                            </div>
                            <input name="description[]" class="form-control mt-2" placeholder="Description (optional)">
                        </td>
                        <td><input type="number" min="0.5" step="0.5" name="width_mm[]" class="form-control width" placeholder="0" required></td>
                        <td><input type="number" min="0.5" step="0.5" name="height_mm[]" class="form-control height" placeholder="0" required></td>
                        <td><input type="number" min="1" step="1" name="quantity[]" value="1" class="form-control qty" required></td>
                        <td>
                            <strong class="sqft">0.00</strong>
                            <input type="hidden" name="sqft[]" class="sqftInput">
                            <small class="selling-size d-block text-body-secondary mt-1">0 ft × 0 ft</small>
                        </td>
                        <td><input type="number" min="0" step="50" name="unit_price[]" class="form-control rate" placeholder="0" required></td>
                        <td>
                            <strong><span class="lineTotal">0.00</span></strong>
                            <input type="hidden" name="line_total[]" class="lineTotalInput">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script>
(() => {
    const products = <?= json_encode($factoryProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const initialItems = <?= json_encode($initialItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const draftKey = <?= json_encode($isEdit ? 'win_pos_quotation_draft_' . $editId : 'win_pos_quotation_draft_new'); ?>;
    const items = document.getElementById('items');
    const template = document.getElementById('itemTemplate');
    const discount = document.getElementById('discount');
    const form = document.getElementById('quoteForm');
    const money = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const closeProductResults = (except = null) => {
        items.querySelectorAll('.product-results.show').forEach((box) => {
            if (box !== except) box.classList.remove('show');
        });
    };

    const updateItemNumbers = () => {
        const rows = [...items.querySelectorAll('.quote-item')];
        rows.forEach((row, index) => {
            row.querySelector('.quote-item-number').textContent = index + 1;
            row.querySelector('.item-label').textContent = index + 1;
            row.querySelector('.remove').disabled = rows.length === 1;
        });
        document.getElementById('itemCount').textContent = `${rows.length} ${rows.length === 1 ? 'item' : 'items'}`;
    };

    const calculateTotals = () => {
        let subtotal = 0;
        items.querySelectorAll('.lineTotalInput').forEach((input) => {
            subtotal += Number(input.value) || 0;
        });
        const discountValue = Number(discount.value) || 0;
        const discountInvalid = discountValue > subtotal && discountValue > 0;
        discount.classList.toggle('is-invalid', discountInvalid);
        discount.setCustomValidity(discountInvalid ? 'Discount cannot be more than the subtotal.' : '');
        document.getElementById('subtotalText').textContent = money.format(subtotal);
        document.getElementById('totalText').textContent = money.format(Math.max(0, subtotal - discountValue));
    };

    const calculateRow = (row) => {
        const width = Number(row.querySelector('.width').value) || 0;
        const height = Number(row.querySelector('.height').value) || 0;
        const quantity = Number(row.querySelector('.qty').value) || 0;
        const rate = Number(row.querySelector('.rate').value) || 0;
        const widthFeet = Math.ceil((width / 304.8) * 2) / 2;
        const heightFeet = Math.ceil((height / 304.8) * 2) / 2;
        const squareFeet = widthFeet * heightFeet * quantity;
        const lineTotal = squareFeet * rate;
        row.querySelector('.sqft').textContent = squareFeet.toFixed(2);
        row.querySelector('.sqftInput').value = squareFeet.toFixed(2);
        row.querySelector('.selling-size').textContent = `${widthFeet.toFixed(1)} ft × ${heightFeet.toFixed(1)} ft`;
        row.querySelector('.lineTotal').textContent = money.format(lineTotal);
        row.querySelector('.lineTotalInput').value = lineTotal.toFixed(2);
        calculateTotals();
    };

    const chooseProduct = (row, product) => {
        const search = row.querySelector('.product-search');
        search.value = product.name;
        search.setCustomValidity('');
        row.querySelector('.product-id').value = product.id;
        row.querySelector('.rate').value = product.price > 0 ? product.price : '';
        row.querySelector('.product-results').classList.remove('show');
        calculateRow(row);
    };

    const showProductResults = (row) => {
        const search = row.querySelector('.product-search');
        const resultBox = row.querySelector('.product-results');
        const query = search.value.trim().toLocaleLowerCase();
        const matches = products.filter((product) => !query || product.name.toLocaleLowerCase().includes(query)).slice(0, 10);
        resultBox.replaceChildren();
        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'p-3 text-body-secondary small';
            empty.textContent = 'No matching factory product found.';
            resultBox.appendChild(empty);
        } else {
            matches.forEach((product) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'product-result';
                const name = document.createElement('span');
                name.textContent = product.name;
                const price = document.createElement('span');
                price.className = 'product-result-price';
                price.textContent = product.price > 0 ? `${money.format(product.price)} / sqft` : 'No default price';
                button.append(name, price);
                button.addEventListener('click', () => chooseProduct(row, product));
                resultBox.appendChild(button);
            });
        }
        closeProductResults(resultBox);
        resultBox.classList.add('show');
    };

    const addItem = (data = {}) => {
        const row = template.content.firstElementChild.cloneNode(true);
        const search = row.querySelector('.product-search');
        row.querySelectorAll('.width, .height, .qty, .rate').forEach((input) => {
            input.addEventListener('input', () => calculateRow(row));
        });
        search.addEventListener('focus', () => showProductResults(row));
        search.addEventListener('input', () => {
            row.querySelector('.product-id').value = '';
            search.setCustomValidity('Please choose a product from the search results.');
            showProductResults(row);
        });
        row.querySelector('.remove').addEventListener('click', () => {
            row.remove();
            updateItemNumbers();
            calculateTotals();
        });
        items.appendChild(row);

        const selectedProduct = products.find((product) => Number(product.id) === Number(data.productId));
        if (selectedProduct) {
            chooseProduct(row, selectedProduct);
        } else if (data.productId) {
            row.querySelector('.product-id').value = data.productId;
            search.value = data.productName || '';
            search.setCustomValidity('');
        }
        row.querySelector('[name="description[]"]').value = data.description || '';
        row.querySelector('.width').value = data.width || '';
        row.querySelector('.height').value = data.height || '';
        row.querySelector('.qty').value = data.quantity || 1;
        row.querySelector('.rate').value = data.rate || '';

        updateItemNumbers();
        calculateRow(row);
    };

    const collectDraft = () => ({
        customerId: document.getElementById('customer_id').value,
        quoteDate: document.getElementById('quote_date').value,
        validUntil: document.getElementById('valid_until').value,
        notes: document.getElementById('notes').value,
        discount: discount.value,
        items: [...items.querySelectorAll('.quote-item')].map((row) => ({
            productId: row.querySelector('.product-id').value,
            productName: row.querySelector('.product-search').value,
            description: row.querySelector('[name="description[]"]').value,
            width: row.querySelector('.width').value,
            height: row.querySelector('.height').value,
            quantity: row.querySelector('.qty').value,
            rate: row.querySelector('.rate').value,
        })),
        heldAt: new Date().toISOString(),
    });

    const loadDraft = (draft) => {
        document.getElementById('customer_id').value = draft.customerId || '';
        document.getElementById('quote_date').value = draft.quoteDate || '';
        document.getElementById('valid_until').value = draft.validUntil || '';
        document.getElementById('notes').value = draft.notes || '';
        discount.value = draft.discount || '';
        items.replaceChildren();
        (draft.items?.length ? draft.items : [{}]).forEach(addItem);
        calculateTotals();
    };

    document.getElementById('addRow').addEventListener('click', () => addItem());
    document.getElementById('holdQuotation').addEventListener('click', () => {
        localStorage.setItem(draftKey, JSON.stringify(collectDraft()));
        document.getElementById('heldNotice').classList.remove('d-none');
        document.getElementById('heldNotice').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    discount.addEventListener('input', calculateTotals);
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.product-picker')) closeProductResults();
    });
    form.addEventListener('submit', (event) => {
        let firstInvalidProduct = null;
        items.querySelectorAll('.quote-item').forEach((row) => {
            const search = row.querySelector('.product-search');
            if (!row.querySelector('.product-id').value) {
                search.setCustomValidity('Please choose a product from the search results.');
                firstInvalidProduct ||= search;
            } else {
                search.setCustomValidity('');
            }
        });
        calculateTotals();
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            (firstInvalidProduct || form.querySelector(':invalid'))?.focus();
        }
    });
    let heldDraft = null;
    try {
        heldDraft = JSON.parse(localStorage.getItem(draftKey) || 'null');
    } catch (error) {
        localStorage.removeItem(draftKey);
    }

    if (heldDraft) {
        loadDraft(heldDraft);
        document.getElementById('heldNotice').classList.remove('d-none');
    } else if (initialItems.length) {
        initialItems.forEach(addItem);
    } else {
        addItem();
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
