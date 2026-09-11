<?php

session_start();

if(!isset($_SESSION['user'])){
    header("Location:../index.php");
    exit();
}

include_once "../config/database.php";


if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$id = $_GET['id'];


// Check customer orders first

$check = $conn->prepare("
    SELECT id 
    FROM orders 
    WHERE customer_id=?
");

$check->bind_param("i",$id);

$check->execute();

$result = $check->get_result();


if($result->num_rows > 0){

    echo "
    <script>
    alert('Warning: This customer has existing orders and cannot be deleted.');
    window.location='index.php';
    </script>
    ";

    exit();

}



// Delete customer

$stmt = $conn->prepare("
    DELETE FROM customers
    WHERE id=?
");


$stmt->bind_param(
    "i",
    $id
);



if($stmt->execute()){

    echo "
    <script>
    alert('Customer Deleted Successfully');
    window.location='index.php';
    </script>
    ";

}
else{

    echo "
    <script>
    alert('Delete Failed');
    window.location='index.php';
    </script>
    ";

}

?>