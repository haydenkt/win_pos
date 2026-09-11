<?php

if(session_status() == PHP_SESSION_NONE){

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


    $from=mysqli_real_escape_string(
        $conn,
        $_GET['from'] ?? ''
    );


    $to=mysqli_real_escape_string(
        $conn,
        $_GET['to'] ?? ''
    );



    if($from!="" && $to!=""){


        $where="

        WHERE DATE(invoices.created_at)

        BETWEEN '$from' AND '$to'

        ";


    }


}








// ============================
// CUSTOMER REPORT
// ============================


$sql="


SELECT


customers.id,


customers.name,


customers.phone,


customers.address,



COUNT(invoices.id) AS total_invoices,



COALESCE(
SUM(invoices.grand_total),
0
) AS total_sales,



COALESCE(
SUM(invoices.deposit),
0
) AS total_paid,



COALESCE(
SUM(invoices.balance),
0
) AS total_balance



FROM customers



LEFT JOIN invoices

ON customers.id=invoices.customer_id



$where



GROUP BY customers.id



ORDER BY total_sales DESC



";





$result=mysqli_query($conn,$sql);








// ============================
// SUMMARY
// ============================


$summary_sql="


SELECT



COUNT(DISTINCT customers.id) AS total_customers,



COALESCE(
SUM(invoices.grand_total),
0
) AS total_sales,



COALESCE(
SUM(invoices.deposit),
0
) AS total_received,



COALESCE(
SUM(invoices.balance),
0
) AS total_balance



FROM customers



LEFT JOIN invoices

ON customers.id=invoices.customer_id



$where



";




$summary=mysqli_query($conn,$summary_sql);


$total=mysqli_fetch_assoc($summary);



?>





<h2>

<i class="fa fa-users"></i>

Customer Report

</h2>








<div class="card mt-3">


<div class="card-body">


<form method="GET">


<div class="row">



<div class="col-md-3">


<label>

From Date

</label>


<input

type="date"

name="from"

class="form-control"

value="<?=$from;?>">


</div>







<div class="col-md-3">


<label>

To Date

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

href="customers.php"

class="btn btn-secondary me-2">


<i class="fa fa-refresh"></i>

Reset


</a>






<button

type="button"

onclick="window.print()"

class="btn btn-success">


<i class="fa fa-print"></i>

Print


</button>



</div>



</div>



</form>



</div>


</div>









<!-- SUMMARY CARDS -->


<div class="row mt-4">





<div class="col-md-3 mb-3">


<div class="card border-primary">


<div class="card-body text-center">


<h6>

Customers

</h6>


<h3>

<?=number_format($total['total_customers'] ?? 0);?>

</h3>


</div>


</div>


</div>







<div class="col-md-3 mb-3">


<div class="card border-success">


<div class="card-body text-center">


<h6>

Total Sales

</h6>


<h3>

<?=number_format($total['total_sales'] ?? 0);?>

</h3>


</div>


</div>


</div>







<div class="col-md-3 mb-3">


<div class="card border-info">


<div class="card-body text-center">


<h6>

Received

</h6>


<h3>

<?=number_format($total['total_received'] ?? 0);?>

</h3>


</div>


</div>


</div>







<div class="col-md-3 mb-3">


<div class="card border-danger">


<div class="card-body text-center">


<h6>

Balance

</h6>


<h3>

<?=number_format($total['total_balance'] ?? 0);?>

</h3>


</div>


</div>


</div>





</div>









<!-- TABLE -->


<div class="card mt-3">


<div class="card-header">


<h5>

<i class="fa fa-table"></i>

Customer Sales History

</h5>


</div>







<div class="card-body">


<div class="table-responsive">



<table class="table table-bordered table-striped table-hover">


<thead class="table-dark">


<tr>


<th>#</th>

<th>Customer</th>

<th>Phone</th>

<th>Total Invoice</th>

<th>Total Sales</th>

<th>Paid</th>

<th>Balance</th>


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


<?=$row['name'];?>


</td>





<td>


<?=$row['phone'];?>


</td>





<td>


<span class="badge bg-primary">

<?=$row['total_invoices'];?>

</span>


</td>






<td>


<?=number_format($row['total_sales']);?>


</td>






<td>


<?=number_format($row['total_paid']);?>


</td>






<td>


<?=number_format($row['total_balance']);?>


</td>



</tr>



<?php

}

?>



</tbody>



</table>


</div>


</div>


</div>









<?php


if(mysqli_num_rows($result)==0){


?>


<script>

alert("No customer records found");

</script>


<?php


}


?>








<style>


@media print{


.sidebar,

.navbar,

.btn,

form{

display:none !important;

}



.card{

border:none !important;

}



.table{

font-size:12px;

}



h2{

text-align:center;

}



}



</style>








<?php

include_once "../includes/footer.php";

?>