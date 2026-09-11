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


$id=intval($_GET['id']);




// ORDER DATA

$order_query=mysqli_query($conn,"

SELECT

orders.*,

customers.name AS customer_name,
customers.phone,
customers.address


FROM orders


LEFT JOIN customers

ON orders.customer_id = customers.id


WHERE orders.id='$id'


");



$order=mysqli_fetch_assoc($order_query);



if(!$order){

    die("Order not found");

}






// ORDER ITEMS

$items=mysqli_query($conn,"

SELECT *

FROM order_items

WHERE order_id='$id'

");




include "../includes/header.php";
include "../includes/sidebar.php";


?>



<h2>

<i class="fa fa-industry"></i>

Order #<?=$order['id'];?>

</h2>






<div class="row mt-3">



<div class="col-md-6 mb-3">


<div class="card">


<div class="card-body">


<h5>

<i class="fa fa-user"></i>

Customer

</h5>


<p>

<b>Name:</b>

<?=$order['customer_name'];?>

</p>



<p>

<b>Phone:</b>

<?=$order['phone'];?>

</p>



<p>

<b>Address:</b>

<?=$order['address'];?>

</p>



</div>

</div>


</div>







<div class="col-md-6 mb-3">


<div class="card">


<div class="card-body">


<h5>

<i class="fa fa-file-invoice"></i>

Order Information

</h5>



<p>

<b>Invoice No:</b>

<?=$order['invoice_no'];?>

</p>





<p>

<b>Status:</b>


<?php


$color="secondary";


switch($order['order_status']){


case "Confirmed":

$color="info";

break;


case "Production":

$color="warning";

break;


case "Ready":

$color="primary";

break;


case "Completed":

$color="success";

break;


}


?>


<span class="badge bg-<?=$color;?>">

<?=$order['order_status'] === 'Production' ? 'In Progress' : htmlspecialchars($order['order_status']);?>

</span>



</p>



<p>

<b>Date:</b>

<?=date(
'd-m-Y',
strtotime($order['created_at'])
);?>

</p>



</div>


</div>


</div>



</div>









<div class="card mt-3">


<div class="card-body">


<h4>

<i class="fa fa-window-maximize"></i>

Order Items

</h4>





<div class="table-responsive">


<table class="table table-bordered table-striped">


<thead>


<tr>

<th>#</th>

<th>Product</th>

<th>Category</th>

<th>Size</th>

<th>Qty</th>

<th>SQFT</th>

<th>Calculation</th>

<th>Description</th>


</tr>


</thead>



<tbody>


<?php


$i=1;


while($item=mysqli_fetch_assoc($items)){


?>



<tr>


<td>

<?=$i++;?>

</td>



<td>

<?=$item['product_name'];?>

</td>



<td>

<?=$item['category'];?>

</td>




<td>

<?php if($item['calculation_type']=="SQFT"){ ?>


<?=$item['width'];?> × <?=$item['height'];?>


<?php }else{ ?>

-

<?php } ?>


</td>





<td>

<?=$item['quantity'];?>

</td>




<td>

<?=number_format($item['sqft'],2);?>

</td>




<td>

<?=$item['calculation_type'];?>

</td>




<td>

<?=$item['description'];?>

</td>



</tr>



<?php } ?>



</tbody>


</table>


</div>



</div>


</div>









<div class="card mt-3">


<div class="card-body">


<h5>

<i class="fa fa-sticky-note"></i>

Order Notes

</h5>



<p>

<?=nl2br(htmlspecialchars($order['notes']));?>

</p>



</div>


</div>









<div class="mt-3 d-flex flex-wrap gap-2">


<a href="edit.php?id=<?=$order['id'];?>"

class="btn btn-warning">


<i class="fa fa-edit"></i>

Edit Order


</a>





<a href="index.php"

class="btn btn-secondary">


<i class="fa fa-arrow-left"></i>

Back

</a>



</div>







<?php

include "../includes/footer.php";

?>
