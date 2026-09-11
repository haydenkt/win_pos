<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user'])) {

    header("Location: ../index.php");
    exit();

}

include "../config/database.php";

$conn->begin_transaction();

try {

    // =================================
    // CUSTOMER
    // =================================

    $customer_id = intval($_POST['customer_id'] ?? 0);

    $new_name = trim($_POST['new_name'] ?? '');
    $new_phone = trim($_POST['new_phone'] ?? '');
    $new_address = trim($_POST['new_address'] ?? '');


    // =================================
    // CREATE NEW CUSTOMER
    // =================================

    if ($customer_id <= 0) {

        if ($new_name == '') {

            throw new Exception(
                "Customer name is required."
            );

        }


        $stmt = $conn->prepare("

            INSERT INTO customers

            (
                name,
                phone,
                address
            )

            VALUES
            (
                ?,
                ?,
                ?
            )

        ");


        if (!$stmt) {

            throw new Exception(
                "Customer prepare failed: "
                . $conn->error
            );

        }


        $stmt->bind_param(
            "sss",
            $new_name,
            $new_phone,
            $new_address
        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Customer creation failed: "
                . $stmt->error
            );

        }

        $customer_id =
            $conn->insert_id;

    }


    // =================================
    // VERIFY CUSTOMER
    // =================================

    $stmt = $conn->prepare("

        SELECT id

        FROM customers

        WHERE id = ?

        LIMIT 1

    ");


    if (!$stmt) {

        throw new Exception(
            "Customer verification failed: "
            . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $customer_id
    );


    $stmt->execute();


    $customer_result =
        $stmt->get_result();


    if ($customer_result->num_rows == 0) {

        throw new Exception(
            "Invalid customer selected."
        );

    }


    // =================================
    // BASIC DATA
    // =================================

    $discount = floatval(
        $_POST['discount'] ?? 0
    );


    $deposit = floatval(
        $_POST['deposit'] ?? 0
    );


    $invoice_status =
        $_POST['invoice_status']
        ?? 'Draft';


    $notes =
        trim(
            $_POST['notes'] ?? ''
        );


    // =================================
    // CALCULATE SUBTOTAL
    // =================================

    $subtotal = 0;


    $totals =
        $_POST['total'] ?? [];


    foreach ($totals as $total) {

        $subtotal +=
            floatval($total);

    }


    // =================================
    // GRAND TOTAL
    // =================================

    $grand_total =
        $subtotal
        - $discount;


    if ($grand_total < 0) {

        $grand_total = 0;

    }


    // =================================
    // DEPOSIT
    // =================================

    if ($deposit < 0) {

        $deposit = 0;

    }


    if ($deposit > $grand_total) {

        $deposit =
            $grand_total;

    }


    // =================================
    // BALANCE
    // =================================

    $balance =
        $grand_total
        - $deposit;


    // =================================
    // PAYMENT STATUS
    // =================================

    if ($deposit <= 0) {

        $payment_status =
            "Unpaid";

    }
    elseif ($deposit < $grand_total) {

        $payment_status =
            "Partial";

    }
    else {

        $payment_status =
            "Paid";

    }


    // =================================
    // CREATE INVOICE
    // =================================

    $stmt = $conn->prepare("

        INSERT INTO invoices

        (
            customer_id,
            invoice_no,
            subtotal,
            discount,
            grand_total,
            deposit,
            balance,
            payment_status,
            invoice_status,
            notes
        )

        VALUES

        (
            ?,
            'TEMP',
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )

    ");


    if (!$stmt) {

        throw new Exception(
            "Invoice prepare failed: "
            . $conn->error
        );

    }


    /*
     * IMPORTANT
     *
     * 10 variables:
     *
     * customer_id       = i
     * subtotal          = d
     * discount          = d
     * grand_total       = d
     * deposit           = d
     * balance           = d
     * payment_status    = s
     * invoice_status    = s
     * notes             = s
     *
     * Therefore:
     *
     * idddddsss
     */

    $stmt->bind_param(

        "idddddsss",

        $customer_id,
        $subtotal,
        $discount,
        $grand_total,
        $deposit,
        $balance,
        $payment_status,
        $invoice_status,
        $notes

    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Invoice insert failed: "
            . $stmt->error
        );

    }


    $invoice_id =
        $conn->insert_id;

 // =================================
// GENERATE INVOICE NUMBER
// USING INVOICE SEQUENCE
// =================================


// Get latest invoice sequence

$result = $conn->query("

    SELECT MAX(invoice_sequence) AS last_sequence

    FROM invoices

");


$row = $result->fetch_assoc();


$next_sequence =
    intval($row['last_sequence'] ?? 0) + 1;



// Create invoice number
// Example:
// INV-2026-00000001

$invoice_no =

    "INV-"
    . date("Y")
    . "-"
    . str_pad(

        $next_sequence,

        6,

        "0",

        STR_PAD_LEFT

    );



// Save invoice number + sequence


$stmt = $conn->prepare("

    UPDATE invoices

    SET

        invoice_no = ?,

        invoice_sequence = ?

    WHERE id = ?

");


if (!$stmt) {

    throw new Exception(
        "Invoice number update failed: "
        . $conn->error
    );

}



$stmt->bind_param(

    "sii",

    $invoice_no,

    $next_sequence,

    $invoice_id

);



if (!$stmt->execute()) {

    throw new Exception(
        "Invoice number update failed: "
        . $stmt->error
    );

}

// =================================
// SAVE INITIAL DEPOSIT AS PAYMENT
// =================================

if ($deposit > 0) {


    $payment_type = "Deposit";

    $payment_method = "Cash";

    $reference_no = "";

    $payment_note =
        "Initial deposit for invoice "
        . $invoice_no;


    $stmt = $conn->prepare("

        INSERT INTO payments

        (
            invoice_id,
            customer_id,
            order_id,
            amount,
            payment_method,
            reference_no,
            payment_type,
            payment_date,
            note,
            created_at
        )

        VALUES

        (
            ?,
            ?,
            NULL,
            ?,
            ?,
            ?,
            ?,
            NOW(),
            ?,
            NOW()
        )

    ");


    if (!$stmt) {

        throw new Exception(
            "Deposit payment prepare failed: "
            . $conn->error
        );

    }


    $stmt->bind_param(

        "iidssss",

        $invoice_id,
        $customer_id,
        $deposit,
        $payment_method,
        $reference_no,
        $payment_type,
        $payment_note

    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Deposit payment failed: "
            . $stmt->error
        );

    }

}

    // =================================
    // CREATE ORDER AUTOMATICALLY
    // =================================

    $order_id = 0;


    $item_types =
        $_POST['item_type'] ?? [];


    foreach ($item_types as $type) {

        if ($type == "ORDER") {


            $stmt = $conn->prepare("

                INSERT INTO orders

                (
                    invoice_no,
                    customer_id,
                    order_status,
                    notes
                )

                VALUES

                (
                    ?,
                    ?,
                    ?,
                    ?
                )

            ");


            if (!$stmt) {

                throw new Exception(
                    "Order prepare failed: "
                    . $conn->error
                );

            }


            $status =
                "Quotation";


            $stmt->bind_param(

                "siss",

                $invoice_no,
                $customer_id,
                $status,
                $notes

            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Order creation failed: "
                    . $stmt->error
                );

            }


            $order_id =
                $conn->insert_id;


            break;

        }

    }


    // =================================
    // MM TO FEET
    // =================================

    function convertMMtoFT($mm)
    {

        $ft =
            $mm / 304.8;


        if ($ft <= 0) {

            return 0;

        }


        $whole =
            floor($ft);


        $decimal =
            $ft - $whole;


        if ($decimal < 0.1) {

            return $whole;

        }
        elseif ($decimal <= 0.5) {

            return $whole + 0.5;

        }
        else {

            return $whole + 1;

        }

    }


    // =================================
    // SAVE ITEMS
    // =================================

    foreach (
        $item_types
        as $key => $type
    ) {


        if (empty($type)) {

            continue;

        }


        // =================================
        // PRODUCT ID
        // =================================

        $product_id =
            intval(
                $_POST['product_id'][$key]
                ?? 0
            );


        // =================================
        // PRODUCT NAME
        // =================================

        $product_name = "";


        // =================================
        // FACTORY PRODUCT
        // =================================

        if ($type == "ORDER") {


            $stmt =
                $conn->prepare("

                    SELECT product_name

                    FROM factory_products

                    WHERE id = ?

                    LIMIT 1

                ");


            if (!$stmt) {

                throw new Exception(
                    "Factory product query failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(
                "i",
                $product_id
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            if (
                $p =
                $result->fetch_assoc()
            ) {

                $product_name =
                    $p['product_name'];

            }

        }


        // =================================
        // SALE PRODUCT
        // =================================

        elseif ($type == "SALE") {


            $stmt =
                $conn->prepare("

                    SELECT name

                    FROM products

                    WHERE id = ?

                    LIMIT 1

                ");


            if (!$stmt) {

                throw new Exception(
                    "Sale product query failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(
                "i",
                $product_id
            );


            $stmt->execute();


            $result =
                $stmt->get_result();


            if (
                $p =
                $result->fetch_assoc()
            ) {

                $product_name =
                    $p['name'];

            }

        }


        // =================================
        // CUSTOM / SERVICE NAME
        // =================================

        if ($product_name == '') {

            $product_name =
                trim(
                    $_POST['product_name'][$key]
                    ?? ''
                );

        }


        // =================================
        // CALCULATION TYPE
        // =================================

        $calculation_type =
            $_POST['calculation_type'][$key]
            ?? "MANUAL";


        // =================================
        // MEASUREMENT UNIT
        // =================================

        $measurement_unit =
            $_POST['measurement_unit'][$key]
            ?? "MM";


        // =================================
        // WIDTH
        // =================================

        $width_input =
            floatval(
                $_POST['width'][$key]
                ?? 0
            );


        // =================================
        // HEIGHT
        // =================================

        $height_input =
            floatval(
                $_POST['height'][$key]
                ?? 0
            );


        // =================================
        // CONVERT MEASUREMENT
        // =================================

        if ($measurement_unit == "MM") {


            $width_mm =
                $width_input;


            $height_mm =
                $height_input;


            $width_ft =
                convertMMtoFT(
                    $width_input
                );


            $height_ft =
                convertMMtoFT(
                    $height_input
                );

        }
        else {


            // FT entered directly

            $width_ft =
                $width_input;


            $height_ft =
                $height_input;


            // FT -> MM

            $width_mm =
                $width_input * 304.8;


            $height_mm =
                $height_input * 304.8;

        }


        // =================================
        // QUANTITY
        // =================================

        $qty =
            intval(
                $_POST['quantity'][$key]
                ?? 1
            );


        if ($qty <= 0) {

            $qty = 1;

        }


        // =================================
        // PRICE
        // =================================

        $price =
            floatval(
                $_POST['price'][$key]
                ?? 0
            );


        // =================================
        // CALCULATE ITEM
        // =================================

        if ($calculation_type == "SQFT") {


            $sqft =

                $width_ft
                *
                $height_ft
                *
                $qty;


            $total =

                $sqft
                *
                $price;

        }
        else {


            $sqft = 0;


            $width_mm = 0;

            $height_mm = 0;

            $width_ft = 0;

            $height_ft = 0;


            $total =

                $qty
                *
                $price;

        }


        // =================================
        // SELLING FEET
        // =================================

        $width =
            $width_ft;


        $height =
            $height_ft;


        // =================================
        // SAVE INVOICE ITEM
        // =================================

        $stmt =
            $conn->prepare("

                INSERT INTO invoice_items

                (
                    invoice_id,
                    item_type,
                    product_id,
                    product_name,
                    quantity,
                    width_mm,
                    height_mm,
                    width_ft,
                    height_ft,
                    width,
                    height,
                    sqft,
                    price,
                    total
                )

                VALUES

                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )

            ");


        if (!$stmt) {

            throw new Exception(
                "Invoice item prepare failed: "
                . $conn->error
            );

        }


        /*
         * 14 variables:
         *
         * invoice_id       i
         * type             s
         * product_id       i
         * product_name     s
         * quantity         i
         * width_mm         d
         * height_mm        d
         * width_ft         d
         * height_ft        d
         * width            d
         * height           d
         * sqft             d
         * price            d
         * total            d
         */

        $stmt->bind_param(

            "isisiidddddddd",

            $invoice_id,
            $type,
            $product_id,
            $product_name,
            $qty,
            $width_mm,
            $height_mm,
            $width_ft,
            $height_ft,
            $width,
            $height,
            $sqft,
            $price,
            $total

        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Invoice item failed: "
                . $stmt->error
            );

        }


        $invoice_item_id =
            $conn->insert_id;


        // =================================
        // CREATE ORDER ITEM
        // =================================

        if ($type == "ORDER") {


            if ($order_id == 0) {

                continue;

            }


            // =================================
            // GET FACTORY PRODUCT
            // =================================

            $stmt =
                $conn->prepare("

                    SELECT *

                    FROM factory_products

                    WHERE id = ?

                    LIMIT 1

                ");


            if (!$stmt) {

                throw new Exception(
                    "Factory product lookup failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(
                "i",
                $product_id
            );


            $stmt->execute();


            $factory =
                $stmt->get_result();


            $fp =
                $factory->fetch_assoc();


            if (!$fp) {

                throw new Exception(
                    "Factory product not found."
                );

            }


            $category_id =
                $fp['category_id']
                ?? null;


            $material_type_id =
                $fp['material_type_id']
                ?? null;


            $category =
                "";


            $description =
                "";


            // =================================
            // ORDER ITEM
            // =================================

            $stmt =
                $conn->prepare("

                    INSERT INTO order_items

                    (
                        order_id,
                        category_id,
                        material_type_id,
                        factory_product_id,
                        category,
                        product_name,
                        calculation_type,
                        width,
                        height,
                        width_mm,
                        height_mm,
                        width_ft,
                        height_ft,
                        quantity,
                        sqft,
                        description
                    )

                    VALUES

                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )

                ");


            if (!$stmt) {

                throw new Exception(
                    "Order item prepare failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(

                "iiiisssddddddids",

                $order_id,
                $category_id,
                $material_type_id,
                $product_id,
                $category,
                $product_name,
                $calculation_type,
                $width,
                $height,
                $width_mm,
                $height_mm,
                $width_ft,
                $height_ft,
                $qty,
                $sqft,
                $description

            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Order item failed: "
                    . $stmt->error
                );

            }


            $order_item_id =
                $conn->insert_id;


            // Keep an exact link for reliable return pricing.
            $stmt = $conn->prepare("

                UPDATE invoice_items

                SET order_item_id = ?

                WHERE id = ?

            ");


            if (!$stmt) {

                throw new Exception(
                    "Invoice item link prepare failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(
                "ii",
                $order_item_id,
                $invoice_item_id
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Invoice item link failed: "
                    . $stmt->error
                );

            }


        }


        // =================================
        // SALE STOCK UPDATE
        // =================================

        if ($type == "SALE") {


            // =================================
            // CHECK STOCK
            // =================================

            $stmt =
                $conn->prepare("

                    SELECT stock_qty

                    FROM products

                    WHERE id = ?

                    LIMIT 1

                ");


            if (!$stmt) {

                throw new Exception(
                    "Stock check failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(
                "i",
                $product_id
            );


            $stmt->execute();


            $stock_result =
                $stmt->get_result();


            $stock =
                $stock_result->fetch_assoc();


            if (!$stock) {

                throw new Exception(
                    "Sale product not found."
                );

            }


            if (
                floatval($stock['stock_qty'])
                < $qty
            ) {

                throw new Exception(
                    "Insufficient stock for "
                    . $product_name
                );

            }


            // =================================
            // UPDATE STOCK
            // =================================

            $stmt =
                $conn->prepare("

                    UPDATE products

                    SET stock_qty =
                        stock_qty - ?

                    WHERE id = ?

                ");


            if (!$stmt) {

                throw new Exception(
                    "Stock update prepare failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(

                "ii",

                $qty,
                $product_id

            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Stock update failed: "
                    . $stmt->error
                );

            }


            // =================================
            // STOCK HISTORY
            // =================================

            $stock_type =
                "OUT";


            $stock_note =
                "Invoice " . $invoice_no;


            $stmt =
                $conn->prepare("

                    INSERT INTO stock_history

                    (
                        product_id,
                        type,
                        quantity,
                        note
                    )

                    VALUES

                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )

                ");


            if (!$stmt) {

                throw new Exception(
                    "Stock history prepare failed: "
                    . $conn->error
                );

            }


            $stmt->bind_param(

                "isis",

                $product_id,
                $stock_type,
                $qty,
                $stock_note

            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Stock history failed: "
                    . $stmt->error
                );

            }

        }

    }


    // =================================
    // UPDATE INVOICE TOTAL
    // =================================

    $stmt =
        $conn->prepare("

            UPDATE invoices

            SET
                grand_total = ?,
                deposit = ?,
                balance = ?,
                payment_status = ?

            WHERE id = ?

        ");


    if (!$stmt) {

        throw new Exception(
            "Invoice update prepare failed: "
            . $conn->error
        );

    }


    $stmt->bind_param(

        "dddsi",

        $grand_total,
        $deposit,
        $balance,
        $payment_status,
        $invoice_id

    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Invoice total update failed: "
            . $stmt->error
        );

    }


    // =================================
    // COMMIT
    // =================================

    $conn->commit();


    echo "

    <script>

        alert('Invoice Saved Successfully');

        window.location='index.php';

    </script>

    ";


}
catch (Exception $e) {

    $conn->rollback();


    echo "

    <div style='
        font-family: Arial;
        padding: 30px;
    '>

        <h3>
            Save Error
        </h3>

        <p>
            "
            . htmlspecialchars(
                $e->getMessage()
            )
            . "
        </p>

        <a href='add.php'>
            Back to Invoice
        </a>

    </div>

    ";

}

?>
