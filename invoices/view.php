<?php

session_start();

if (!isset($_SESSION['user'])) {

    header("Location:../index.php");
    exit();

}

include_once "../config/database.php";


if (!isset($_GET['id'])) {

    die("Invoice ID missing");

}

$id = intval($_GET['id']);

if ($id <= 0) {

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
    $id
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
// GET INVOICE ITEMS
// =====================================================

$stmt = $conn->prepare("

    SELECT *

    FROM invoice_items

    WHERE invoice_id = ?

    ORDER BY id ASC

");

if (!$stmt) {

    die(
        "Invoice items query failed: "
        . $conn->error
    );

}

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$items =
    $stmt->get_result();


// =====================================================
// GET ORDER ITEMS FOR THIS INVOICE
//
// invoices
//    ↓
// orders.invoice_no
//    ↓
// order_items
// =====================================================

$order_items = [];

$stmt = $conn->prepare("

    SELECT

        oi.id AS order_item_id,

        oi.order_id,

        oi.product_name,

        oi.category,

        oi.width_mm,
        oi.height_mm,

        oi.width_ft,
        oi.height_ft,

        oi.quantity,

        oi.returned_quantity,

        oi.return_status

    FROM orders o

    INNER JOIN order_items oi

        ON oi.order_id = o.id

    WHERE o.invoice_no = ?

    ORDER BY oi.id ASC

");

if (!$stmt) {

    die(
        "Order items query failed: "
        . $conn->error
    );

}

$stmt->bind_param(

    "s",

    $invoice['invoice_no']

);

$stmt->execute();

$order_result =
    $stmt->get_result();


while ($row = $order_result->fetch_assoc()) {

    $order_items[] = $row;

}


?>

<?php include "../includes/header.php"; ?>

<?php include "../includes/sidebar.php"; ?>


<div class="container-fluid">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="row mt-3">

    <div class="col-md-8">

        <h2>

            <i class="fa fa-file-invoice"></i>

            Invoice Details

        </h2>

    </div>


    <div class="col-md-4 text-end">

        <a

            href="print_invoice.php?id=<?=$id;?>"

            class="btn btn-success"

            target="_blank"

        >

            <i class="fa fa-print"></i>

            Print

        </a>

    </div>

</div>


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

                    Date

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

                    Invoice Status

                </strong>

                <br>

                <?=$invoice['invoice_status'];?>

            </div>


            <div class="col-md-3">

                <strong>

                    Payment Status

                </strong>

                <br>


                <?php

                if (
                    $invoice['payment_status']
                    == 'Paid'
                ) {

                    echo '<span class="badge bg-success">
                            Paid
                          </span>';

                }
                elseif (
                    $invoice['payment_status']
                    == 'Partial'
                ) {

                    echo '<span class="badge bg-warning text-dark">
                            Partial
                          </span>';

                }
                else {

                    echo '<span class="badge bg-danger">
                            Unpaid
                          </span>';

                }

                ?>

            </div>


        </div>

    </div>

</div>


<!-- =====================================================
     CUSTOMER
===================================================== -->

<div class="card mt-3">

    <div class="card-body">

        <h5>

            Customer Information

        </h5>

        <hr>


        <div class="row">

            <div class="col-md-4">

                <b>Name:</b>

                <br>

                <?=htmlspecialchars(
                    $invoice['customer_name'] ?? ''
                );?>

            </div>


            <div class="col-md-4">

                <b>Phone:</b>

                <br>

                <?=htmlspecialchars(
                    $invoice['customer_phone'] ?? ''
                );?>

            </div>


            <div class="col-md-4">

                <b>Address:</b>

                <br>

                <?=htmlspecialchars(
                    $invoice['customer_address'] ?? ''
                );?>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     INVOICE ITEMS
===================================================== -->

<div class="card mt-3">

    <div class="card-body">

        <h5>

            Invoice Items

        </h5>


        <div class="table-responsive">

        <table class="table table-bordered table-hover">

            <thead class="table-dark">

                <tr>

                    <th>Type</th>

                    <th>Product</th>

                    <th>Size</th>

                    <th>Sold Qty</th>

                    <th>Returned</th>

                    <th>Remaining</th>

                    <th>Price</th>

                    <th>Total</th>

                </tr>

            </thead>


            <tbody>


<?php

$has_order_item = false;


while (
    $item =
    $items->fetch_assoc()
) {


    $type =
        $item['item_type'];


    // =================================================
    // DEFAULT RETURN VALUES
    // =================================================

    $sold_qty =
        floatval(
            $item['quantity'] ?? 0
        );

    $returned_qty = 0;

    $remaining_qty =
        $sold_qty;


    $return_status =
        'Not Returned';


    $order_item = null;


    // =================================================
    // ORDER ITEM
    // =================================================

    if ($type == 'ORDER') {


        $has_order_item = true;


        /*
         * invoice_items.product_id is NOT
         * the order_items.id.
         *
         * Therefore find matching order item
         * using product name + quantity/size.
         */

        foreach (
            $order_items
            as $oi
        ) {


            if (
                $oi['product_name']
                == $item['product_name']
                &&
                floatval(
                    $oi['quantity']
                )
                == $sold_qty
            ) {

                $order_item =
                    $oi;

                break;

            }

        }


        if ($order_item) {

            $sold_qty =
                floatval(
                    $order_item['quantity']
                );

            $returned_qty =
                floatval(
                    $order_item['returned_quantity']
                    ?? 0
                );

            $remaining_qty =
                $sold_qty
                - $returned_qty;


            if (
                $remaining_qty < 0
            ) {

                $remaining_qty = 0;

            }


            $return_status =
                $order_item['return_status']
                ?? 'Not Returned';

        }

    }

?>


<tr>


<!-- TYPE -->

<td>

<?php if ($type == 'ORDER') { ?>

<span class="badge bg-primary">

ORDER

</span>

<?php }
elseif ($type == 'SALE') { ?>

<span class="badge bg-success">

SALE

</span>

<?php }
else { ?>

<span class="badge bg-warning text-dark">

SERVICE

</span>

<?php } ?>

</td>


<!-- PRODUCT -->

<td>

<strong>

<?=htmlspecialchars(
    $item['product_name']
);?>

</strong>

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

<?=number_format(
    $sold_qty,
    2
);?>

</td>


<!-- RETURNED -->

<td>

<?php if ($type == 'ORDER') { ?>

<?=number_format(
    $returned_qty,
    2
);?>

<?php } else { ?>

-

<?php } ?>

</td>


<!-- REMAINING -->

<td>

<?php if ($type == 'ORDER') { ?>


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


<?php } else { ?>

-

<?php } ?>

</td>


<!-- PRICE -->

<td>

<?=number_format(
    $item['price'],
    2
);?>

</td>


<!-- TOTAL -->

<td>

<?=number_format(
    $item['total'],
    2
);?>

</td>


</tr>


<?php } ?>


<?php if (!$has_order_item) { ?>

<?php } ?>


            </tbody>

        </table>

        </div>

    </div>

</div>


<!-- =====================================================
     RETURN ORDER
===================================================== -->

<?php if ($has_order_item) { ?>


<div class="card mt-3">

    <div class="card-body">


        <div class="row align-items-center">


            <div class="col-md-8">

                <h5 class="mb-1">

                    <i class="fa fa-undo"></i>

                    Factory Order Returns

                </h5>

                <small class="text-muted">

                    Return factory order items to inventory.
                    Partial returns are supported.

                </small>

            </div>


            <div class="col-md-4 text-end">


                <?php

                $can_return = false;


                foreach (
                    $order_items
                    as $oi
                ) {

                    $sold =
                        floatval(
                            $oi['quantity']
                        );

                    $returned =
                        floatval(
                            $oi['returned_quantity']
                            ?? 0
                        );


                    if (
                        ($sold - $returned)
                        > 0
                    ) {

                        $can_return = true;

                        break;

                    }

                }

                ?>


                <?php if ($can_return) { ?>

                <a

                    href="return.php?id=<?=$id;?>"

                    class="btn btn-danger"

                >

                    <i class="fa fa-undo"></i>

                    Return Order Items

                </a>

                <?php } else { ?>

                <span class="badge bg-success p-2">

                    All Items Fully Returned

                </span>

                <?php } ?>


            </div>

        </div>


    </div>

</div>


<?php } ?>


<!-- =====================================================
     TOTALS
===================================================== -->

<div class="card mt-3">

    <div class="card-body">


        <table class="table mb-0">


            <tr>

                <td>

                    Subtotal

                </td>

                <td class="text-end">

                    <?=number_format(
                        $invoice['subtotal'],
                        2
                    );?>

                </td>

            </tr>


            <tr>

                <td>

                    Discount

                </td>

                <td class="text-end">

                    <?=number_format(
                        $invoice['discount'],
                        2
                    );?>

                </td>

            </tr>


            <tr class="table-primary">

                <td>

                    <b>

                        Grand Total

                    </b>

                </td>

                <td class="text-end">

                    <b>

                        <?=number_format(
                            $invoice['grand_total'],
                            2
                        );?>

                    </b>

                </td>

            </tr>


            <tr>

                <td>

                    Deposit

                </td>

                <td class="text-end">

                    <?=number_format(
                        $invoice['deposit'],
                        2
                    );?>

                </td>

            </tr>


            <tr>

                <td>

                    Balance

                </td>

                <td class="text-end">

                    <?=number_format(
                        $invoice['balance'],
                        2
                    );?>

                </td>

            </tr>


        </table>

    </div>

</div>


<!-- =====================================================
     NOTES
===================================================== -->

<?php if (!empty($invoice['notes'])) { ?>

<div class="card mt-3">

    <div class="card-body">

        <strong>

            Notes

        </strong>

        <hr>

        <?=nl2br(
            htmlspecialchars(
                $invoice['notes']
            )
        );?>

    </div>

</div>

<?php } ?>


<a

href="index.php"

class="btn btn-secondary mt-3"

>

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>


<?php include "../includes/footer.php"; ?>