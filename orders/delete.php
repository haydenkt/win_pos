<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$order_id = intval($_GET['id']);



mysqli_begin_transaction($conn);



try{



    // ==========================
    // DELETE PAYMENTS
    // ==========================


    mysqli_query($conn,"

        DELETE FROM payments

        WHERE order_id='$order_id'

    ");







    // ==========================
    // DELETE ORDER ITEMS
    // ==========================


    mysqli_query($conn,"

        DELETE FROM order_items

        WHERE order_id='$order_id'

    ");







    // ==========================
    // DELETE ORDER
    // ==========================


    mysqli_query($conn,"

        DELETE FROM orders

        WHERE id='$order_id'

    ");







    mysqli_commit($conn);



    header("Location:index.php?deleted=1");

    exit();




}

catch(Exception $e){



    mysqli_rollback($conn);



    die("Delete Error: ".$e->getMessage());

}



?>
