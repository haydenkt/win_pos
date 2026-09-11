<?php

if(session_status() == PHP_SESSION_NONE){

    session_start();

}



if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}



include_once "../config/database.php";


include_once "../includes/header.php";


include_once "../includes/sidebar.php";



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

        WHERE DATE(payments.payment_date)

        BETWEEN '$from' AND '$to'

        ";


    }


}




// PAYMENT LIST


$sql="


SELECT


payments.*,


invoices.invoice_no,


invoices.grand_total,


customers.name AS customer_name



FROM payments



LEFT JOIN invoices

ON payments.invoice_id=invoices.id



LEFT JOIN customers

ON payments.customer_id=customers.id



$where



ORDER BY payments.id DESC



";



$result=mysqli_query($conn,$sql);






// SUMMARY


$summary_sql="


SELECT


COUNT(*) AS total_payments,


COALESCE(SUM(amount),0) AS total_received,



COALESCE(

SUM(

CASE

WHEN payment_method='Cash'

THEN amount

ELSE 0

END

)

,0) AS cash_total,





COALESCE(

SUM(

CASE

WHEN payment_method='Bank Transfer'

THEN amount

ELSE 0

END

)

,0) AS bank_total,





COALESCE(

SUM(

CASE

WHEN payment_method='Mobile Payment'

THEN amount

ELSE 0

END

)

,0) AS mobile_total



FROM payments



$where



";



$summary=mysqli_query($conn,$summary_sql);



$total=mysqli_fetch_assoc($summary);



?>
<h2>

<i class="fa fa-money-bill-wave"></i>

Payment Report

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

type="submit"

name="search"

class="btn btn-primary me-2">


<i class="fa fa-search"></i>

Search


</button>





<a

href="payments.php"

class="btn btn-secondary me-2">


<i class="fa fa-undo"></i>

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









<div class="row mt-4">





<div class="col-md-3 mb-3">


<div class="card border-primary">


<div class="card-body text-center">


<h6>

Total Payments

</h6>


<h3>

<?=number_format($total['total_payments'] ?? 0);?>

</h3>


</div>


</div>


</div>








<div class="col-md-3 mb-3">


<div class="card border-success">


<div class="card-body text-center">


<h6>

Total Received

</h6>


<h3>

<?=number_format($total['total_received'] ?? 0);?>

</h3>


</div>


</div>


</div>








<div class="col-md-3 mb-3">


<div class="card border-info">


<div class="card-body text-center">


<h6>

Cash Payment

</h6>


<h3>

<?=number_format($total['cash_total'] ?? 0);?>

</h3>


</div>


</div>


</div>








<div class="col-md-3 mb-3">


<div class="card border-warning">


<div class="card-body text-center">


<h6>

Bank Transfer

</h6>


<h3>

<?=number_format($total['bank_total'] ?? 0);?>

</h3>


</div>


</div>


</div>




</div>








<div class="row">



<div class="col-md-4 mb-3">


<div class="card bg-info text-white">


<div class="card-body text-center">


<h6>

Mobile Payment

</h6>


<h3>

<?=number_format($total['mobile_total'] ?? 0);?>

</h3>


</div>


</div>


</div>



</div>









<div class="card mt-3">


<div class="card-header">


<h5>

<i class="fa fa-table"></i>

Payment History


</h5>


</div>





<div class="card-body">


<div class="table-responsive">



<table class="table table-bordered table-striped table-hover">


<thead class="table-dark">


<tr>

<th>#</th>

<th>Date</th>

<th>Invoice No</th>

<th>Customer</th>

<th>Amount</th>

<th>Method</th>

<th>Type</th>

<th>Reference</th>

<th>Note</th>


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

<?=date(
'd-m-Y',
strtotime($row['payment_date'])
);?>

</td>






<td>


<?php if($row['invoice_no']){ ?>


<span class="badge bg-primary">

<?=$row['invoice_no'];?>

</span>


<?php }else{ ?>


<span class="badge bg-secondary">

No Invoice

</span>


<?php } ?>


</td>






<td>

<?=$row['customer_name'];?>

</td>






<td>


<strong>

<?=number_format($row['amount']);?>

</strong>


</td>






<td>



<?php


$method_color="secondary";


switch($row['payment_method']){


case "Cash":

$method_color="success";

break;



case "Bank Transfer":

$method_color="primary";

break;



case "Mobile Payment":

$method_color="info";

break;



}



?>


<span class="badge bg-<?=$method_color;?>">

<?=$row['payment_method'];?>

</span>


</td>






<td>


<span class="badge bg-dark">

<?=$row['payment_type'];?>

</span>


</td>






<td>

<?=$row['reference_no'];?>

</td>






<td>

<?=$row['note'];?>

</td>



</tr>



<?php } ?>



</tbody>



</table>



</div>


</div>


</div>








<?php


if(mysqli_num_rows($result)==0){


?>

<script>

alert("No payment records found");

</script>


<?php

}


?>







<style>


@media print {


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