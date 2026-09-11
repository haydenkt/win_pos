<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();


// ========================================
// LOGIN CHECK
// ========================================

if (!isset($_SESSION['user'])) {

    header("Location: ../index.php");
    exit();

}


include "../config/database.php";


// ========================================
// ONLY POST REQUEST
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: add.php");
    exit();

}


// ========================================
// GET FORM DATA
// ========================================

$customer_type = $_POST['customer_type'] ?? 'old';

$customer_id = $_POST['customer_id'] ?? '';

$new_name = trim($_POST['new_name'] ?? '');

$new_phone = trim($_POST['new_phone'] ?? '');

$new_address = trim($_POST['new_address'] ?? '');

$survey_date = $_POST['survey_date'] ?? date('Y-m-d');

$status = $_POST['status'] ?? 'Pending';

$notes = trim($_POST['notes'] ?? '');



// ========================================
// CUSTOMER
// ========================================

if ($customer_type === 'new') {


    // ------------------------------------
    // CHECK NEW CUSTOMER NAME
    // ------------------------------------

    if ($new_name === '') {

        die("Customer name is required.");

    }


    // ------------------------------------
    // CREATE NEW CUSTOMER
    // ------------------------------------

    $sql = "

        INSERT INTO customers
        (
            name,
            phone,
            address
        )

        VALUES (?, ?, ?)

    ";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die("Customer prepare failed: " . $conn->error);

    }


    $stmt->bind_param(
        "sss",
        $new_name,
        $new_phone,
        $new_address
    );


    if (!$stmt->execute()) {

        die("Customer insert failed: " . $stmt->error);

    }


    // Get new customer ID

    $customer_id = $conn->insert_id;


    $stmt->close();

}



// ========================================
// EXISTING CUSTOMER
// ========================================

else {


    if (empty($customer_id)) {

        die("Please select a customer.");

    }


    $customer_id = (int)$customer_id;


}



// ========================================
// SAVE SITE SURVEY
// ========================================

$sql = "

    INSERT INTO site_survey
    (
        customer_id,
        survey_date,
        address,
        notes,
        status
    )

    VALUES (?, ?, ?, ?, ?)

";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("Survey prepare failed: " . $conn->error);

}



// ----------------------------------------
// GET CUSTOMER ADDRESS FOR EXISTING CUSTOMER
// ----------------------------------------

if ($customer_type === 'old') {


    $address = '';


    $address_sql = "

        SELECT address

        FROM customers

        WHERE id = ?

        LIMIT 1

    ";


    $address_stmt = $conn->prepare($address_sql);


    if ($address_stmt) {

        $address_stmt->bind_param(
            "i",
            $customer_id
        );

        $address_stmt->execute();

        $address_result = $address_stmt->get_result();

        $customer = $address_result->fetch_assoc();


        if ($customer) {

            $address = $customer['address'] ?? '';

        }


        $address_stmt->close();

    }


} else {


    // New customer's address

    $address = $new_address;

}



// ========================================
// INSERT SURVEY
// ========================================

$stmt->bind_param(
    "issss",
    $customer_id,
    $survey_date,
    $address,
    $notes,
    $status
);


if (!$stmt->execute()) {

    die("Survey insert failed: " . $stmt->error);

}


$stmt->close();


// ========================================
// SUCCESS
// ========================================

header("Location: index.php?success=Survey Saved");

exit();

?>
