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



$id = intval($_GET['id']);




// Get customer

$customer_query = mysqli_query($conn,"

SELECT *

FROM customers

WHERE id='$id'

");


$customer = mysqli_fetch_assoc($customer_query);



if(!$customer){

    echo "Customer not found";
    exit();

}





// Get orders

$order_query = mysqli_query($conn,"

SELECT *

FROM orders

WHERE customer_id='$id'

ORDER BY id DESC

");





include "../includes/header.php";
include "../includes/sidebar.php";


?>



<h2>

<i class="fa fa-user"></i>

Customer Profile

</h2>





<div class="card mt-3">


<div class="card-body">



<h4>

<?=$customer['name'];?>

</h4>


<p>

<b>Phone:</b>

<?=$customer['phone'];?>

</p>



<p>

<b>Address:</b>

<?=$customer['address'];?>

</p>



</div>


</div>







<h3 class="mt-4">

Order History

</h3>





<div class="card">


<div class="card-body">



<table class="table table-bordered table-striped">


<thead>


<tr>


<th>
Order #
</th>


<th>
Date
</th>


<th>
Grand Total
</th>


<th>
Deposit
</th>


<th>
Balance
</th>


<th>
Action
</th>


</tr>


</thead>



<tbody>



<?php



$total_orders = 0;

$total_paid = 0;

$total_balance = 0;



while($order=mysqli_fetch_assoc($order_query)){



$total_orders++;


$total_paid += $order['deposit'];

$total_balance += $order['balance'];



?>



<tr>


<td>

#<?=$order['id'];?>

</td>



<td>

<?=date('d-m-Y',
strtotime($order['created_at']));?>

</td>



<td>

<?=number_format($order['grand_total']);?>

</td>



<td>

<?=number_format($order['deposit']);?>

</td>



<td>

<?=number_format($order['balance']);?>

</td>



<td>



<a href="../orders/view.php?id=<?=$order['id'];?>"

class="btn btn-info btn-sm">


<i class="fa fa-eye"></i>


</a>



</td>


</tr>



<?php } ?>



</tbody>


</table>



</div>


</div>






<div class="row mt-3">



<div class="col-md-4">


<div class="alert alert-primary">

Total Orders:

<b>
<?=$total_orders;?>
</b>

</div>


</div>




<div class="col-md-4">


<div class="alert alert-success">

Total Paid:

<b>

<?=number_format($total_paid);?>

</b>

</div>


</div>




<div class="col-md-4">


<div class="alert alert-warning">

Balance:

<b>

<?=number_format($total_balance);?>

</b>

</div>


</div>


</div>





<a href="../orders/add.php?customer_id=<?=$customer['id'];?>"

class="btn btn-success">

<i class="fa fa-plus"></i>

New Order

</a>


<a href="index.php"

class="btn btn-secondary">

Back

</a>





<?php

include "../includes/footer.php";

?>