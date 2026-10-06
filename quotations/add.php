<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location:../index.php'); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/items.php';
requirePermission('quotations_manage');

$customers = $conn->query('SELECT id,name,phone FROM customers ORDER BY name');
$factoryProductData = $saleProductData = $glassTypeData = [];
foreach ([
    ['factory_products', 'product_name', &$factoryProductData],
    ['products', 'name', &$saleProductData],
    ['glass_types', 'name', &$glassTypeData],
] as &$catalog) {
    [$table, $nameColumn] = $catalog;
    // Keep inactive entries available on edit, but only offer active factory/glass entries for new quotes.
    $where = $table !== 'products' && empty($_GET['id']) ? " WHERE status='Active'" : '';
    $rows = $conn->query("SELECT * FROM $table$where ORDER BY $nameColumn");
    while ($row = $rows->fetch_assoc()) {
        $catalog[2][] = ['id' => (int) $row['id'], 'name' => $row[$nameColumn], 'price' => quotationPrice($row)];
    }
}
unset($catalog);
$editId = (int) ($_GET['id'] ?? 0);
$isEdit = $editId > 0;
$quotation = ['customer_id' => 0, 'quote_date' => date('Y-m-d'), 'valid_until' => date('Y-m-d', strtotime('+7 days')), 'discount' => '', 'notes' => '', 'status' => 'Draft'];
$initialItems = [];
if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM quotations WHERE id=? AND status<>'Converted'");
    $stmt->execute([$editId]);
    $quotation = $stmt->get_result()->fetch_assoc();
    if (!$quotation) {
        $_SESSION['error'] = 'This quotation is missing or already converted.';
        header('Location:index.php'); exit;
    }
    $stmt = $conn->prepare('SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY id');
    $stmt->execute([$editId]);
    foreach ($stmt->get_result() as $item) {
        $type = $item['item_type'] ?? 'ORDER';
        $unit = $item['measurement_unit'] ?? 'MM';
        [$wf, $hf] = quotationFeet($item);
        // Older FT entries were saved only as MM (e.g. 31 ft -> 9448.8 mm).
        // Recover that unit when both measurements are exact half-foot values,
        // so the existing .5-step controls do not reject an unchanged quotation.
        if ($item['width_ft'] === null && $item['height_ft'] === null) {
            $rawWf = (float) $item['width_mm'] / 304.8;
            $rawHf = (float) $item['height_mm'] / 304.8;
            $hasFractionalMm = abs(fmod((float) $item['width_mm'], 0.5)) > 1e-7
                || abs(fmod((float) $item['height_mm'], 0.5)) > 1e-7;
            if ($hasFractionalMm && abs($rawWf * 2 - round($rawWf * 2)) < 1e-7
                && abs($rawHf * 2 - round($rawHf * 2)) < 1e-7) $unit = 'FT';
        }
        $productId = $item['product_id'] ?? $item['factory_product_id'] ?? '';
        $customName = $item['product_name'];
        $catalogProducts = $type === 'ORDER' ? $factoryProductData : ($type === 'SALE' ? $saleProductData : []);
        foreach ($catalogProducts as $product) {
            if ((string) $product['id'] === (string) $productId && $product['name'] === $customName) {
                $customName = ''; break;
            }
        }
        $initialItems[] = [
            'type' => $type, 'product' => $productId,
            'productName' => $customName, 'description' => $item['description'] ?? '',
            'mode' => $item['calculation_type'] ?? 'SQFT', 'unit' => $unit,
            'width' => $unit === 'FT' ? $wf : $item['width_mm'],
            'height' => $unit === 'FT' ? $hf : $item['height_mm'],
            'quantity' => $item['quantity'], 'basePrice' => $item['base_price'] ?? $item['unit_price'],
            'price' => $item['unit_price'], 'glass' => $item['glass_type_id'] ?? '',
            'glassPrice' => $item['glass_price'] ?? 0,
        ];
    }
}
if (empty($_SESSION['quotation_csrf'])) $_SESSION['quotation_csrf'] = bin2hex(random_bytes(32));
$draftKey = $isEdit ? 'win_pos_quotation_draft_' . $editId : 'win_pos_quotation_draft_new';
$page_title = $isEdit ? 'Edit quotation' : 'New quotation';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<style>
.invoice-item { border:1px solid var(--bs-border-color); border-radius:8px; margin-bottom:20px; overflow:hidden; background:var(--bs-body-bg); }
.invoice-item__bar { background:var(--bs-tertiary-bg); border-bottom:1px solid var(--bs-border-color); padding:10px 14px; }
.invoice-item table { margin-bottom:0; }
.invoice-item.sqft table { min-width:1250px; }
.invoice-item.manual table { min-width:700px; }
.invoice-item th { white-space:nowrap; font-size:13px; vertical-align:middle; }
.invoice-item td { vertical-align:middle; }
.product-cell { min-width:210px; }
.invoice-item input[readonly] { background-color:var(--bs-tertiary-bg); }
.money { white-space:nowrap; font-weight:600; }
.item-total { font-weight:700; white-space:nowrap; }
[data-theme="dark"] .invoice-item__bar,
[data-theme="dark"] .invoice-item input[readonly] { background-color:var(--bs-body-bg); }
@media(max-width:768px) { .invoice-item table { font-size:12px; } }
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fa fa-file-invoice"></i> <?= $isEdit ? 'Edit ' . htmlspecialchars($quotation['quote_no']) : 'New Quotation'; ?></h2>
    </div>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert"><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?></div>
    <?php endif; ?>
    <form method="post" action="<?=$isEdit ? 'update.php' : 'save.php';?>" id="quoteForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['quotation_csrf']);?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?=$editId;?>"><?php endif; ?>
        <div class="card mt-3">
            <div class="card-header"><strong><i class="fa fa-user"></i> Customer Information</strong></div>
            <div class="card-body">
                <input type="hidden" name="customer_type" id="customer_type" value="existing">
                <div class="row">
                    <div class="col-md-8">
                        <label for="customer_id">Customer</label>
                        <div id="existingCustomerBox">
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">-- Select Customer --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?=$customer['id'];?>" <?=(int)$quotation['customer_id']===(int)$customer['id']?'selected':'';?>><?=htmlspecialchars($customer['name'] . (!empty($customer['phone']) ? ' - ' . $customer['phone'] : ''));?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="newCustomerBox" style="display:none">
                            <input type="text" name="new_name" id="new_name" class="form-control mb-2" placeholder="Customer Name" maxlength="100" aria-label="New customer name">
                            <input type="text" name="new_phone" class="form-control mb-2" placeholder="Phone" maxlength="50" aria-label="New customer phone">
                            <textarea name="new_address" class="form-control" placeholder="Address" aria-label="New customer address"></textarea>
                        </div>
                    </div>
                    <div class="col-md-4 pt-4">
                        <button type="button" class="btn btn-success" id="newCustomerBtn"><i class="fa fa-plus"></i> New Customer</button>
                        <button type="button" class="btn btn-secondary" id="existingCustomerBtn" style="display:none"><i class="fa fa-users"></i> Existing Customer</button>
                    </div>
                </div>
                <hr>
                <div class="row g-3">
                    <div class="col-md-3"><label for="quote_status">Status</label><select name="status" id="quote_status" class="form-control">
                        <?php foreach (['Draft','Sent','Accepted','Rejected'] as $status): ?>
                            <option <?=$quotation['status']===$status?'selected':'';?>><?=$status;?></option>
                        <?php endforeach; ?>
                    </select></div>
                    <div class="col-md-9"><label for="notes">Notes</label><input type="text" name="notes" id="notes" class="form-control" value="<?=htmlspecialchars((string)$quotation['notes']);?>"></div>
                    <div class="col-md-3"><label for="quote_date">Quotation Date</label><input type="date" name="quote_date" id="quote_date" class="form-control" required value="<?=htmlspecialchars($quotation['quote_date']);?>"></div>
                    <div class="col-md-3"><label for="valid_until">Valid Until</label><input type="date" name="valid_until" id="valid_until" class="form-control" value="<?=htmlspecialchars((string)$quotation['valid_until']);?>"><small class="text-muted">Default validity is 1 week.</small></div>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><strong><i class="fa fa-list"></i> Items</strong></div>
            <div class="card-body">
                <p class="text-muted mb-3">SQFT items use measurements and glass pricing. MANUAL items only require quantity and price.</p>
                <div id="itemList"></div>
                <div class="d-flex justify-content-end mt-3"><button type="button" class="btn btn-primary" id="addItem"><i class="fa fa-plus"></i> Add Item</button></div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><strong><i class="fa fa-calculator"></i> Quotation Totals</strong></div>
            <div class="card-body">
                <div class="row justify-content-end"><div class="col-md-5">
                    <div class="row mb-2"><label class="col-6 col-form-label" for="subtotal">Subtotal</label><div class="col-6"><input type="text" id="subtotal" class="form-control" readonly value="0.00"></div></div>
                    <div class="row mb-2"><label class="col-6 col-form-label" for="discount">Discount</label><div class="col-6"><input type="number" min="0" step="500" name="discount" id="discount" class="form-control" value="<?=(float)$quotation['discount']>0?htmlspecialchars((string)$quotation['discount']):'';?>"></div></div>
                    <div class="row mb-2"><label class="col-6 col-form-label" for="grand_total">Grand Total</label><div class="col-6"><input type="text" id="grand_total" class="form-control" readonly value="0.00"></div></div>
                    <p class="small text-muted mt-3 mb-0">Receive payment when converting this quotation to an invoice.</p>
                </div></div>
            </div>
        </div>
        <div class="mt-3 mb-4">
            <div id="quoteHoldStatus" class="small text-muted text-end mb-2" aria-live="polite"></div>
            <div class="d-flex justify-content-end gap-2 flex-wrap">
                <button type="button" class="btn btn-warning btn-lg" id="holdQuotation"><i class="fa fa-pause"></i> Hold Quotation</button>
                <button type="submit" class="btn btn-success btn-lg" id="saveQuotation"><i class="fa fa-save"></i> <?=$isEdit?'Update':'Save';?> Quotation</button>
            </div>
        </div>
        <input type="hidden" name="form_complete" value="1">
    </form>
