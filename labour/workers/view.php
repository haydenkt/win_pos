<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}


include "../../config/database.php";



$id = $_GET['id'];



// Worker information

$worker_sql = "

SELECT *

FROM workers

WHERE id='$id'

";


$worker_result = mysqli_query($conn,$worker_sql);

$worker = mysqli_fetch_assoc($worker_result);






// Total earned

$earned_sql = "

SELECT COALESCE(SUM(earned_amount),0) AS total

FROM labour_records

WHERE worker_id='$id'

";


$earned_result = mysqli_query($conn,$earned_sql);

$earned = mysqli_fetch_assoc($earned_result)['total'];






// Cash payment

$cash_sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE worker_id='$id'

AND type='Cash Payment'

";


$cash_result = mysqli_query($conn,$cash_sql);

$cash_paid = mysqli_fetch_assoc($cash_result)['total'];







// Saving deposit

$saving_sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE worker_id='$id'

AND type='Saving Deposit'

";


$saving_result = mysqli_query($conn,$saving_sql);

$saving = mysqli_fetch_assoc($saving_result)['total'];






// Saving withdrawal

$withdraw_sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE worker_id='$id'

AND type='Saving Withdrawal'

";


$withdraw_result = mysqli_query($conn,$withdraw_sql);

$withdraw = mysqli_fetch_assoc($withdraw_result)['total'];








// Advance taken

$advance_sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE worker_id='$id'

AND type='Advance'

";


$advance_result = mysqli_query($conn,$advance_sql);

$advance_taken = mysqli_fetch_assoc($advance_result)['total'];







// Advance repayment

$advance_repay_sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE worker_id='$id'

AND type='Advance Repayment'

";


$advance_repay_result = mysqli_query($conn,$advance_repay_sql);

$advance_repaid = mysqli_fetch_assoc($advance_repay_result)['total'];





// Balance

$advance = $advance_taken - $advance_repaid;


$salary_balance = $earned - $cash_paid;


$saving_balance = $saving - $withdraw;








// Attendance

$records = mysqli_query($conn,"

SELECT *

FROM labour_records

WHERE worker_id='$id'

ORDER BY work_date DESC

");






// Transactions

$transactions = mysqli_query($conn,"

SELECT *

FROM labour_transactions

WHERE worker_id='$id'

ORDER BY transaction_date DESC

");






include "../../includes/header.php";

include "../../includes/sidebar.php";

?>





<div class="container-fluid">





<div class="d-flex justify-content-between align-items-center mb-3">


<h2>

<i class="fa fa-user"></i>

<?=htmlspecialchars($worker['name'])?>

</h2>



<a href="index.php" class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>









<!-- Worker Info -->


<div class="card shadow-sm mb-3">


<div class="card-body">



<div class="row">



<div class="col-md-3">

<strong>Name</strong>

<br>

<?=$worker['name']?>

</div>





<div class="col-md-3">

<strong>Position</strong>

<br>

<?=$worker['position']?>

</div>





<div class="col-md-3">

<strong>Daily Pay</strong>

<br>

<?=number_format($worker['daily_rate'])?>

</div>





<div class="col-md-3">

<strong>Status</strong>

<br>


<?php if($worker['status']=="Active"){ ?>

<span class="badge bg-success">

Active

</span>

<?php }else{ ?>

<span class="badge bg-secondary">

Inactive

</span>

<?php } ?>


</div>



</div>





<hr>





<div class="row">



<div class="col-md-6">


<strong>

Default Cash

</strong>


<br>


<?=number_format($worker['default_cash'])?>



</div>







<div class="col-md-6">


<strong>

Default Saving

</strong>


<br>


<?=number_format($worker['default_saving'])?>



</div>



</div>



</div>


</div>









<!-- Summary -->


<div class="row mb-3">






<div class="col-md-3">

<div class="card shadow-sm border-danger">


<div class="card-body">


<h6 class="text-danger">

<i class="fa fa-exclamation-triangle"></i>

Advance Balance

</h6>



<h4 class="text-danger">

<?=number_format($advance,2)?>

</h4>


</div>


</div>

</div>









<div class="col-md-3">

<div class="card shadow-sm">


<div class="card-body">


<h6>

Cash Paid

</h6>


<h4>

<?=number_format($cash_paid,2)?>

</h4>


</div>


</div>

</div>









<div class="col-md-3">

<div class="card shadow-sm">


<div class="card-body">


<h6>

Salary Balance

</h6>


<h4>

<?=number_format($salary_balance,2)?>

</h4>


</div>


</div>

</div>








<div class="col-md-3">

<div class="card shadow-sm">


<div class="card-body">


<h6>

Saving Balance

</h6>


<h4>

<?=number_format($saving_balance,2)?>

</h4>


</div>


</div>

</div>






</div>









<!-- Attendance History -->


<div class="card shadow-sm mb-3">


<div class="card-header">

<h5>

<i class="fa fa-calendar"></i>

Attendance History

</h5>

</div>





<div class="card-body table-responsive">


<table class="table table-bordered">


<thead class="table-dark">


<tr>

<th>Date</th>

<th>Status</th>

<th>Days</th>

<th>Earned</th>

</tr>


</thead>



<tbody>



<?php while($row=mysqli_fetch_assoc($records)){ ?>


<tr>


<td>

<?=$row['work_date']?>

</td>



<td>

<?=$row['status']?>

</td>



<td>

<?=$row['days']?>

</td>



<td class="text-end">

<?=number_format($row['earned_amount'],2)?>

</td>



</tr>


<?php } ?>



</tbody>


</table>


</div>


</div>









<!-- Money History -->


<div class="card shadow-sm">


<div class="card-header">


<h5>

<i class="fa fa-money"></i>

Money History

</h5>


</div>





<div class="card-body table-responsive">


<table class="table table-bordered">


<thead class="table-dark">


<tr>

<th>Date</th>

<th>Type</th>

<th>Amount</th>

<th>Note</th>

</tr>


</thead>



<tbody>



<?php while($row=mysqli_fetch_assoc($transactions)){ ?>


<tr>


<td>

<?=$row['transaction_date']?>

</td>



<td>


<?=$row['type']?>


</td>




<td class="text-end">

<?=number_format($row['amount'],2)?>

</td>




<td>

<?=htmlspecialchars($row['note'])?>

</td>



</tr>


<?php } ?>



</tbody>


</table>


</div>


</div>






</div>





<?php

include "../../includes/footer.php";

?>