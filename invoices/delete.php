<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



// ONLY ADMIN CAN DELETE

if($_SESSION['role'] != "admin"){

    die("Permission denied");

}



if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$invoice_id = intval($_GET['id']);



mysqli_begin_transaction($conn);



try{


    // =========================
    // GET INVOICE NUMBER
    // =========================

    $invoice_query=mysqli_query($conn,"

        SELECT invoice_no

        FROM invoices

        WHERE id='$invoice_id'

    ");


    $invoice=mysqli_fetch_assoc($invoice_query);



    if(!$invoice){

        throw new Exception("Invoice not found");

    }



    $invoice_no=$invoice['invoice_no'];




    // =========================
    // FIND ORDER
    // =========================


    $order_query=mysqli_query($conn,"

        SELECT id

        FROM orders

        WHERE invoice_no='$invoice_no'

    ");



    while($order=mysqli_fetch_assoc($order_query)){


        $order_id=$order['id'];



        // DELETE ORDER ITEMS

        mysqli_query($conn,"

            DELETE FROM order_items

            WHERE order_id='$order_id'

        ");




        // DELETE PAYMENTS

        mysqli_query($conn,"

            DELETE FROM payments

            WHERE order_id='$order_id'

        ");




        // DELETE ORDER

        mysqli_query($conn,"

            DELETE FROM orders

            WHERE id='$order_id'

        ");


    }







    // =========================
    // DELETE INVOICE ITEMS
    // =========================


    mysqli_query($conn,"

        DELETE FROM invoice_items

        WHERE invoice_id='$invoice_id'

    ");







    // =========================
    // DELETE INVOICE
    // =========================


    mysqli_query($conn,"

        DELETE FROM invoices

        WHERE id='$invoice_id'

    ");





    mysqli_commit($conn);



    header("Location:index.php?deleted=1");

    exit();



}

catch(Exception $e){


    mysqli_rollback($conn);


    echo "Delete Error : ".$e->getMessage();


}


?>