</div>
<script>
(() => {
const factoryProducts = <?=json_encode($factoryProductData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>;
const saleProducts = <?=json_encode($saleProductData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>;
const glassTypes = <?=json_encode($glassTypeData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>;
const initialItems = <?=json_encode($initialItems, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>;
const draftKey = <?=json_encode($draftKey);?>;
const isEdit = <?=$isEdit?'true':'false';?>;
const itemList = document.getElementById('itemList');
const form = document.getElementById('quoteForm');
let productSearchId = 0;
let draftTimer = null;
let submitting = false;
function number(value)
{
    return Number.parseFloat(
        String(value ?? '').replaceAll(',', '')
    ) || 0;
}
function formatMoneyValue(value)
{
    const raw = String(value ?? '')
        .replaceAll(',', '')
        .replace(/[^0-9.]/g, '');
    if (raw === '') {
        return '';
    }
    const hasDecimal = raw.includes('.');
    const parts = raw.split('.');
    const integer = (parts.shift() || '0')
        .replace(/^0+(?=\d)/, '');
    const decimal = parts.join('');
    const formattedInteger = integer.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        ','
    );
    return hasDecimal
        ? `${formattedInteger}.${decimal}`
        : formattedInteger;
}
function formatMoneyInput(input)
{
    if (!input) {
        return;
    }
    const oldValue = input.value;
    const oldCursor = input.selectionStart ?? oldValue.length;
    const charactersBeforeCursor = oldValue
        .slice(0, oldCursor)
        .replaceAll(',', '')
        .length;
    const formatted = formatMoneyValue(oldValue);
    input.value = formatted;
    const step = Number(input.dataset.moneyStep || 0);
    const amount = number(formatted);
    const invalidStep = formatted !== ''
        && step > 0
        && Math.abs(amount % step) > 1e-7;
    input.setCustomValidity(
        invalidStep
            ? `Price must be in steps of ${step}.`
            : ''
    );
    if (document.activeElement === input) {
        let cursor = 0;
        let characters = 0;
        while (cursor < formatted.length && characters < charactersBeforeCursor) {
            if (formatted[cursor] !== ',') {
                characters++;
            }
            cursor++;
        }
        input.setSelectionRange(cursor, cursor);
    }
}
function bindMoneyInputs(scope)
{
    scope.querySelectorAll('.money-input').forEach(input => {
        input.addEventListener('input', () => formatMoneyInput(input));
        input.addEventListener('focus', () => input.select());
        formatMoneyInput(input);
    });
}
function money(value)
{
    return number(value).toFixed(2);
}
function escapeHtml(value)
{
    const element =
        document.createElement('div');
    element.textContent =
        value ?? '';
    return element.innerHTML.replaceAll('"', '&quot;').replaceAll("'", '&#39;');
}
function selectedValue(
    item,
    name,
    fallback = ''
)
{
    if (!item) {
        return fallback;
    }
    const element =
        item.querySelector(
            `[name="${name}[]"]`
        );
    return element
        ? element.value
        : fallback;
}
/* =========================================================
   PRODUCT OPTIONS
========================================================= */
function productsForType(type)
{
    if (type === 'ORDER') {
        return factoryProducts;
    }
    if (type === 'SALE') {
        return saleProducts;
    }
    return [];
}
function productOptions(
    type,
    selected = ''
)
{
    const products =
        productsForType(type);
    let html =
        '<option value="">Select product</option>';
    products.forEach(product => {
        html += `
            <option
                value="${product.id}"
                data-price="${product.price}"
                ${
                    String(product.id)
                    === String(selected)
                    ? 'selected'
                    : ''
                }
            >
                ${escapeHtml(product.name)}
            </option>
        `;
    });
    return html;
}
function productSearchOptions(type)
{
    return productsForType(type)
        .map(product => `
            <option value="${escapeHtml(product.name)}"></option>
        `)
        .join('');
}
function selectedProductName(
    type,
    selected = ''
)
{
    const product =
        productsForType(type)
            .find(item =>
                String(item.id)
                === String(selected)
            );
    return product
        ? product.name
        : '';
}
/* =========================================================
   GLASS OPTIONS
========================================================= */
function glassOptions(
    selected = ''
)
{
    let html = `
        <option
            value=""
            data-price="0"
        >
            No Glass
        </option>
    `;
    glassTypes.forEach(glass => {
        html += `
            <option
                value="${glass.id}"
                data-price="${glass.price}"
                ${
                    String(glass.id)
                    === String(selected)
                    ? 'selected'
                    : ''
                }
            >
                ${escapeHtml(glass.name)}
            </option>
        `;
    });
    return html;
}
/* =========================================================
   INPUT
========================================================= */
function input(
    name,
    value = '',
    options = ''
)
{
    const isMoney =
        name === 'price';
    return `
        <input
            type="${isMoney ? 'text' : 'number'}"
            name="${name}[]"
            class="form-control${isMoney ? ' money-input' : ''}"
            ${isMoney ? 'inputmode="decimal" data-money-step="50"' : ''}
            value="${escapeHtml(value)}"
            ${options}
        >
    `;
}
/* =========================================================
   ITEM STATE
========================================================= */
function itemState(item)
{
    return {
        description: selectedValue(item, 'description'),
        glassPrice: selectedValue(item, 'glass_price'),
        type:
            selectedValue(
                item,
                'item_type',
                'ORDER'
            ),
        product:
            selectedValue(
                item,
                'product_id'
            ),
        productName:
            selectedValue(
                item,
                'product_name'
            ),
        mode:
            selectedValue(
                item,
                'calculation_type',
                'SQFT'
            ),
        glass:
            selectedValue(
                item,
                'glass_type_id'
            ),
        unit:
            selectedValue(
                item,
                'measurement_unit',
                'MM'
            ),
        width:
            selectedValue(
                item,
                'width'
            ),
        height:
            selectedValue(
                item,
                'height'
            ),
        quantity:
            selectedValue(
                item,
                'quantity',
                '1'
            ),
        basePrice:
            item?.querySelector(
                '.base-price'
            )?.value ?? '',
        price:
            selectedValue(
                item,
                'price',
                ''
            )
    };
}
/* =========================================================
   COMMON CELLS
========================================================= */
function commonCells(state)
{
    const searchListId =
        `invoice-product-search-${++productSearchId}`;
    const canSearchProducts =
        productsForType(state.type).length > 0;
    return `
        <!-- TYPE -->
        <td>
            <select
                name="item_type[]"
                class="form-control item-type"
            >
                <option
                    value="ORDER"
                    ${
                        state.type === 'ORDER'
                        ? 'selected'
                        : ''
                    }
                >
                    ORDER
                </option>
                <option
                    value="SALE"
                    ${
                        state.type === 'SALE'
                        ? 'selected'
                        : ''
                    }
                >
                    SALE
                </option>
                <option
                    value="SERVICE"
                    ${
                        state.type === 'SERVICE'
                        ? 'selected'
                        : ''
                    }
                >
                    SERVICE
                </option>
            </select>
        </td>
        <!-- PRODUCT -->
        <td class="product-cell">
            <input
                type="search"
                class="form-control product-search"
                list="${searchListId}"
                placeholder="${
                    canSearchProducts
                        ? 'Search product...'
                        : 'Use custom name below'
                }"
                value="${escapeHtml(
                    selectedProductName(
                        state.type,
                        state.product
                    )
                )}"
                autocomplete="off"
                ${canSearchProducts ? '' : 'disabled'}
            >
            <datalist id="${searchListId}">
                ${productSearchOptions(state.type)}
            </datalist>
            <select
                name="product_id[]"
                class="product visually-hidden"
                tabindex="-1"
                aria-hidden="true"
            >
                ${productOptions(
                    state.type,
                    state.product
                )}
            </select>
            <input
                type="text"
                name="product_name[]"
                class="form-control mt-2 custom-name"
                placeholder="Custom name"
                value="${escapeHtml(
                    state.productName
                )}"
            >
            <input type="text" name="description[]" class="form-control mt-2" placeholder="Description (optional)" value="${escapeHtml(state.description || '')}">
        </td>
    `;
}
/* =========================================================
   SQFT CELLS
========================================================= */
function sqftCells(state)
{
    return (
        commonCells(state)
        +
        `
        <!-- GLASS -->
        <td>
            <select
                name="glass_type_id[]"
                class="form-control glass"
            >
                ${glassOptions(
                    state.glass
                )}
            </select>
        </td>
        <!-- MODE -->
        <td>
            <select
                name="calculation_type[]"
                class="form-control calculation-type"
            >
                <option
                    value="SQFT"
                    selected
                >
                    SQFT
                </option>
                <option value="MANUAL">
                    MANUAL
                </option>
            </select>
        </td>
        <!-- UNIT -->
        <td>
            <select
                name="measurement_unit[]"
                class="form-control unit"
            >
                <option
                    value="MM"
                    ${
                        state.unit === 'MM'
                        ? 'selected'
                        : ''
                    }
                >
                    MM
                </option>
                <option
                    value="FT"
                    ${
                        state.unit === 'FT'
                        ? 'selected'
                        : ''
                    }
                >
                    FT
                </option>
            </select>
        </td>
        <!-- WIDTH -->
        <td>
            ${input(
                'width',
                state.width,
                'min="0" step="0.5" required'
            )}
             <input
                type="text"
                class="form-control width-ft"
                readonly
            >
        </td>
        <!-- HEIGHT -->
        <td>
            ${input(
                'height',
                state.height,
                'min="0" step="0.5" required'
            )}
            <input
                type="text"
                class="form-control height-ft"
                readonly
            >
        </td>
        <!-- QUANTITY -->
        <td>
            ${input(
                'quantity',
                state.quantity || 1,
                'min="1" step="1" required'
            )}
        </td>
        <!-- BASE PRICE -->
        <td>
            <input
                type="text"
                inputmode="decimal"
                class="form-control base-price money-input"
                name="base_price[]"
                data-money-step="50"
                value="${escapeHtml(
                    state.basePrice
                )}"
                placeholder="Original price"
            >
        </td>
        <!-- GLASS ADD -->
        <td>
            <input type="hidden" name="glass_price[]" value="">
            <input
                type="text"
                class="form-control glass-add"
                readonly
            >
        </td>
        <!-- FINAL PRICE -->
        <td>
            <input
                type="hidden"
                name="price[]"
                value="${escapeHtml(
                    state.price
                )}"
            >
            <input
                type="text"
                class="form-control final-price"
                readonly
            >
        </td>
        <!-- TOTAL -->
        <td>
            <span
                class="money item-total"
            >
                0.00
            </span>
        </td>
        `
    );
}
/* =========================================================
   MANUAL CELLS
========================================================= */
function manualCells(state)
{
    return (
        commonCells(state)
        +
        `
        <!-- MODE -->
        <td>
            <select
                name="calculation_type[]"
                class="form-control calculation-type"
            >
                <option value="SQFT">
                    SQFT
                </option>
                <option
                    value="MANUAL"
                    selected
                >
                    MANUAL
                </option>
            </select>
            <input type="hidden" name="base_price[]" value="0">
            <input type="hidden" name="glass_price[]" value="0">
            <input
                type="hidden"
                name="measurement_unit[]"
                value="MM"
            >
            <input
                type="hidden"
                name="width[]"
                value="0"
            >
            <input
                type="hidden"
                name="height[]"
                value="0"
            >
            <input
                type="hidden"
                name="glass_type_id[]"
                value=""
            >
        </td>
        <!-- QTY -->
        <td>
            ${input(
                'quantity',
                state.quantity || 1,
                'min="1" step="1" required'
            )}
        </td>
        <!-- PRICE -->
        <td>
            ${input(
                'price',
                state.price || '',
                'min="0" step="50" required'
            )}
        </td>
        <!-- TOTAL -->
        <td>
            <span
                class="money item-total"
            >
                0.00
            </span>
        </td>
        `
    );
}
/* =========================================================
   RENDER ITEM
========================================================= */
function renderItem(
    item,
    state = itemState(item)
)
{
    const isSqft =
        state.mode === 'SQFT';
    item.className =
        `invoice-item ${
            isSqft
                ? 'sqft'
                : 'manual'
        }`;
    item.innerHTML = `
        <div
            class="invoice-item__bar
                   d-flex
                   justify-content-between
                   align-items-center"
        >
            <button
                type="button"
                class="btn btn-outline-danger btn-sm remove-item"
            >
                <i class="fa fa-times"></i>
                Remove
            </button>
            <strong class="item-number">
                Item
            </strong>
        </div>
        <div class="table-responsive">
            <table
                class="table table-bordered align-middle"
            >
                <thead>
                    <tr>
                        ${
                            isSqft
                            ?
                            `
                            <th>Type</th>
                            <th>Product</th>
                            <th>Glass</th>
                            <th>Mode</th>
                            <th>Unit</th>
                            <th>Width</th>
                            <th>Height</th>
                            <th>Qty</th>
                            <th>Original Price</th>
                            <th>Glass Add</th>
                            <th>Final Price</th>
                            <th>Total</th>
                            `
                            :
                            `
                            <th>Type</th>
                            <th>Product</th>
                            <th>Mode</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            `
                        }
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        ${
                            isSqft
                                ? sqftCells(state)
                                : manualCells(state)
                        }
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    if (state.glassPrice !== undefined && state.glassPrice !== null && state.glassPrice !== '') {
        const selectedGlass = item.querySelector('.glass')?.selectedOptions[0];
        if (selectedGlass && selectedGlass.value) selectedGlass.dataset.price = number(state.glassPrice);
    }
    bindMoneyInputs(item);
    bindItem(item);
    calculateItem(item);
    updateItemNumbers();
}
function updateItemNumbers()
{
    Array.from(itemList.children)
        .forEach((item, index) => {
            const itemNumber =
                item.querySelector(
                    '.item-number'
                );
            if (itemNumber) {
                itemNumber.textContent =
                    `Item ${index + 1}`;
            }
        });
}
/* =========================================================
   BIND ITEM EVENTS
========================================================= */
function bindItem(item)
{
    item.querySelector('.glass')?.addEventListener('change', () => {
        const selected = item.querySelector('.glass').selectedOptions[0];
        selected.dataset.price = glassTypes.find(g => String(g.id) === selected.value)?.price || 0;
    });
    /* REMOVE */
    item
        .querySelector('.remove-item')
        .addEventListener(
            'click',
            () => {
                item.remove();
                updateItemNumbers();
                if (
                    !itemList.children.length
                ) {
                    addItem();
                }
                calculateTotals();
                scheduleDraftSave();
            }
        );
    /* MODE */
    item
        .querySelector(
            '.calculation-type'
        )
        .addEventListener(
            'change',
            event => {
                const state =
                    itemState(item);
                state.mode =
                    event.target.value;
                renderItem(
                    item,
                    state
                );
            }
        );
    /* TYPE */
    item
        .querySelector(
            '.item-type'
        )
        .addEventListener(
            'change',
            event => {
                const state =
                    itemState(item);
                state.type =
                    event.target.value;
                /*
                 * SERVICE and SALE
                 * use MANUAL mode.
                 */
                if (
                    state.type !== 'ORDER'
                ) {
                    state.mode =
                        'MANUAL';
                }
                state.product = '';
                state.price = '';
                state.basePrice = '';
                renderItem(
                    item,
                    state
                );
            }
        );
    /* INPUTS */
    item
        .querySelectorAll(
            'input, select'
        )
        .forEach(control => {
            if (
                control.type === 'number'
                &&
                !control.readOnly
            ) {
                control.addEventListener(
                    'focus',
                    () => control.select()
                );
            }
            if (
                !control.classList.contains(
                    'calculation-type'
                )
                &&
                !control.classList.contains(
                    'item-type'
                )
            ) {
                control.addEventListener(
                    'input',
                    () => calculateItem(item)
                );
                control.addEventListener(
                    'change',
                    () => calculateItem(item)
                );
            }
        });
    /* PRODUCT */
    const product =
        item.querySelector('.product');
    product.addEventListener(
        'change',
        () => {
            const option =
                product.selectedOptions[0];
            const price =
                option
                    ? number(
                        option.dataset.price
                    )
                    : 0;
            /*
             * ORDER + SQFT
             *
             * Original product price
             * goes into Base Price.
             */
            if (
                item.classList.contains(
                    'sqft'
                )
            ) {
                const basePrice =
                    item.querySelector(
                        '.base-price'
                    );
                if (basePrice) {
                    basePrice.value =
                        price
                            ? price
                            : '';
                }
            }
            /*
             * MANUAL
             *
             * Product price goes
             * directly into Price.
             */
            else {
                const priceInput =
                    item.querySelector(
                        '[name="price[]"]'
                    );
                if (priceInput) {
                    priceInput.value =
                        price
                            ? price
                            : '';
                }
            }
            calculateItem(item);
        }
    );
    const productSearch =
        item.querySelector('.product-search');
    if (productSearch) {
        const syncProductSelection = () => {
            const searchValue =
                productSearch.value
                    .trim()
                    .toLocaleLowerCase();
            const matchingOption =
                Array.from(product.options)
                    .find(option =>
                        option.value
                        &&
                        option.textContent
                            .trim()
                            .toLocaleLowerCase()
                            === searchValue
                    );
            const nextValue =
                matchingOption
                    ? matchingOption.value
                    : '';
            if (product.value !== nextValue) {
                product.value = nextValue;
                product.dispatchEvent(
                    new Event(
                        'change',
                        { bubbles: true }
                    )
                );
            }
        };
        productSearch.addEventListener(
            'input',
            syncProductSelection
        );
        productSearch.addEventListener(
            'change',
            syncProductSelection
        );
    }
}
/* =========================================================
   CALCULATE ITEM
========================================================= */
function calculateItem(item)
{
    const qty =
        number(
            item.querySelector(
                '[name="quantity[]"]'
            )?.value
        );
    let total = 0;
    /*
     * ======================================================
     * SQFT
     * ======================================================
     */
    if (
        item.classList.contains(
            'sqft'
        )
    ) {
        const unit =
            item.querySelector(
                '.unit'
            ).value;
        const width =
            number(
                item.querySelector(
                    '[name="width[]"]'
                ).value
            );
        const height =
            number(
                item.querySelector(
                    '[name="height[]"]'
                ).value
            );
        let widthFt =
            width;
        let heightFt =
            height;
        if (
            unit === 'MM'
        ) {
            widthFt =
                Math.ceil(
                    ((width / 304.8) * 2)
                    - 1e-9
                ) / 2;
            heightFt =
                Math.ceil(
                    ((height / 304.8) * 2)
                    - 1e-9
                ) / 2;
        }
        /*
         * Display FT
         */
        item.querySelector(
            '.width-ft'
        ).value =
            money(widthFt);
        item.querySelector(
            '.height-ft'
        ).value =
            money(heightFt);
        /*
         * PRODUCT ORIGINAL PRICE
         */
        const productPrice =
            number(
                item.querySelector(
                    '.product'
                )
                .selectedOptions[0]
                ?.dataset.price
            );
        const basePriceInput =
            item.querySelector(
                '.base-price'
            );
        /*
         * Automatically fill
         * original price if empty.
         */
        if (
            basePriceInput.value === ''
            &&
            productPrice > 0
        ) {
            basePriceInput.value =
                productPrice;
        }
        formatMoneyInput(basePriceInput);
        const basePrice =
            number(
                basePriceInput.value
            );
        /*
         * GLASS
         */
        const glassPrice =
            number(
                item.querySelector(
                    '.glass'
                )
                .selectedOptions[0]
                ?.dataset.price
            );
        /*
         * FINAL PRICE
         */
        item.querySelector('[name="glass_price[]"]').value = glassPrice;
        const finalPrice =
            basePrice +
            glassPrice;
        item.querySelector(
            '.glass-add'
        ).value =
            money(glassPrice);
        item.querySelector(
            '.final-price'
        ).value =
            money(finalPrice);
        item.querySelector(
            '[name="price[]"]'
        ).value =
            finalPrice;
        /*
         * SQFT
         */
        const sqft =
            widthFt *
            heightFt *
            qty;
        /*
         * TOTAL
         */
        total =
            sqft *
            finalPrice;
    }
    /*
     * ======================================================
     * MANUAL
     * ======================================================
     */
    else {
        const priceInput =
            item.querySelector(
                '[name="price[]"]'
            );
        formatMoneyInput(priceInput);
        const price =
            number(
                priceInput?.value
            );
        total =
            qty *
            price;
    }
    /*
     * ITEM TOTAL
     */
    item.querySelector(
    '.item-total'
).textContent =
    money(total);
// save total for PHP
let totalInput =
    item.querySelector('.item-total-input');
if (!totalInput) {
    totalInput =
        document.createElement('input');
    totalInput.type = 'hidden';
    totalInput.name = 'total[]';
    totalInput.className =
        'item-total-input';
    item.appendChild(totalInput);
}
totalInput.value = total;
    calculateTotals();
}
/* =========================================================
   CALCULATE INVOICE TOTALS
========================================================= */

function calculateTotals() {
    const subtotal = [...itemList.querySelectorAll('.item-total')].reduce((sum, el) => sum + number(el.textContent), 0);
    const discount = document.getElementById('discount');
    discount.setCustomValidity(number(discount.value) > subtotal ? 'Discount cannot exceed the subtotal.' : '');
    document.getElementById('subtotal').value = money(subtotal);
    document.getElementById('grand_total').value = money(Math.max(0, subtotal - number(discount.value)));
}
function addItem(state = {}) {
    const item = document.createElement('section');
    itemList.appendChild(item);
    renderItem(item, {
        type:'ORDER', product:'', productName:'', description:'', mode:'SQFT',
        glass:'', unit:'MM', width:'', height:'', quantity:'1', basePrice:'', price:'',
        ...state
    });
}
function setCustomerType(type) {
    const isNew = type === 'new';
    document.getElementById('customer_type').value = isNew ? 'new' : 'existing';
    document.getElementById('existingCustomerBox').style.display = isNew ? 'none' : '';
    document.getElementById('newCustomerBox').style.display = isNew ? '' : 'none';
    document.getElementById('newCustomerBtn').style.display = isNew ? 'none' : '';
    document.getElementById('existingCustomerBtn').style.display = isNew ? '' : 'none';
}
function collectDraft() {
    return {
        version:2, savedAt:new Date().toISOString(),
        customerType:document.getElementById('customer_type').value,
        customerId:document.getElementById('customer_id').value,
        newName:document.getElementById('new_name').value,
        newPhone:form.elements.new_phone.value, newAddress:form.elements.new_address.value,
        quoteDate:form.elements.quote_date.value, validUntil:form.elements.valid_until.value,
        status:form.elements.status.value, notes:form.elements.notes.value, discount:form.elements.discount.value,
        items:[...itemList.children].map(itemState)
    };
}
function saveDraft(announce = false) {
    if (submitting) return;
    clearTimeout(draftTimer); draftTimer = null;
    try {
        localStorage.setItem(draftKey, JSON.stringify(collectDraft()));
        document.getElementById('quoteHoldStatus').textContent = announce
            ? 'Quotation held. It is safe to leave or refresh this page.'
            : 'Changes protected on this device.';
    } catch (error) {
        document.getElementById('quoteHoldStatus').textContent = 'This browser could not hold the quotation. Please save before leaving.';
    }
}
function scheduleDraftSave() {
    clearTimeout(draftTimer);
    draftTimer = setTimeout(() => saveDraft(), 350);
}
function restoreDraft() {
    let draft;
    try { draft = JSON.parse(localStorage.getItem(draftKey) || 'null'); }
    catch (error) { return false; }
    if (!draft || !Array.isArray(draft.items)) return false;
    setCustomerType(draft.customerType || 'existing');
    document.getElementById('customer_id').value = draft.customerId || '';
    document.getElementById('new_name').value = draft.newName || '';
    form.elements.new_phone.value = draft.newPhone || '';
    form.elements.new_address.value = draft.newAddress || '';
    form.elements.quote_date.value = draft.quoteDate || form.elements.quote_date.value;
    form.elements.valid_until.value = draft.validUntil ?? form.elements.valid_until.value;
    form.elements.status.value = draft.status || 'Draft';
    form.elements.notes.value = draft.notes || '';
    form.elements.discount.value = draft.discount || '';
    for (let state of draft.items) {
        // Keep drafts made with the previous quotation form.
        if (!state.type) state = {
            ...state, type:'ORDER', mode:'SQFT', product:state.productId || '',
            basePrice:state.rate ?? '', price:state.rate ?? ''
        };
        if (productsForType(state.type).some(p => String(p.id) === String(state.product) && p.name === state.productName)) {
            state.productName = '';
        }
        // A new held quotation refreshes catalog prices like Add Invoice.
        // An existing quotation retains its agreed prices on edit.
        if (!isEdit) {
            const product = productsForType(state.type).find(p => String(p.id) === String(state.product));
            if (product) {
                if (state.mode === 'SQFT') state.basePrice = product.price;
                else state.price = product.price;
            }
            delete state.glassPrice;
        }
        addItem(state);
    }
    if (!itemList.children.length) addItem();
    document.getElementById('quoteHoldStatus').textContent = isEdit
        ? 'Held changes restored. Saved quotation prices retained.'
        : 'Held quotation restored. Product prices refreshed.';
    return true;
}
document.getElementById('newCustomerBtn').addEventListener('click', () => { setCustomerType('new'); scheduleDraftSave(); });
document.getElementById('existingCustomerBtn').addEventListener('click', () => { setCustomerType('existing'); scheduleDraftSave(); });
document.getElementById('addItem').addEventListener('click', () => { addItem(); scheduleDraftSave(); });
document.getElementById('holdQuotation').addEventListener('click', () => saveDraft(true));
document.getElementById('discount').addEventListener('input', calculateTotals);
form.addEventListener('input', scheduleDraftSave);
form.addEventListener('change', scheduleDraftSave);
form.elements.quote_date.addEventListener('change', () => {
    if (!isEdit && form.elements.quote_date.value) {
        const date = new Date(form.elements.quote_date.value + 'T12:00:00Z');
        date.setUTCDate(date.getUTCDate() + 7);
        form.elements.valid_until.value = date.toISOString().slice(0, 10);
    }
});
window.addEventListener('beforeunload', () => { if (draftTimer && !submitting) saveDraft(); });
form.addEventListener('submit', event => {
    if (submitting) { event.preventDefault(); return; }
    calculateTotals();
    const isNew = form.elements.customer_type.value === 'new';
    if (!(isNew ? form.elements.new_name.value.trim() : form.elements.customer_id.value)) {
        event.preventDefault(); alert('Please select or add a customer.'); return;
    }
    for (const item of itemList.children) {
        const state = itemState(item);
        if ((!state.product && !state.productName.trim()) || (state.type === 'SALE' && !state.product)) {
            event.preventDefault(); alert(state.type === 'SALE' ? 'Please select a sale product.' : 'Each item needs a product or custom name.'); return;
        }
        if (state.mode === 'SQFT' && (number(state.width) <= 0 || number(state.height) <= 0)) {
            event.preventDefault(); alert('Please enter width and height for SQFT items.'); return;
        }
    }
    if (!form.reportValidity()) { event.preventDefault(); return; }
    saveDraft();
    submitting = true;
    clearTimeout(draftTimer); draftTimer = null;
    form.querySelectorAll('.money-input[name]').forEach(input => input.value = input.value.replaceAll(',', ''));
    const button = document.getElementById('saveQuotation');
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
});
window.addEventListener('pageshow', event => {
    if (event.persisted) {
        submitting = false;
        document.getElementById('saveQuotation').disabled = false;
        document.getElementById('saveQuotation').innerHTML = '<i class="fa fa-save"></i> ' + (isEdit ? 'Update' : 'Save') + ' Quotation';
    }
});
if (!restoreDraft()) {
    if (initialItems.length) initialItems.forEach(addItem);
    else addItem();
}
calculateTotals();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
