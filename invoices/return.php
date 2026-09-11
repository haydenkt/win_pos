<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location:../index.php");
    exit();
}

include_once "../config/database.php";


// =====================================================
// CHECK INVOICE ID
// =====================================================

if (!isset($_GET['id'])) {
    die("Invoice ID missing");
}

$invoice_id = intval($_GET['id']);

if ($invoice_id <= 0) {
    die("Invalid invoice ID");
}


// =====================================================
// GET INVOICE
// =====================================================

$stmt = $conn->prepare("

    SELECT

        invoices.*,

        customers.name AS customer_name,

        customers.phone AS customer_phone,

        customers.address AS customer_address

    FROM invoices

    LEFT JOIN customers

        ON invoices.customer_id = customers.id

    WHERE invoices.id = ?

    LIMIT 1

");


if (!$stmt) {
    die(
        "Invoice query failed: "
        . $conn->error
    );
}


$stmt->bind_param(
    "i",
    $invoice_id
);


$stmt->execute();


$invoice_result =
    $stmt->get_result();


$invoice =
    $invoice_result->fetch_assoc();


if (!$invoice) {
    die("Invoice not found");
}


// =====================================================
// GET ORDER ITEMS
//
// IMPORTANT:
// invoice_no is used to connect:
//
// invoices → orders → order_items
//
// We do NOT use:
// invoice_items.product_id = order_items.id
// =====================================================

$stmt = $conn->prepare("

    SELECT

        oi.*,

        COALESCE(
            NULLIF(oi.category, ''),
            categories.name,
            ''
        ) AS resolved_category,

        COALESCE(
            (
                SELECT
                    ii.total / NULLIF(ii.quantity, 0)
                FROM invoice_items ii
                WHERE ii.invoice_id = ?
                AND ii.item_type = 'ORDER'
                AND (
                    ii.order_item_id = oi.id
                    OR (
                        ii.order_item_id IS NULL
                        AND ii.product_id = oi.factory_product_id
                        AND ii.quantity = oi.quantity
                        AND (
                            (
                                COALESCE(oi.width_ft, 0) > 0
                                AND ABS(COALESCE(ii.width_ft, 0) - oi.width_ft) < 0.01
                                AND ABS(COALESCE(ii.height_ft, 0) - oi.height_ft) < 0.01
                            )
                            OR (
                                COALESCE(oi.width_ft, 0) = 0
                                AND ABS(COALESCE(ii.width_mm, 0) - oi.width_mm) < 1
                                AND ABS(COALESCE(ii.height_mm, 0) - oi.height_mm) < 1
                            )
                        )
                    )
                )
                ORDER BY (ii.order_item_id = oi.id) DESC, ii.id ASC
                LIMIT 1
            ),
            0
        ) AS unit_price,

        o.id AS real_order_id,

        o.invoice_no AS order_invoice_no

    FROM orders o

    INNER JOIN order_items oi

        ON oi.order_id = o.id

    LEFT JOIN categories

        ON categories.id = oi.category_id

    WHERE o.invoice_no = ?

    ORDER BY oi.id ASC

");


if (!$stmt) {
    die(
        "Order item query failed: "
        . $conn->error
    );
}


$stmt->bind_param(

    "is",

    $invoice_id,
    $invoice['invoice_no']

);


$stmt->execute();


$items_query =
    $stmt->get_result();

?>

<?php include "../includes/header.php"; ?>

<?php include "../includes/sidebar.php"; ?>


<div class="container-fluid">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="row mt-3">


<div class="col-md-8">

<h2>

<i class="fa fa-undo"></i>

Return Order Items

</h2>

</div>


<div class="col-md-4 text-end">

<a

href="view.php?id=<?=$invoice_id;?>"

class="btn btn-secondary"

>

<i class="fa fa-arrow-left"></i>

Back to Invoice

</a>

</div>


</div>


<!-- =====================================================
     SUCCESS / ERROR MESSAGE
===================================================== -->

<?php if (!empty($_SESSION['success'])) { ?>

<div class="alert alert-success mt-3">

<i class="fa fa-check-circle"></i>

<?=htmlspecialchars($_SESSION['success']);?>

</div>

<?php

unset($_SESSION['success']);

}

?>


<?php if (!empty($_SESSION['error'])) { ?>

<div class="alert alert-danger mt-3">

<i class="fa fa-exclamation-circle"></i>

<?=htmlspecialchars($_SESSION['error']);?>

</div>

<?php

unset($_SESSION['error']);

}

?>


<!-- =====================================================
     INVOICE INFORMATION
===================================================== -->

<div class="card mt-3">

<div class="card-body">


<div class="row">


<div class="col-md-3">

<strong>

Invoice No

</strong>

<br>

<?=htmlspecialchars(
    $invoice['invoice_no']
);?>

</div>


<div class="col-md-3">

<strong>

Customer

</strong>

<br>

<?=htmlspecialchars(
    $invoice['customer_name'] ?? ''
);?>

</div>


<div class="col-md-3">

<strong>

Invoice Date

</strong>

<br>

<?=date(
    'd-m-Y',
    strtotime(
        $invoice['created_at']
    )
);?>

</div>


<div class="col-md-3">

<strong>

Grand Total

</strong>

<br>

<?=number_format(
    $invoice['grand_total'],
    2
);?>

</div>


</div>


</div>

</div>


<!-- =====================================================
     RETURN FORM
===================================================== -->

<form

action="save_return.php"

method="POST"

onsubmit="return confirmReturn();"

>


<input

type="hidden"

name="invoice_id"

value="<?=$invoice_id;?>"

>


<div class="card mt-3">


<div class="card-header">

<h5 class="mb-0">

<i class="fa fa-box"></i>

Factory Order Items

</h5>

</div>


<div class="card-body p-0">


<div class="table-responsive">


<table class="table table-bordered table-hover mb-0">


<thead class="table-dark">


<tr>


<th>

#

</th>


<th>

Product

</th>


<th>

Category

</th>


<th>

Unit Price

</th>


<th>

Cost Price

</th>


<th>

Size

</th>


<th>

Sold Qty

</th>


<th>

Returned Qty

</th>


<th>

Remaining

</th>


<th>

Status

</th>


<th>

Return Qty

</th>


</tr>


</thead>


<tbody>


<?php


$no = 1;

$found = false;


while (
    $item =
    $items_query->fetch_assoc()
) {


    $found = true;


    $original_qty =
        floatval(
            $item['quantity'] ?? 0
        );


    $returned_qty =
        floatval(
            $item['returned_quantity'] ?? 0
        );


    $remaining_qty =
        $original_qty
        - $returned_qty;


    if ($remaining_qty < 0) {
        $remaining_qty = 0;
    }


    $return_status =
        $item['return_status']
        ?? 'Not Returned';


?>


<tr>


<!-- NUMBER -->

<td>

<?=$no++;?>

</td>


<!-- PRODUCT -->

<td>

<strong>

<?=htmlspecialchars(
    $item['product_name'] ?? ''
);?>

</strong>


<?php if (!empty($item['description'])) { ?>

<br>

<small class="text-muted">

<?=htmlspecialchars(
    $item['description']
);?>

</small>

<?php } ?>


</td>


<!-- CATEGORY -->

<td>

<?=htmlspecialchars(
    $item['resolved_category'] ?? ''
);?>

</td>


<!-- UNIT PRICE -->

<td>

<?=number_format(
    floatval($item['unit_price'] ?? 0),
    2
);?>

<input
type="hidden"
name="unit_price[]"
value="<?=htmlspecialchars($item['unit_price'] ?? 0);?>"
>

</td>


<!-- COST PRICE -->

<td>

<input
type="number"
name="cost_price[]"
class="form-control"
min="0"
step="0.01"
value="0"
>

</td>


<!-- SIZE -->

<td>

<?php


$width_ft =
    floatval(
        $item['width_ft'] ?? 0
    );


$height_ft =
    floatval(
        $item['height_ft'] ?? 0
    );


$width_mm =
    floatval(
        $item['width_mm'] ?? 0
    );


$height_mm =
    floatval(
        $item['height_mm'] ?? 0
    );


if (
    $width_ft > 0 ||
    $height_ft > 0
) {


    echo number_format(
        $width_ft,
        2
    );


    echo " × ";


    echo number_format(
        $height_ft,
        2
    );


    echo " ft";


}
elseif (
    $width_mm > 0 ||
    $height_mm > 0
) {


    echo number_format(
        $width_mm,
        0
    );


    echo " × ";


    echo number_format(
        $height_mm,
        0
    );


    echo " mm";


}
else {


    echo "-";

}


?>


</td>


<!-- SOLD -->

<td>

<strong>

<?=number_format(
    $original_qty,
    2
);?>

</strong>

</td>


<!-- RETURNED -->

<td>

<?=number_format(
    $returned_qty,
    2
);?>

</td>


<!-- REMAINING -->

<td>

<strong

class="<?=$remaining_qty > 0
    ? 'text-danger'
    : 'text-success';
?>"

>

<?=number_format(
    $remaining_qty,
    2
);?>

</strong>

</td>


<!-- STATUS -->

<td>


<?php if (
    $remaining_qty <= 0
) { ?>


<span class="badge bg-success">

Fully Returned

</span>


<?php } elseif (
    $returned_qty > 0
) { ?>


<span class="badge bg-warning text-dark">

Partially Returned

</span>


<?php } else { ?>


<span class="badge bg-secondary">

Not Returned

</span>


<?php } ?>


</td>


<!-- RETURN QUANTITY -->

<td>


<?php if (
    $remaining_qty > 0
) { ?>


<input

type="number"

name="return_qty[]"

class="form-control return-qty"

min="0"

max="<?=$remaining_qty;?>"

step="0.01"

value="0"

data-max="<?=$remaining_qty;?>"

>


<small class="text-muted">

Max:

<?=number_format(
    $remaining_qty,
    2
);?>

</small>


<?php } else { ?>


<input

type="number"

class="form-control"

value="0"

disabled

>


<small class="text-success">

No quantity remaining

</small>


<?php } ?>


<input

type="hidden"

name="order_item_id[]"

value="<?=$item['id'];?>"

>


</td>


</tr>


<?php } ?>


<?php if (!$found) { ?>


<tr>


<td

colspan="11"

class="text-center text-muted py-5"

>


<i

class="fa fa-box-open fa-2x mb-2"

></i>


<br>


No factory order items found for this invoice.


</td>


</tr>


<?php } ?>


</tbody>


</table>


</div>

</div>


<?php if ($found) { ?>


<div class="card-footer">


<div class="row align-items-center">


<div class="col-md-6">


<span class="text-muted">

Enter the quantity you want to return.

Partial returns are allowed.

</span>


</div>


<div class="col-md-6 text-md-end mt-2 mt-md-0">


<strong>

Total Returning:

<span

id="totalReturn"

class="text-danger"

>

0

</span>

</strong>


&nbsp;


<button

type="submit"

class="btn btn-danger"

>

<i class="fa fa-undo"></i>

Return Selected Items

</button>


</div>


</div>

</div>


<?php } ?>


</div>


</form>


</div>


<script>


// =====================================================
// VALIDATE RETURN QUANTITIES
// =====================================================

document.querySelectorAll(
    '.return-qty'
).forEach(function(input) {


    input.addEventListener(
        'input',
        function() {


            let value =
                parseFloat(
                    this.value
                ) || 0;


            let max =
                parseFloat(
                    this.dataset.max
                ) || 0;


            if (value < 0) {

                value = 0;

            }


            if (value > max) {

                value = max;

            }


            this.value = value;


            calculateTotalReturn();

        }
    );

});


// =====================================================
// TOTAL RETURN
// =====================================================

function calculateTotalReturn() {


    let total = 0;


    document.querySelectorAll(
        '.return-qty'
    ).forEach(function(input) {


        total +=
            parseFloat(
                input.value
            ) || 0;


    });


    document.getElementById(
        'totalReturn'
    ).innerText =
        total.toFixed(2);

}


// =====================================================
// CONFIRM
// =====================================================

function confirmReturn() {


    let total = 0;


    let valid = true;


    document.querySelectorAll(
        '.return-qty'
    ).forEach(function(input) {


        let qty =
            parseFloat(
                input.value
            ) || 0;


        let max =
            parseFloat(
                input.dataset.max
            ) || 0;


        if (qty > max) {

            valid = false;

        }


        total += qty;


    });


    if (!valid) {


        alert(
            "Return quantity cannot be greater than the remaining quantity."
        );


        return false;

    }


    if (total <= 0) {


        alert(
            "Please enter at least one quantity to return."
        );


        return false;

    }


    return confirm(

        "Return "
        + total.toFixed(2)
        + " item(s) to inventory?"

    );

}


// Initial calculation

calculateTotalReturn();

</script>


<?php include "../includes/footer.php"; ?>
