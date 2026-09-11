<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";


if(!isset($_GET['id'])){

    die("Payment ID missing");

}


$payment_id = intval($_GET['id']);




// GET PAYMENT DATA

$query = mysqli_query($conn,"

SELECT

payments.*,

customers.name AS customer_name,
customers.phone AS customer_phone,

orders.id AS order_number,
orders.grand_total


FROM payments


LEFT JOIN customers

ON payments.customer_id = customers.id


LEFT JOIN orders

ON payments.order_id = orders.id


WHERE payments.id='$payment_id'


");



$payment=mysqli_fetch_assoc($query);



if(!$payment){

    die("Payment not found");

}





// COMPANY SETTINGS

function getSetting($conn,$key){

    $query=mysqli_query($conn,"

    SELECT setting_value

    FROM settings

    WHERE setting_group='company'

    AND setting_key='$key'

    ");

    if($row=mysqli_fetch_assoc($query)){

        return $row['setting_value'];

    }

    return "";

}



$company_name=getSetting($conn,'company_name');
$company_phone=getSetting($conn,'phone');



?>



<!DOCTYPE html>

<html>

<head>

<title>Payment Receipt</title>


<style>


@page{

    size:A5 portrait;

    margin:10mm;

}


body{

    font-family:Arial;

}


.receipt{

    width:100%;

}


.header{

    text-align:center;

}


h2{

    margin:5px;

}


table{

    width:100%;

    border-collapse:collapse;

}


td{

    padding:8px;

}



.amount{

    font-size:22px;

    font-weight:bold;

}



.footer{

    margin-top:50px;

    display:flex;

    justify-content:space-between;

}



@media print{

    .no-print{

        display:none;

    }

}


</style>


</head>



<body>


<div class="no-print">

<button onclick="window.print()">

Print

</button>

</div>



<div class="receipt">



<div class="header">


<h2>

<?php echo $company_name; ?>

</h2>


<p>

Phone:
<?php echo $company_phone; ?>

</p>


<h2>

PAYMENT RECEIPT

</h2>


</div>




<hr>



<table>


<tr>

<td>
Receipt No:
</td>

<td>

PAY-<?php echo str_pad($payment['id'],6,'0',STR_PAD_LEFT); ?>

</td>

</tr>



<tr>

<td>
Date:
</td>

<td>

<?php echo date('d-m-Y',strtotime($payment['payment_date'])); ?>

</td>

</tr>



<tr>

<td>
Customer:
</td>

<td>

<?php echo $payment['customer_name']; ?>

</td>

</tr>




<tr>

<td>
Invoice:
</td>

<td>

INV-<?php echo date('Y'); ?>-<?php echo str_pad($payment['order_number'],6,'0',STR_PAD_LEFT); ?>

</td>

</tr>




<tr>

<td>
Payment Type:
</td>

<td>

<?php echo $payment['payment_type']; ?>

</td>

</tr>



<tr>

<td>
Method:
</td>

<td>

<?php echo $payment['payment_method']; ?>

</td>

</tr>



<tr>

<td>
Reference:
</td>

<td>

<?php echo $payment['reference_no']; ?>

</td>

</tr>



<tr>

<td class="amount">

Amount Paid:

</td>


<td class="amount">

<?php echo number_format($payment['amount']); ?>

MMK

</td>


</tr>



<tr>

<td>

Note:

</td>


<td>

<?php echo $payment['note']; ?>

</td>


</tr>


</table>





<div class="footer">


<div>

Customer Signature

</div>


<div>

Received By

</div>


</div>



</div>



</body>

</html>