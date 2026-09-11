<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user'])) {
    header("Location:../index.php");
    exit();
}

include_once "../config/database.php";


// =====================================================
// ONLY POST REQUEST
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location:index.php");
    exit();
}


$invoice_id = intval($_POST['invoice_id'] ?? 0);

$order_item_ids = $_POST['order_item_id'] ?? [];

$return_qtys = $_POST['return_qty'] ?? [];

$cost_prices = $_POST['cost_price'] ?? [];


if ($invoice_id <= 0) {
    die("Invalid invoice ID");
}


if (empty($order_item_ids)) {
    die("No order item received.");
}


$conn->begin_transaction();


try {

    // =====================================================
    // GET INVOICE
    // =====================================================

    $stmt = $conn->prepare("
        SELECT
            id,
            invoice_no,
            customer_id
        FROM invoices
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception(
            "Invoice query failed: " . $conn->error
        );
    }

    $stmt->bind_param(
        "i",
        $invoice_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $invoice = $result->fetch_assoc();

    if (!$invoice) {
        throw new Exception("Invoice not found.");
    }


    $returned_count = 0;


    // =====================================================
    // PROCESS ITEMS
    // =====================================================

    foreach ($order_item_ids as $key => $order_item_id) {

        $order_item_id = intval($order_item_id);

        $return_qty = floatval(
            $return_qtys[$key] ?? 0
        );


        if ($order_item_id <= 0 || $return_qty <= 0) {
            continue;
        }


        // =================================================
        // GET ORDER ITEM
        // =================================================

        $stmt = $conn->prepare("

            SELECT
                oi.id,
                oi.order_id,
                oi.product_name,
                oi.category_id,
                oi.factory_product_id,
                COALESCE(
                    NULLIF(oi.category, ''),
                    categories.name,
                    ''
                ) AS category,
                oi.width_mm,
                oi.height_mm,
                oi.width_ft,
                oi.height_ft,
                oi.quantity,
                oi.returned_quantity,
                oi.return_status,
                oi.description,

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

                o.invoice_no

            FROM order_items oi

            INNER JOIN orders o
                ON o.id = oi.order_id

            LEFT JOIN categories
                ON categories.id = oi.category_id

            WHERE oi.id = ?

            AND o.invoice_no = ?

            LIMIT 1

            FOR UPDATE

        ");


        if (!$stmt) {
            throw new Exception(
                "Order item query failed: "
                . $conn->error
            );
        }


        $stmt->bind_param(
            "iis",
            $invoice_id,
            $order_item_id,
            $invoice['invoice_no']
        );


        $stmt->execute();


        $result = $stmt->get_result();

        $item = $result->fetch_assoc();


        if (!$item) {

            throw new Exception(
                "Order item #"
                . $order_item_id
                . " does not belong to invoice "
                . $invoice['invoice_no']
            );

        }


        // =================================================
        // QUANTITY
        // =================================================

        $original_qty = floatval(
            $item['quantity']
        );


        $already_returned = floatval(
            $item['returned_quantity'] ?? 0
        );


        $remaining_qty =
            $original_qty
            - $already_returned;


        if ($remaining_qty <= 0) {

            throw new Exception(
                "No quantity remaining for "
                . $item['product_name']
            );

        }


        if ($return_qty > $remaining_qty) {

            throw new Exception(
                "Return quantity cannot be greater than remaining quantity for "
                . $item['product_name']
            );

        }


        // =================================================
        // INVENTORY NAME
        // =================================================

        $product_name =
            trim($item['product_name']);


        $width_ft =
            floatval($item['width_ft']);


        $height_ft =
            floatval($item['height_ft']);


        $width_mm =
            floatval($item['width_mm']);


        $height_mm =
            floatval($item['height_mm']);


        $inventory_name =
            $product_name;


        if (
            $width_ft > 0 ||
            $height_ft > 0
        ) {

            $inventory_name .=
                " - "
                . rtrim(
                    rtrim(
                        number_format(
                            $width_ft,
                            2,
                            '.',
                            ''
                        ),
                        '0'
                    ),
                    '.'
                )
                . "x"
                . rtrim(
                    rtrim(
                        number_format(
                            $height_ft,
                            2,
                            '.',
                            ''
                        ),
                        '0'
                    ),
                    '.'
                )
                . " ft";

        }
        elseif (
            $width_mm > 0 ||
            $height_mm > 0
        ) {

            $inventory_name .=
                " - "
                . rtrim(
                    rtrim(
                        number_format(
                            $width_mm,
                            2,
                            '.',
                            ''
                        ),
                        '0'
                    ),
                    '.'
                )
                . "x"
                . rtrim(
                    rtrim(
                        number_format(
                            $height_mm,
                            2,
                            '.',
                            ''
                        ),
                        '0'
                    ),
                    '.'
                )
                . " mm";

        }


        $category =
            $item['category'] ?? '';


        $description =
            $item['description'] ?? '';


        // =================================================
        // ADD TO PRODUCTS STOCK
        // =================================================

        $purchase_price = max(
            0,
            floatval($cost_prices[$key] ?? 0)
        );

        $selling_price = max(
            0,
            floatval($item['unit_price'] ?? 0)
        );

        $unit = "pcs";


        $stmt = $conn->prepare("

            SELECT
                id,
                stock_qty

            FROM products

            WHERE name = ?

            LIMIT 1

            FOR UPDATE

        ");


        if (!$stmt) {
            throw new Exception(
                "Product lookup failed: "
                . $conn->error
            );
        }


        $stmt->bind_param(
            "s",
            $inventory_name
        );


        $stmt->execute();


        $product_result =
            $stmt->get_result();


        $product =
            $product_result->fetch_assoc();


        if ($product) {

            $product_id =
                intval($product['id']);


            $stmt = $conn->prepare("

                UPDATE products

                SET stock_qty = stock_qty + ?,
                    category = CASE
                        WHEN category IS NULL OR category = ''
                        THEN ?
                        ELSE category
                    END,
                    unit = CASE
                        WHEN unit IS NULL OR unit = ''
                        THEN ?
                        ELSE unit
                    END,
                    purchase_price = CASE
                        WHEN purchase_price <= 0 AND ? > 0
                        THEN ?
                        ELSE purchase_price
                    END,
                    selling_price = CASE
                        WHEN selling_price <= 0 AND ? > 0
                        THEN ?
                        ELSE selling_price
                    END

                WHERE id = ?

            ");


            if (!$stmt) {
                throw new Exception(
                    "Stock update prepare failed: "
                    . $conn->error
                );
            }


            $stmt->bind_param(
                "dssddddi",
                $return_qty,
                $category,
                $unit,
                $purchase_price,
                $purchase_price,
                $selling_price,
                $selling_price,
                $product_id
            );


            if (!$stmt->execute()) {
                throw new Exception(
                    "Stock update failed: "
                    . $stmt->error
                );
            }

        }
        else {

            $stmt = $conn->prepare("

                INSERT INTO products
                (
                    name,
                    category,
                    unit,
                    purchase_price,
                    selling_price,
                    stock_qty
                )

                VALUES
                (
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
                    "Product insert failed: "
                    . $conn->error
                );
            }


            $stmt->bind_param(
                "sssddd",
                $inventory_name,
                $category,
                $unit,
                $purchase_price,
                $selling_price,
                $return_qty
            );


            if (!$stmt->execute()) {
                throw new Exception(
                    "Product insert failed: "
                    . $stmt->error
                );
            }


            $product_id =
                $conn->insert_id;

        }


        // =================================================
        // STOCK HISTORY
        // =================================================

        $history_type =
            "ORDER RETURN";


        $history_note =
            "Returned from Invoice "
            . $invoice['invoice_no']
            . " / Order Item #"
            . $order_item_id;


        $stmt = $conn->prepare("

            INSERT INTO stock_history
            (
                material_id,
                product_id,
                type,
                quantity,
                note
            )

            VALUES
            (
                NULL,
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
            "isds",
            $product_id,
            $history_type,
            $return_qty,
            $history_note
        );


        if (!$stmt->execute()) {
            throw new Exception(
                "Stock history failed: "
                . $stmt->error
            );
        }


        // =================================================
        // SAVE RETURNED INVENTORY
        // =================================================

        $quantity_available =
            $return_qty;


        $status =
            "Available";


        $stmt = $conn->prepare("

            INSERT INTO returned_inventory
            (
                order_item_id,
                invoice_id,
                product_name,
                category,
                width_mm,
                height_mm,
                width_ft,
                height_ft,
                quantity,
                quantity_available,
                selling_price,
                description,
                status
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
                ?
            )

        ");


        if (!$stmt) {
            throw new Exception(
                "Returned inventory prepare failed: "
                . $conn->error
            );
        }


        $stmt->bind_param(
    "iissdddddddss",
    $order_item_id,
    $invoice_id,
    $inventory_name,
    $category,
    $width_mm,
    $height_mm,
    $width_ft,
    $height_ft,
    $return_qty,
    $quantity_available,
    $selling_price,
    $description,
    $status
);


        if (!$stmt->execute()) {
            throw new Exception(
                "Returned inventory save failed: "
                . $stmt->error
            );
        }


        // =================================================
        // UPDATE ORDER ITEM
        // =================================================

        $new_returned_qty =
            $already_returned
            + $return_qty;


        if ($new_returned_qty >= $original_qty) {

            $new_returned_qty =
                $original_qty;

            $new_return_status =
                "Returned";

        }
        else {

            $new_return_status =
                "Not Returned";

        }


        $stmt = $conn->prepare("

            UPDATE order_items

            SET
                returned_quantity = ?,
                return_status = ?

            WHERE id = ?

        ");


        if (!$stmt) {
            throw new Exception(
                "Order item update prepare failed: "
                . $conn->error
            );
        }


        $stmt->bind_param(
            "dsi",
            $new_returned_qty,
            $new_return_status,
            $order_item_id
        );


        if (!$stmt->execute()) {
            throw new Exception(
                "Order item update failed: "
                . $stmt->error
            );
        }


        $returned_count++;

    }


    // =====================================================
    // NOTHING RETURNED
    // =====================================================

    if ($returned_count <= 0) {

        throw new Exception(
            "No items were selected for return."
        );

    }


    // =====================================================
    // COMMIT
    // =====================================================

    $conn->commit();


    $_SESSION['success'] =
        $returned_count
        . " item(s) returned successfully.";


    header(
        "Location:view.php?id="
        . $invoice_id
    );

    exit();


}
catch (Exception $e) {

    $conn->rollback();

    $_SESSION['error'] =
        $e->getMessage();


    header(
        "Location:return.php?id="
        . $invoice_id
    );

    exit();

}

?>
