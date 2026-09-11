<?php

if(session_status() == PHP_SESSION_NONE){

    session_start();

}


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";





// CHECK ID

if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}



$id = intval($_GET['id']);





// GET INVOICE ID BEFORE DELETE

$get=mysqli_query($conn,"

SELECT invoice_id

FROM payments

WHERE id='$id'

");



$payment=mysqli_fetch_assoc($get);





if($payment){



    $invoice_id=intval($payment['invoice_id']);





    // DELETE PAYMENT


    mysqli_query($conn,"

    DELETE FROM payments

    WHERE id='$id'

    ");







    // RECALCULATE TOTAL PAYMENT


    $paid_query=mysqli_query($conn,"

    SELECT 

    IFNULL(SUM(amount),0) AS total_paid

    FROM payments

    WHERE invoice_id='$invoice_id'

    ");




    $paid=mysqli_fetch_assoc($paid_query);



    $total_paid=floatval($paid['total_paid']);







    // GET INVOICE TOTAL


    $invoice_query=mysqli_query($conn,"

    SELECT 

    grand_total

    FROM invoices

    WHERE id='$invoice_id'

    ");




    $invoice=mysqli_fetch_assoc($invoice_query);





    if($invoice){



        $grand_total=floatval($invoice['grand_total']);



        $balance=$grand_total-$total_paid;





        if($balance<=0){


            $balance=0;

            $status="Paid";


        }
        elseif($total_paid>0){


            $status="Partial";


        }
        else{


            $status="Unpaid";


        }







        // UPDATE INVOICE


        mysqli_query($conn,"

        UPDATE invoices SET


        deposit='$total_paid',

        balance='$balance',

        payment_status='$status'


        WHERE id='$invoice_id'


        ");



    }



}






header("Location:index.php");

exit();


?>