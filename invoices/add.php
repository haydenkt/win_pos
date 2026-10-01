<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

include_once '../config/database.php';
include '../includes/header.php';
include '../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| LOAD DATA
|--------------------------------------------------------------------------
*/

$customers = mysqli_query(
    $conn,
    "SELECT id, name, phone
     FROM customers
     ORDER BY name ASC"
);

$factoryProducts = mysqli_query(
    $conn,
    "SELECT
        factory_products.*,
        categories.name AS category_name,
        material_types.name AS material_name
     FROM factory_products
     LEFT JOIN categories
        ON factory_products.category_id = categories.id
     LEFT JOIN material_types
        ON factory_products.material_type_id = material_types.id
     WHERE factory_products.status = 'Active'
     ORDER BY factory_products.product_name ASC"
);

$saleProducts = mysqli_query(
    $conn,
    "SELECT *
     FROM products
     ORDER BY name ASC"
);

$glassTypes = mysqli_query(
    $conn,
    "SELECT *
     FROM glass_types
     WHERE status = 'Active'
     ORDER BY name ASC"
);


/*
|--------------------------------------------------------------------------
| GET PRODUCT PRICE
|--------------------------------------------------------------------------
|
| Factory product:
| default_price should be the original SQFT price.
|
| We keep fallbacks so older records/schema still work.
|
*/

function getProductPrice(array $row): float
{
    $priceColumns = [
        'default_price',
        'sqft_price',
        'price_per_sqft',
        'base_price',
        'selling_price',
        'sale_price',
        'price'
    ];

    foreach ($priceColumns as $column) {

        if (
            array_key_exists($column, $row)
            && $row[$column] !== ''
            && $row[$column] !== null
        ) {

            return (float) $row[$column];

        }
    }

    return 0;
}


/*
|--------------------------------------------------------------------------
| FACTORY PRODUCTS
|--------------------------------------------------------------------------
*/

$factoryProductData = [];

if ($factoryProducts) {

    while ($product = mysqli_fetch_assoc($factoryProducts)) {

        $factoryProductData[] = [

            'id' =>
                (int) $product['id'],

            'name' =>
                $product['product_name'],

            'price' =>
                getProductPrice($product)

        ];

    }

}


/*
|--------------------------------------------------------------------------
| SALE PRODUCTS
|--------------------------------------------------------------------------
*/

$saleProductData = [];

if ($saleProducts) {

    while ($product = mysqli_fetch_assoc($saleProducts)) {

        $saleProductData[] = [

            'id' =>
                (int) $product['id'],

            'name' =>
                $product['name'],

            'price' =>
                getProductPrice($product)

        ];

    }

}


/*
|--------------------------------------------------------------------------
| GLASS TYPES
|--------------------------------------------------------------------------
*/

$glassTypeData = [];

if ($glassTypes) {

    while ($glass = mysqli_fetch_assoc($glassTypes)) {

        $glassTypeData[] = [

            'id' =>
                (int) $glass['id'],

            'name' =>
                $glass['name'],

            'price' =>
                getProductPrice($glass)

        ];

    }

}

?>

<style>

/* =========================================================
   INVOICE ITEM
========================================================= */

.invoice-item {

    border: 1px solid #dee2e6;

    border-radius: 8px;

    margin-bottom: 20px;

    overflow: hidden;

    background: #fff;

}


/* ITEM HEADER */

.invoice-item__bar {

    background: #f8f9fa;

    border-bottom: 1px solid #dee2e6;

    padding: 10px 14px;

}


/* TABLE */

.invoice-item table {

    margin-bottom: 0;

}


/* SQFT TABLE */

.invoice-item.sqft table {

    min-width: 1250px;

}


/* MANUAL TABLE */

.invoice-item.manual table {

    min-width: 700px;

}


/* TABLE HEADER */

.invoice-item th {

    white-space: nowrap;

    font-size: 13px;

    vertical-align: middle;

}


/* TABLE CELLS */

.invoice-item td {

    vertical-align: middle;

}


/* PRODUCT */

.product-cell {

    min-width: 210px;

}


/* READONLY */

.invoice-item input[readonly] {

    background-color: #f8f9fa;

}


/* MONEY */

.money {

    white-space: nowrap;

    font-weight: 600;

}


/* TOTAL */

.item-total {

    font-weight: 700;

    white-space: nowrap;

}


/* MOBILE */

