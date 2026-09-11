<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";

include "../includes/header.php";
include "../includes/sidebar.php";



$search="";


if(isset($_GET['search'])){

    $search=mysqli_real_escape_string(
        $conn,
        $_GET['search']
    );

}




$sql="

SELECT

orders.*,

customers.name AS customer_name


FROM orders


LEFT JOIN customers

ON orders.customer_id = customers.id



WHERE

customers.name LIKE '%$search%'

OR orders.invoice_no LIKE '%$search%'

OR orders.order_status LIKE '%$search%'



ORDER BY orders.id DESC


";



$result=mysqli_query($conn,$sql);



?>



<h2>

<i class="fa fa-industry"></i>

Sales Orders

</h2>





<div class="row mt-3">


<div class="col-md-6">


<form method="GET">


<div class="input-group">


<input type="text"

name="search"

class="form-control"

placeholder="Search invoice, customer, status..."

value="<?=htmlspecialchars($search);?>">


<button class="btn btn-primary">

<i class="fa fa-search"></i>

Search

</button>


</div>


</form>


</div>


</div>







<div class="card mt-3">


<div class="card-body">


<table class="table table-bordered table-striped">


<thead>

<tr>

<th>#</th>

<th>Invoice No</th>

<th>Customer</th>

<th>Status</th>

<th>Date</th>

<th>Action</th>


</tr>


</thead>




<tbody>


<?php


$i=1;


while($row=mysqli_fetch_assoc($result)){


?>


<tr>


<td>

<?=$i++;?>

</td>



<td>

<a href="../invoices/view.php?id=<?=$row['id'];?>">

<?=htmlspecialchars($row['invoice_no'] ?? 'N/A');?>

</a>

</td>




<td>

<?=htmlspecialchars($row['customer_name'] ?? '');?>

</td>





<td>


<?php


$status_color="secondary";


switch($row['order_status']){


case "Quotation":

$status_color="secondary";

break;


case "Confirmed":

$status_color="info";

break;


case "Production":

$status_color="warning";

break;


case "Ready":

$status_color="primary";

break;


case "Installation":

$status_color="dark";

break;


case "Completed":

$status_color="success";

break;


}



?>


<span class="badge bg-<?=$status_color;?>">

<?=$row['order_status'] === 'Production' ? 'In Progress' : htmlspecialchars($row['order_status']);?>

</span>



</td>






<td>

<?=date(
'd-m-Y',
strtotime($row['created_at'])
);?>

</td>






<td>


<a href="view.php?id=<?=$row['id'];?>"

class="btn btn-info btn-sm">

<i class="fa fa-eye"></i>

</a>



<a href="edit.php?id=<?=$row['id'];?>"

class="btn btn-warning btn-sm">

<i class="fa fa-edit"></i>

</a>




<a href="delete.php?id=<?=$row['id'];?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Delete this order?');">

<i class="fa fa-trash"></i>

</a>



</td>



</tr>



<?php } ?>



</tbody>


</table>


</div>


</div>




<?php

include "../includes/footer.php";

?>
