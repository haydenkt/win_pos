<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";
require_once "../includes/audit.php";



$customer_id = intval($_POST['customer_id']);

$invoice_id = intval($_POST['invoice_id']);

$amount = floatval($_POST['amount']);



$payment_method = mysqli_real_escape_string(
    $conn,
    $_POST['payment_method']
);


$reference_no = mysqli_real_escape_string(
    $conn,
    $_POST['reference_no']
);


$payment_type = mysqli_real_escape_string(
    $conn,
    $_POST['payment_type']
);


$payment_date = mysqli_real_escape_string(
    $conn,
    $_POST['payment_date']
);


$note = mysqli_real_escape_string(
    $conn,
    $_POST['note']
);




// GET INVOICE

$invoice_query=mysqli_query($conn,"

SELECT

grand_total

FROM invoices

WHERE id='$invoice_id'

");


$invoice=mysqli_fetch_assoc($invoice_query);



if(!$invoice){

    die("Invoice not found");

}




// CHECK OVER PAYMENT

$current_paid=mysqli_fetch_assoc(mysqli_query($conn,"

SELECT

IFNULL(SUM(amount),0) AS paid

FROM payments

WHERE invoice_id='$invoice_id'

"));



$already_paid=floatval($current_paid['paid']);



$new_total=$already_paid+$amount;



if($new_total > $invoice['grand_total']){

    die("Payment amount is higher than balance");

}







// SAVE PAYMENT


mysqli_query($conn,"

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
'$invoice_id',
'$customer_id',
NULL,
'$amount',
'$payment_method',
'$reference_no',
'$payment_type',
'$payment_date',
'$note',
NOW()
)

");

$payment_id = (int) $conn->insert_id;
auditLog($conn,'CREATE','payment',$payment_id,'Added payment for invoice #'.$invoice_id,null,['amount'=>$amount,'date'=>$payment_date]);








// UPDATE INVOICE


$total_paid=mysqli_fetch_assoc(mysqli_query($conn,"

SELECT

IFNULL(SUM(amount),0) AS paid

FROM payments

WHERE invoice_id='$invoice_id'

"));



$total_paid=floatval($total_paid['paid']);




$balance=$invoice['grand_total']-$total_paid;



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







mysqli_query($conn,"

UPDATE invoices SET

deposit='$total_paid',

balance='$balance',

payment_status='$status'


WHERE id='$invoice_id'

");







echo "

<script>

alert('Payment Saved Successfully');

window.location='../invoices/index.php';

</script>

";

?>
