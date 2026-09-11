<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";


if(!isset($_GET['order_id'])){

    die("Order ID missing");

}


$order_id = intval($_GET['order_id']);




// Get order information

$order_query = mysqli_query($conn,"

SELECT

orders.*,

customers.name AS customer_name


FROM orders


LEFT JOIN customers

ON orders.customer_id = customers.id


WHERE orders.id='$order_id'


");


$order = mysqli_fetch_assoc($order_query);



if(!$order){

    die("Order not found");

}





// Payment history

$query = mysqli_query($conn,"

SELECT *

FROM payments

WHERE order_id='$order_id'

ORDER BY id DESC


");



?>


<?php include "../includes/header.php"; ?>

<?php include "../includes/sidebar.php"; ?>



<div class="content">


<h2>

<i class="fa fa-money-bill"></i>

Payment History

</h2>



<div class="card mt-3">


<div class="card-body">


<h5>

Invoice:

INV-<?php echo date('Y'); ?>-<?php echo str_pad($order['id'],6,'0',STR_PAD_LEFT); ?>

</h5>


<p>

Customer:

<strong>

<?php echo $order['customer_name']; ?>

</strong>

</p>



<div class="row">


<div class="col-md-4">

<div class="alert alert-primary">

Grand Total

<br>

<strong>

<?php echo number_format($order['grand_total']); ?>

</strong>

</div>

</div>




<div class="col-md-4">

<div class="alert alert-success">

Deposit

<br>

<strong>

<?php echo number_format($order['deposit']); ?>

</strong>

</div>

</div>




<div class="col-md-4">

<div class="alert alert-warning">

Balance

<br>

<strong>

<?php echo number_format($order['balance']); ?>

</strong>

</div>

</div>



</div>


</div>


</div>







<div class="card mt-4">


<div class="card-body">



<table class="table table-bordered table-striped">


<thead>

<tr>

<th>
Date
</th>


<th>
Type
</th>


<th>
Amount
</th>


<th>
Method
</th>


<th>
Reference
</th>


<th>
Note
</th>


<th>
Action
</th>


</tr>


</thead>




<tbody>



<?php while($row=mysqli_fetch_assoc($query)){ ?>


<tr>


<td>

<?php echo date('d-m-Y',strtotime($row['payment_date'])); ?>

</td>




<td>

<?php echo $row['payment_type']; ?>

</td>




<td>

<?php echo number_format($row['amount']); ?>

</td>




<td>

<?php echo $row['payment_method']; ?>

</td>




<td>

<?php echo $row['reference_no']; ?>

</td>




<td>

<?php echo $row['note']; ?>

</td>




<td>


<a

href="receipt.php?id=<?php echo $row['id']; ?>"

target="_blank"

class="btn btn-success btn-sm">


<i class="fa fa-print"></i>

Receipt


</a>



</td>



</tr>



<?php } ?>



</tbody>


</table>



</div>


</div>




<a href="add.php"

class="btn btn-primary mt-3">

<i class="fa fa-plus"></i>

Add Payment

</a>



<a href="../orders/print_invoice.php?id=<?php echo $order_id; ?>"

class="btn btn-secondary mt-3">

<i class="fa fa-file-invoice"></i>

Invoice

</a>



</div>




<?php include "../includes/footer.php"; ?>