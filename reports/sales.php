<?php


if(session_status()==PHP_SESSION_NONE){

    session_start();

}


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}



include "../config/database.php";


include "../includes/header.php";
include "../includes/sidebar.php";





$from="";
$to="";

$where="";



if(isset($_GET['search'])){


    $from=mysqli_real_escape_string($conn,$_GET['from']);

    $to=mysqli_real_escape_string($conn,$_GET['to']);



    if($from!="" && $to!=""){


        $where="

        WHERE DATE(invoices.created_at)

        BETWEEN '$from' AND '$to'

        ";


    }


}






// =======================
// SUMMARY
// =======================


$summary=mysqli_query($conn,"

SELECT


COUNT(id) AS total_invoice,


COALESCE(SUM(grand_total),0) AS total_sales,


COALESCE(SUM(deposit),0) AS total_paid,


COALESCE(SUM(balance),0) AS total_balance



FROM invoices



$where



");



$total=mysqli_fetch_assoc($summary);








// =======================
// MATERIAL TYPE SALES
// =======================


$material=mysqli_query($conn,"

SELECT


material_types.name AS material_name,


COUNT(invoice_items.id) AS total_items,


COALESCE(SUM(invoice_items.quantity),0) AS total_qty,


COALESCE(SUM(invoice_items.sqft),0) AS total_sqft,


COALESCE(SUM(invoice_items.total),0) AS total_sales



FROM invoice_items



LEFT JOIN invoices

ON invoice_items.invoice_id=invoices.id



LEFT JOIN factory_products

ON invoice_items.product_id=factory_products.id



LEFT JOIN material_types

ON factory_products.material_type_id=material_types.id



$where



GROUP BY material_types.id



ORDER BY total_sales DESC



");









// =======================
// TOP PRODUCTS
// =======================


$products=mysqli_query($conn,"

SELECT


invoice_items.product_name,


SUM(invoice_items.quantity) AS qty,


SUM(invoice_items.sqft) AS sqft,


SUM(invoice_items.total) AS sales



FROM invoice_items



LEFT JOIN invoices

ON invoice_items.invoice_id=invoices.id



$where



GROUP BY invoice_items.product_name



ORDER BY sales DESC



LIMIT 10



");



?>



<h2>

<i class="fa fa-chart-line"></i>

Sales Report

</h2>






<!-- DATE FILTER -->


<div class="card mt-3">


<div class="card-body">


<form method="GET">


<div class="row">



<div class="col-md-3">


<label>

From

</label>


<input

type="date"

name="from"

class="form-control"

value="<?=$from;?>">


</div>





<div class="col-md-3">


<label>

To

</label>


<input

type="date"

name="to"

class="form-control"

value="<?=$to;?>">


</div>





<div class="col-md-6 d-flex align-items-end">


<button

class="btn btn-primary me-2"

name="search">

<i class="fa fa-search"></i>

Search

</button>




<a
href="sales.php"
class="btn btn-secondary me-2">

Reset

</a>


<a

href="export_sales_excel.php?from=<?=$from;?>&to=<?=$to;?>"

class="btn btn-success">

<i class="fa fa-file-excel"></i>

Export Excel

</a>



</div>


</div>


</form>


</div>


</div>









<!-- SUMMARY CARDS -->


<div class="row mt-4">



<div class="col-md-3">


<div class="card bg-primary text-white">


<div class="card-body text-center">


<h6>

Invoices

</h6>


<h3>

<?=number_format($total['total_invoice']);?>

</h3>


</div>


</div>


</div>






<div class="col-md-3">


<div class="card bg-success text-white">


<div class="card-body text-center">


<h6>

Sales

</h6>


<h3>

<?=number_format($total['total_sales']);?>

</h3>


</div>


</div>


</div>






<div class="col-md-3">


<div class="card bg-info text-white">


<div class="card-body text-center">


<h6>

Paid

</h6>


<h3>

<?=number_format($total['total_paid']);?>

</h3>


</div>


</div>


</div>






<div class="col-md-3">


<div class="card bg-danger text-white">


<div class="card-body text-center">


<h6>

Balance

</h6>


<h3>

<?=number_format($total['total_balance']);?>

</h3>


</div>


</div>


</div>


</div>









<!-- MATERIAL TYPE REPORT -->


<div class="card mt-4">


<div class="card-header">


<h5>

<i class="fa fa-layer-group"></i>

Sales By Material Type

</h5>


</div>




<div class="card-body">


<table class="table table-bordered table-striped">


<thead class="table-dark">


<tr>

<th>#</th>

<th>Material Type</th>

<th>Items</th>

<th>Qty</th>

<th>SQFT</th>

<th>Sales</th>


</tr>


</thead>



<tbody>



<?php


$i=1;


while($row=mysqli_fetch_assoc($material)){


?>


<tr>


<td><?=$i++;?></td>


<td>

<?=$row['material_name'] ?? 'Unknown';?>

</td>


<td>

<?=$row['total_items'];?>

</td>


<td>

<?=$row['total_qty'];?>

</td>


<td>

<?=number_format($row['total_sqft'],2);?>

</td>


<td>

<?=number_format($row['total_sales']);?>

</td>


</tr>


<?php } ?>



</tbody>


</table>


</div>


</div>









<!-- TOP PRODUCTS -->


<div class="card mt-4">


<div class="card-header">


<h5>

Top Selling Products

</h5>


</div>



<div class="card-body">


<table class="table table-bordered">


<thead class="table-dark">


<tr>

<th>#</th>

<th>Product</th>

<th>Qty</th>

<th>SQFT</th>

<th>Sales</th>


</tr>


</thead>



<tbody>



<?php


$i=1;


while($row=mysqli_fetch_assoc($products)){


?>


<tr>


<td><?=$i++;?></td>


<td><?=$row['product_name'];?></td>


<td><?=$row['qty'];?></td>


<td><?=number_format($row['sqft'],2);?></td>


<td><?=number_format($row['sales']);?></td>


</tr>



<?php } ?>


</tbody>


</table>


</div>


</div>








<style>


@media print{


.sidebar,

.navbar,

form,

.btn{

display:none!important;

}


.card{

border:none!important;

}


}



</style>






<?php include "../includes/footer.php"; ?>