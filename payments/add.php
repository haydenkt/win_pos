<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



if(!isset($_GET['invoice_id'])){

    header("Location:../invoices/index.php");
    exit();

}



$invoice_id = intval($_GET['invoice_id']);





// GET INVOICE DATA

$invoice_query = mysqli_query($conn,"

SELECT

invoices.*,

customers.name AS customer_name,

customers.phone AS customer_phone,

customers.address AS customer_address


FROM invoices


LEFT JOIN customers

ON invoices.customer_id = customers.id


WHERE invoices.id='$invoice_id'


");



$invoice = mysqli_fetch_assoc($invoice_query);



if(!$invoice){

    die("Invoice not found");

}





// PAYMENT HISTORY

$payment_query = mysqli_query($conn,"

SELECT *

FROM payments

WHERE invoice_id='$invoice_id'

ORDER BY payment_date DESC, id DESC

");





include "../includes/header.php";
include "../includes/sidebar.php";

?>



<div class="container-fluid">


<h2 class="mb-4">

<i class="fa fa-money-bill"></i>

Invoice Payment

</h2>





<!-- =========================
INVOICE INFORMATION
========================= -->


<div class="card mb-3">


<div class="card-body">


<h5>

<i class="fa fa-file-invoice"></i>

Invoice Information

</h5>



<hr>



<div class="row">


<div class="col-md-4">

<b>
Invoice No
</b>

<br>

<?=$invoice['invoice_no'];?>

</div>




<div class="col-md-4">

<b>
Customer
</b>

<br>

<?=$invoice['customer_name'];?>

</div>




<div class="col-md-4">

<b>
Phone
</b>

<br>

<?=$invoice['customer_phone'];?>

</div>


</div>



<br>



<div class="row">


<div class="col-md-4">


<div class="border rounded p-3">


<b>

Grand Total

</b>


<h4 class="text-primary">

<?=number_format($invoice['grand_total']);?>

</h4>


</div>


</div>




<div class="col-md-4">


<div class="border rounded p-3">


<b>

Paid

</b>


<h4 class="text-success">

<?=number_format($invoice['deposit']);?>

</h4>


</div>


</div>





<div class="col-md-4">


<div class="border rounded p-3">


<b>

Balance

</b>


<h4 class="text-danger">

<?=number_format($invoice['balance']);?>

</h4>


</div>


</div>


</div>



</div>


</div>
<!-- =========================
PAYMENT HISTORY
========================= -->


<div class="card mb-3">


<div class="card-header">


<h5 class="mb-0">

<i class="fa fa-history"></i>

Payment History

</h5>


</div>



<div class="card-body">


<?php if(mysqli_num_rows($payment_query)>0){ ?>



<div class="table-responsive">


<table class="table table-bordered table-striped table-hover">


<thead class="table-dark">


<tr>

<th>
Date
</th>


<th>
Amount
</th>


<th>
Method
</th>


<th>
Payment Type
</th>


<th>
Reference
</th>


<th>
Note
</th>


<th width="120">
Action
</th>


</tr>


</thead>



<tbody>



<?php while($pay=mysqli_fetch_assoc($payment_query)){ ?>


<tr>


<td>

<?=date(
'd-m-Y',
strtotime($pay['payment_date'])
);?>

</td>



<td>

<strong>

<?=number_format($pay['amount']);?>

</strong>

</td>




<td>


<?php

$color="secondary";


switch($pay['payment_method']){


case "Cash":

$color="success";

break;


case "Bank Transfer":

$color="primary";

break;


case "Mobile Payment":

$color="info";

break;


}


?>


<span class="badge bg-<?=$color;?>">

<?=$pay['payment_method'];?>

</span>


</td>




<td>


<span class="badge bg-dark">

<?=$pay['payment_type'];?>

</span>


</td>




<td>

<?=$pay['reference_no'];?>

</td>




<td>

<?=$pay['note'];?>

</td>




<td>



<a href="edit.php?id=<?=$pay['id'];?>"

class="btn btn-warning btn-sm"

title="Edit">

<i class="fa fa-edit"></i>

</a>





<a href="delete.php?id=<?=$pay['id'];?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Delete this payment?')"

title="Delete">

<i class="fa fa-trash"></i>

</a>



</td>



</tr>



<?php } ?>



</tbody>



</table>


</div>



<?php }else{ ?>


<div class="alert alert-info">

No payment history found.

</div>


<?php } ?>



</div>


</div>
<!-- =========================
ADD PAYMENT
========================= -->


<div class="card">


<div class="card-header">


<h5 class="mb-0">

<i class="fa fa-plus-circle"></i>

Add New Payment

</h5>


</div>




<div class="card-body">


<form method="POST" action="save.php">



<input type="hidden"

name="invoice_id"

value="<?=$invoice['id'];?>">





<input type="hidden"

name="customer_id"

value="<?=$invoice['customer_id'];?>">







<div class="row">



<div class="col-md-6 mb-3">


<label>

Payment Type

</label>


<select name="payment_type"

class="form-control"

required>


<option value="Deposit">

Deposit

</option>


<option value="Balance">

Balance

</option>


<option value="Full Payment">

Full Payment

</option>


</select>


</div>






<div class="col-md-6 mb-3">


<label>

Amount

</label>


<input type="number"

step="0.01"

name="amount"

class="form-control"

max="<?=$invoice['balance'];?>"

required>


</div>




</div>







<div class="row">



<div class="col-md-6 mb-3">


<label>

Payment Method

</label>


<select name="payment_method"

class="form-control"

required>


<option value="Cash">

Cash

</option>


<option value="Bank Transfer">

Bank Transfer

</option>


<option value="Mobile Payment">

Mobile Payment

</option>


</select>


</div>






<div class="col-md-6 mb-3">


<label>

Reference No

</label>


<input type="text"

name="reference_no"

class="form-control">


</div>



</div>







<div class="row">



<div class="col-md-6 mb-3">


<label>

Payment Date

</label>


<input type="date"

name="payment_date"

class="form-control"

value="<?=date('Y-m-d');?>"

required>


</div>





<div class="col-md-6 mb-3">


<label>

Note

</label>


<input type="text"

name="note"

class="form-control">


</div>



</div>









<button type="submit"

class="btn btn-success">


<i class="fa fa-save"></i>

Save Payment


</button>





<a href="../invoices/index.php"

class="btn btn-secondary">


<i class="fa fa-arrow-left"></i>

Back


</a>





</form>


</div>


</div>




</div>





<?php

include "../includes/footer.php";

?>