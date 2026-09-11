<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

include_once "../config/database.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit();

}


$name = trim($_POST['name'] ?? '');

$price = $_POST['price_per_sqft'] ?? 0;

$status = $_POST['status'] ?? 'Active';



if ($name == '') {

    $_SESSION['error'] = "Glass name is required.";

    header("Location: add.php");
    exit();

}



$name = mysqli_real_escape_string(
    $conn,
    $name
);


$price = floatval($price);


$status = mysqli_real_escape_string(
    $conn,
    $status
);



$sql = "
INSERT INTO glass_types
(
    name,
    price_per_sqft,
    status
)
VALUES
(
    '$name',
    '$price',
    '$status'
)
";



if (mysqli_query($conn, $sql)) {


    $_SESSION['success'] =
        "Glass type added successfully.";


} else {


    $_SESSION['error'] =
        "Error: " . mysqli_error($conn);


}



header(
    "Location: index.php"
);

exit();

?>