@media (max-width: 768px) {

    .invoice-item table {

        font-size: 12px;

    }

}

</style>


<div class="container-fluid">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-3">

    <h2>

        <i class="fa fa-file-invoice"></i>

        New Invoice

    </h2>


    <a
        href="index.php"
        class="btn btn-secondary"
    >

        <i class="fa fa-arrow-left"></i>

        Back

    </a>

</div>



<form
    method="POST"
    action="save.php"
    id="invoiceForm"
    novalidate
>


<!-- =====================================================
     CUSTOMER
===================================================== -->

<div class="card mt-3">


    <div class="card-header">

        <strong>

            <i class="fa fa-user"></i>

            Customer Information

        </strong>

    </div>


    <div class="card-body">


        <input
            type="hidden"
            name="customer_type"
            id="customer_type"
            value="existing"
        >


        <div class="row">


            <div class="col-md-8">


                <label for="customer_id">

                    Customer

                </label>


                <!-- EXISTING -->

                <div id="existingCustomerBox">

                    <select
                        name="customer_id"
                        id="customer_id"
                        class="form-select"
                    >

                        <option value="">

                            -- Select Customer --

                        </option>


                        <?php while (
                            $customer =
                            mysqli_fetch_assoc($customers)
                        ) { ?>

                            <option
                                value="<?= (int) $customer['id']; ?>"
                            >

                                <?= htmlspecialchars(
                                    $customer['name']
                                ); ?>


                                <?php if (
                                    !empty($customer['phone'])
                                ) { ?>

                                    -
                                    <?= htmlspecialchars(
                                        $customer['phone']
                                    ); ?>

                                <?php } ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>


                <!-- NEW CUSTOMER -->

                <div
                    id="newCustomerBox"
                    style="display:none;"
                >

                    <input
                        type="text"
                        name="new_name"
                        id="new_name"
                        class="form-control mb-2"
                        placeholder="Customer Name"
                    >


                    <input
                        type="text"
                        name="new_phone"
                        class="form-control mb-2"
                        placeholder="Phone"
                    >


                    <textarea
                        name="new_address"
                        class="form-control"
                        placeholder="Address"
                    ></textarea>

                </div>


            </div>


            <div class="col-md-4 pt-4">


                <button
                    type="button"
                    class="btn btn-success"
                    id="newCustomerBtn"
                >

                    <i class="fa fa-plus"></i>

                    New Customer

                </button>


                <button
                    type="button"
                    class="btn btn-secondary"
                    id="existingCustomerBtn"
                    style="display:none;"
                >

                    <i class="fa fa-users"></i>

                    Existing Customer

                </button>


            </div>


        </div>


        <hr>


        <div class="row">


            <div class="col-md-3">


                <label for="invoice_status">

                    Status

                </label>


                <select
                    name="invoice_status"
                    id="invoice_status"
                    class="form-control"
                >

                    <option value="Draft">

                        Draft

                    </option>


                    <option
                        value="Confirmed"
                        selected
                    >

                        Confirmed

                    </option>


                    <option value="Completed">

                        Completed

                    </option>

                </select>

            </div>


            <div class="col-md-9">


                <label for="notes">

                    Notes

                </label>


                <input
                    type="text"
                    name="notes"
                    id="notes"
                    class="form-control"
                >

            </div>


        </div>


    </div>

</div>



<!-- =====================================================
     ITEMS
===================================================== -->

<div class="card mt-3">


    <div class="card-header">

        <strong>

            <i class="fa fa-list"></i>

            Items

        </strong>


    </div>


    <div class="card-body">


        <p class="text-muted mb-3">

            SQFT items use measurements and glass pricing.
            MANUAL items only require quantity and price.

        </p>


        <div id="itemList"></div>


        <div class="d-flex justify-content-end mt-3">

            <button
                type="button"
                class="btn btn-primary"
                id="addItem"
            >

                <i class="fa fa-plus"></i>

                Add Item

            </button>

        </div>


    </div>

</div>



<!-- =====================================================
     TOTALS
===================================================== -->

<div class="card mt-3">


    <div class="card-header">

        <strong>

            <i class="fa fa-calculator"></i>

            Invoice Totals

        </strong>

    </div>


    <div class="card-body">


        <div class="row justify-content-end">


            <div class="col-md-5">


                <!-- SUBTOTAL -->

                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                    >

                        Subtotal

                    </label>


                    <div class="col-6">

                        <input
                            type="text"
                            id="subtotal"
                            class="form-control"
                            value="0.00"
                            readonly
                        >

                    </div>

                </div>


                <!-- DISCOUNT -->

                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                        for="discount"
                    >

                        Discount

                    </label>


                    <div class="col-6">

                        <input
                            type="number"
                            min="0"
                            step="500"
                            name="discount"
                            id="discount"
                            class="form-control"
                            value=""
                        >

                    </div>

                </div>


                <!-- INSTALLATION

                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                        for="installation_cost"
                    >

                        Installation

                    </label>


                    <div class="col-6">

                        <input
                            type="number"
                            min="0"
                            step="50"
                            name="installation_cost"
                            id="installation_cost"
                            class="form-control"
                            value="0"
                        >

                    </div>

                </div> -->


                <!-- GRAND TOTAL -->

                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                    >

                        Grand Total

                    </label>


                    <div class="col-6">

                        <input
                            type="text"
                            id="grand_total"
                            class="form-control"
                            value="0.00"
                            readonly
                        >

                    </div>

                </div>


                <!-- CASH PAYMENT -->

                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                        for="cash_payment"
                    >

                        Cash Paid

                    </label>


                    <div class="col-6">

                        <input
                            type="number"
                            min="0"
                            step="500"
                            name="cash_payment"
                            id="cash_payment"
                            class="form-control"
                            value=""
                        >

                    </div>

                </div>


                <!-- DIGITAL PAYMENT -->

                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                        for="digital_payment"
                    >

                        Digital Paid

                    </label>


                    <div class="col-6">

                        <input
                            type="number"
                            min="0"
                            step="500"
                            name="digital_payment"
                            id="digital_payment"
                            class="form-control"
                            value=""
                        >

                    </div>

                </div>


                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                        for="digital_method"
                    >

                        Digital Method

                    </label>


                    <div class="col-6">

                        <select
                            name="digital_method"
                            id="digital_method"
                            class="form-select"
                        >

                            <option value="Mobile Payment">
                                Mobile Payment
                            </option>

                            <option value="Bank Transfer">
                                Bank Transfer
                            </option>

                        </select>

                    </div>

                </div>


                <div class="row mb-2">

                    <label
                        class="col-6 col-form-label"
                        for="digital_reference"
                    >

                        Reference No.

                    </label>


                    <div class="col-6">

                        <input
                            type="text"
                            name="digital_reference"
                            id="digital_reference"
                            class="form-control"
                            placeholder="Optional"
                        >

                    </div>

                </div>


                <!-- TOTAL PAID -->

                <div class="row mb-2">

                    <label class="col-6 col-form-label">

                        Total Paid

                    </label>


                    <div class="col-6">

                        <input
                            type="text"
                            id="total_paid"
                            class="form-control"
                            value="0.00"
                            readonly
                        >

                        <input
                            type="hidden"
                            name="deposit"
                            id="deposit"
                            value="0"
                        >

                    </div>

                </div>


                <div
                    id="paymentSplitError"
                    class="small text-danger text-end mb-2"
                    aria-live="polite"
                ></div>


                <!-- BALANCE -->

                <div class="row">

                    <label
                        class="col-6 col-form-label"
                    >

                        Balance

                    </label>


                    <div class="col-6">

                        <input
                            type="text"
                            id="balance"
                            class="form-control"
                            value="0.00"
                            readonly
                        >

                    </div>

                </div>


            </div>

        </div>


    </div>

</div>



<!-- SAVE -->

<div class="mt-3 mb-4">

    <div
        id="invoiceHoldStatus"
        class="small text-muted text-end mb-2"
        aria-live="polite"
    ></div>


    <div class="d-flex justify-content-end gap-2 flex-wrap">

        <button
            type="button"
            class="btn btn-warning btn-lg"
            id="holdInvoice"
        >

            <i class="fa fa-pause"></i>

            Hold Invoice

        </button>


        <button
            type="submit"
            class="btn btn-success btn-lg"
            id="saveInvoice"
        >

            <i class="fa fa-save"></i>

            Save Invoice

        </button>

    </div>


</div>


</form>


</div>



<script>

/* =========================================================
   DATA FROM PHP
========================================================= */

const factoryProducts =
    <?= json_encode(
        $factoryProductData,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    ); ?>;


const saleProducts =
    <?= json_encode(
        $saleProductData,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    ); ?>;


const glassTypes =
    <?= json_encode(
        $glassTypeData,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    ); ?>;


const itemList =
    document.getElementById('itemList');


let productSearchId = 0;


const invoiceDraftKey =
    'win_pos_invoice_hold_v1';


let invoiceDraftTimer = null;



/* =========================================================
   HELPERS
========================================================= */

function number(value)
{
    return Number.parseFloat(value) || 0;
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

    return element.innerHTML;
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

    return `

        <input
            type="number"
            name="${name}[]"
            class="form-control"
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
                type="number"
                min="0"
                step="50"
                class="form-control base-price"
                value="${escapeHtml(
                    state.basePrice
                )}"
                placeholder="Original price"
            >

        </td>


        <!-- GLASS ADD -->

        <td>

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

                scheduleInvoiceDraftSave();

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
                width / 304.8;


            heightFt =
                height / 304.8;

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


        const price =
            number(
                item.querySelector(
                    '[name="price[]"]'
                )?.value
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

function calculateTotals()
{

    let subtotal = 0;


    document
        .querySelectorAll(
            '.item-total'
        )
        .forEach(element => {

            subtotal +=
                number(
                    element.textContent
                );

        });


    const discount =
        number(
            document.getElementById(
                'discount'
            ).value
        );


    // const installation =
    //     number(
    //         document.getElementById(
    //             'installation_cost'
    //         ).value
    //     );


    const grandTotal =
        Math.max(
            0,
            subtotal - discount
        );


    const cashPayment =
        number(
            document.getElementById(
                'cash_payment'
            ).value
        );


    const digitalPayment =
        number(
            document.getElementById(
                'digital_payment'
            ).value
        );


    const deposit =
        cashPayment
        + digitalPayment;


    const paymentIsTooHigh =
        deposit > grandTotal;


    document.getElementById(
        'paymentSplitError'
    ).textContent =
        paymentIsTooHigh
            ? 'Total paid cannot be higher than the grand total.'
            : '';


    document.getElementById(
        'subtotal'
    ).value =
        money(subtotal);


    document.getElementById(
        'grand_total'
    ).value =
        money(grandTotal);


    document.getElementById(
        'total_paid'
    ).value =
        money(deposit);


    document.getElementById(
        'deposit'
    ).value =
        money(deposit);


    document.getElementById(
        'balance'
    ).value =
        money(
            Math.max(
                0,
                grandTotal - deposit
            )
        );

}



/* =========================================================
   ADD ITEM
========================================================= */

function addItem(state = null)
{

    const item =
        document.createElement(
            'div'
        );


    itemList.appendChild(
        item
    );


    renderItem(
        item,
        state || {

            type: 'ORDER',

            product: '',

            productName: '',

            mode: 'SQFT',

            glass: '',

            unit: 'MM',

            width: '',

            height: '',

            quantity: '1',

            basePrice: '',

            price: ''

        }
    );

}


/* =========================================================
   HOLD INVOICE
========================================================= */

function invoiceDraftData()
{

    return {

        version: 1,

        savedAt: Date.now(),

        customerType:
            document.getElementById(
                'customer_type'
            ).value,

        customerId:
            document.getElementById(
                'customer_id'
            ).value,

        newName:
            document.getElementById(
                'new_name'
            ).value,

        newPhone:
            document.querySelector(
                '[name="new_phone"]'
            ).value,

        newAddress:
            document.querySelector(
                '[name="new_address"]'
            ).value,

        invoiceStatus:
            document.getElementById(
                'invoice_status'
            ).value,

        notes:
            document.getElementById(
                'notes'
            ).value,

        discount:
            document.getElementById(
                'discount'
            ).value,

        cashPayment:
            document.getElementById(
                'cash_payment'
            ).value,

        digitalPayment:
            document.getElementById(
                'digital_payment'
            ).value,

        digitalMethod:
            document.getElementById(
                'digital_method'
            ).value,

        digitalReference:
            document.getElementById(
                'digital_reference'
            ).value,

        items:
            Array.from(itemList.children)
                .map(item => itemState(item))

    };

}


function setInvoiceHoldStatus(message)
{

    document.getElementById(
        'invoiceHoldStatus'
    ).textContent = message;

}


function saveInvoiceDraft(announce = false)
{

    try {

        const draft =
            invoiceDraftData();


        window.localStorage.setItem(
            invoiceDraftKey,
            JSON.stringify(draft)
        );


        const savedTime =
            new Date(draft.savedAt)
                .toLocaleTimeString(
                    [],
                    {
                        hour: '2-digit',
                        minute: '2-digit'
                    }
                );


        setInvoiceHoldStatus(
            announce
                ? `Invoice held at ${savedTime}. It is safe to refresh.`
                : `Changes protected at ${savedTime}`
        );

    }
    catch (error) {

        setInvoiceHoldStatus(
            'This browser could not hold the invoice.'
        );

    }

}


function scheduleInvoiceDraftSave()
{

    window.clearTimeout(
        invoiceDraftTimer
    );


    invoiceDraftTimer =
        window.setTimeout(
            () => saveInvoiceDraft(false),
            350
        );

}


function currentProductState(state)
{

    const refreshedState =
        { ...state };


    const product =
        productsForType(
            refreshedState.type
        )
        .find(item =>
            String(item.id)
            === String(refreshedState.product)
        );


    if (!product) {

        return refreshedState;

    }


    if (refreshedState.mode === 'SQFT') {

        refreshedState.basePrice =
            product.price;

    }
    else {

        refreshedState.price =
            product.price;

    }


    return refreshedState;

}


function restoreInvoiceDraft()
{

    let draft;


    try {

        draft = JSON.parse(
            window.localStorage.getItem(
                invoiceDraftKey
            )
        );

    }
    catch (error) {

        try {

            window.localStorage.removeItem(
                invoiceDraftKey
            );

        }
        catch (storageError) {

            // Continue with a blank invoice when storage is unavailable.

        }

        return false;

    }


    if (
        !draft
        ||
        draft.version !== 1
        ||
        !Array.isArray(draft.items)
    ) {

        return false;

    }


    if (draft.customerType === 'new') {

        document.getElementById(
            'newCustomerBtn'
        ).click();

    }


    document.getElementById(
        'customer_id'
    ).value = draft.customerId || '';


    document.getElementById(
        'new_name'
    ).value = draft.newName || '';


    document.querySelector(
        '[name="new_phone"]'
    ).value = draft.newPhone || '';


    document.querySelector(
        '[name="new_address"]'
    ).value = draft.newAddress || '';


    document.getElementById(
        'invoice_status'
    ).value = draft.invoiceStatus || 'Confirmed';


    document.getElementById(
        'notes'
    ).value = draft.notes || '';


    document.getElementById(
        'discount'
    ).value = draft.discount || '';


    document.getElementById(
        'cash_payment'
    ).value =
        draft.cashPayment
        ?? draft.deposit
        ?? '';


    document.getElementById(
        'digital_payment'
    ).value = draft.digitalPayment || '';


    document.getElementById(
        'digital_method'
    ).value = draft.digitalMethod || 'Mobile Payment';


    document.getElementById(
        'digital_reference'
    ).value = draft.digitalReference || '';


    itemList.replaceChildren();


    draft.items.forEach(state =>
        addItem(
            currentProductState(state)
        )
    );


    if (!itemList.children.length) {

        addItem();

    }


    calculateTotals();


    const savedTime =
        draft.savedAt
            ? new Date(draft.savedAt)
                .toLocaleTimeString(
                    [],
                    {
                        hour: '2-digit',
                        minute: '2-digit'
                    }
                )
            : '';


    setInvoiceHoldStatus(
        savedTime
            ? `Held invoice restored from ${savedTime}. Product prices refreshed.`
            : 'Held invoice restored. Product prices refreshed.'
    );


    return true;

}


/* =========================================================
   CUSTOMER SWITCH
========================================================= */

document
    .getElementById(
        'addItem'
    )
    .addEventListener(
        'click',
        () => {

            addItem();

            scheduleInvoiceDraftSave();

        }
    );


document
    .getElementById(
        'newCustomerBtn'
    )
    .addEventListener(
        'click',
        () => {

            document.getElementById(
                'customer_type'
            ).value =
                'new';


            document.getElementById(
                'existingCustomerBox'
            ).style.display =
                'none';


            document.getElementById(
                'newCustomerBox'
            ).style.display =
                '';


            document.getElementById(
                'newCustomerBtn'
            ).style.display =
                'none';


            document.getElementById(
                'existingCustomerBtn'
            ).style.display =
                '';

        }
    );


document
    .getElementById(
        'existingCustomerBtn'
    )
    .addEventListener(
        'click',
        () => {

            document.getElementById(
                'customer_type'
            ).value =
                'existing';


            document.getElementById(
                'existingCustomerBox'
            ).style.display =
                '';


            document.getElementById(
                'newCustomerBox'
            ).style.display =
                'none';


            document.getElementById(
                'newCustomerBtn'
            ).style.display =
                '';


            document.getElementById(
                'existingCustomerBtn'
            ).style.display =
                'none';

        }
    );


/* =========================================================
   TOTAL INPUTS
========================================================= */

[
    'discount',
    'cash_payment',
    'digital_payment'
]
.forEach(id => {

    document
        .getElementById(id)
        .addEventListener(
            'input',
            calculateTotals
        );

});


document
    .getElementById(
        'holdInvoice'
    )
    .addEventListener(
        'click',
        () => saveInvoiceDraft(true)
    );


document
    .getElementById(
        'invoiceForm'
    )
    .addEventListener(
        'input',
        scheduleInvoiceDraftSave
    );


document
    .getElementById(
        'invoiceForm'
    )
    .addEventListener(
        'change',
        scheduleInvoiceDraftSave
    );


[
    'newCustomerBtn',
    'existingCustomerBtn'
]
.forEach(id => {

    document
        .getElementById(id)
        .addEventListener(
            'click',
            scheduleInvoiceDraftSave
        );

});


window.addEventListener(
    'beforeunload',
    () => {

        if (invoiceDraftTimer) {

            saveInvoiceDraft(false);

        }

    }
);


/* =========================================================
   FORM VALIDATION
========================================================= */

document
    .getElementById(
        'invoiceForm'
    )
    .addEventListener(
        'submit',
        event => {


            const grandTotal =
                number(
                    document.getElementById(
                        'grand_total'
                    ).value
                );


            const totalPaid =
                number(
                    document.getElementById(
                        'cash_payment'
                    ).value
                )
                +
                number(
                    document.getElementById(
                        'digital_payment'
                    ).value
                );


            if (totalPaid > grandTotal) {

                event.preventDefault();

                alert(
                    'Cash and digital payments together cannot be higher than the invoice total.'
                );

                return;

            }


            const isNew =
                document.getElementById(
                    'customer_type'
                ).value === 'new';


            const hasCustomer =
                isNew

                    ?

                document.getElementById(
                    'new_name'
                ).value.trim()

                    :

                document.getElementById(
                    'customer_id'
                ).value;


            if (
                !hasCustomer
            ) {

                event.preventDefault();

                alert(
                    'Please select or add a customer.'
                );

                return;

            }


            if (
                !itemList.children.length
            ) {

                event.preventDefault();

                alert(
                    'Please add an invoice item.'
                );

                return;

            }


            /*
             * Validate each item.
             */

            for (
                const item
                of itemList.children
            ) {


                const product =
                    item.querySelector(
                        '.product'
                    )?.value;


                const customName =
                    item.querySelector(
                        '.custom-name'
                    )?.value.trim();


                if (
                    !product
                    &&
                    !customName
                ) {

                    event.preventDefault();

                    alert(
                        'Each item needs a product or custom name.'
                    );

                    return;

                }


                /*
                 * SQFT requires dimensions.
                 */

                if (
                    item.classList.contains(
                        'sqft'
                    )
                ) {


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


                    if (
                        width <= 0
                        ||
                        height <= 0
                    ) {

                        event.preventDefault();

                        alert(
                            'Please enter width and height for SQFT items.'
                        );

                        return;

                    }

                }


                /*
                 * MANUAL requires price.
                 */

                else {


                    const price =
                        number(
                            item.querySelector(
                                '[name="price[]"]'
                            ).value
                        );


                    if (
                        price < 0
                    ) {

                        event.preventDefault();

                        alert(
                            'Please enter a valid manual price.'
                        );

                        return;

                    }

                }

            }


            /*
             * Prevent double submit.
             */

            const saveButton =
                document.getElementById(
                    'saveInvoice'
                );


            if (
                saveButton.disabled
            ) {

                event.preventDefault();

                return;

            }


            saveButton.disabled =
                true;


            saveButton.innerHTML =

                '<i class="fa fa-spinner fa-spin"></i> Saving...';

        }
    );


/* =========================================================
   START
========================================================= */

if (!restoreInvoiceDraft()) {

    addItem();

    calculateTotals();

}

</script>


<?php

include '../includes/footer.php';

?>
