<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include '../../config/database.php';
include "../../includes/permissions.php";

requirePermission('labour_view');

include '../../includes/header.php';
include '../../includes/sidebar.php';



// ========================================
// SUMMARY
// ========================================


// Cash Payment

$sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE type='Cash Payment'

";

$result = $conn->query($sql);

$total_cash = $result->fetch_assoc()['total'];




// Saving Deposit

$sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE type='Saving Deposit'

";

$result = $conn->query($sql);

$total_saving_deposit = $result->fetch_assoc()['total'];




// Saving Withdrawal

$sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE type='Saving Withdrawal'

";

$result = $conn->query($sql);

$total_saving_withdrawal = $result->fetch_assoc()['total'];




// Saving Balance

$total_saving =

$total_saving_deposit

-

$total_saving_withdrawal;




// Advance Taken

$sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE type='Advance'

";

$result = $conn->query($sql);

$total_advance_taken = $result->fetch_assoc()['total'];




// Advance Repayment

$sql = "

SELECT COALESCE(SUM(amount),0) AS total

FROM labour_transactions

WHERE type='Advance Repayment'

";

$result = $conn->query($sql);

$total_advance_repaid = $result->fetch_assoc()['total'];




// Advance Balance

$total_advance =

$total_advance_taken

-

$total_advance_repaid;





// ========================================
// WORKERS
// ========================================

$workers = $conn->query("

SELECT *

FROM workers

WHERE status='Active'

ORDER BY name ASC

");





// ========================================
// TRANSACTIONS
// ========================================

$transactions = $conn->query("

SELECT

labour_transactions.*,

workers.name

FROM labour_transactions

JOIN workers

ON labour_transactions.worker_id = workers.id

ORDER BY labour_transactions.id DESC

LIMIT 50

");



?>



<div class="content-wrapper">


<div class="container-fluid">



<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

<i class="fa fa-money"></i>

Labour Money Transactions

</h3>


</div>





<!-- ========================================
     SUMMARY CARDS
======================================== -->


<div class="row mb-3">





<!-- Cash -->

<div class="col-md-3 mb-3">


<div class="card shadow-sm border-success">


<div class="card-body">


<h6 class="text-success">

<i class="fa fa-money"></i>

Cash Paid

</h6>


<h3>

<?=number_format($total_cash,2)?>

</h3>


</div>


</div>


</div>







<!-- Saving -->

<div class="col-md-3 mb-3">


<div class="card shadow-sm border-primary">


<div class="card-body">


<h6 class="text-primary">

<i class="fa fa-bank"></i>

Saving Balance

</h6>


<h3>

<?=number_format($total_saving,2)?>

</h3>


</div>


</div>


</div>







<!-- Advance -->

<div class="col-md-3 mb-3">


<div class="card shadow-sm border-danger">


<div class="card-body">


<h6 class="text-danger">

<i class="fa fa-exclamation-triangle"></i>

Advance Balance

</h6>


<h3 class="text-danger">

<?=number_format($total_advance,2)?>

</h3>


</div>


</div>


</div>







<!-- Transactions -->

<div class="col-md-3 mb-3">


<div class="card shadow-sm">


<div class="card-body">


<h6>

<i class="fa fa-list"></i>

Transactions

</h6>


<h3>

<?=$transactions->num_rows?>

</h3>


</div>


</div>


</div>




</div>









<!-- ========================================
     ADD TRANSACTION
======================================== -->


<div class="card shadow-sm mb-3">


<div class="card-header">


<h5>

<i class="fa fa-plus-circle"></i>

Add Money Transaction

</h5>


</div>





<div class="card-body">


<form action="save.php" method="POST">



<div class="row">





<!-- Worker -->

<div class="col-md-3 mb-3">


<label class="form-label">

Worker

</label>


<select

name="worker_id"

class="form-select"

required>


<option value="">

Select Worker

</option>


<?php while($worker=$workers->fetch_assoc()){ ?>


<option value="<?=$worker['id']?>">

<?=htmlspecialchars($worker['name'])?>

</option>


<?php } ?>


</select>


</div>







<!-- Date -->

<div class="col-md-2 mb-3">


<label class="form-label">

Date

</label>


<input

type="date"

name="transaction_date"

class="form-control"

value="<?=date('Y-m-d')?>"

required>


</div>







<!-- Type -->

<div class="col-md-3 mb-3">


<label class="form-label">

Transaction Type

</label>


<select

name="type"

class="form-select"

required>


<option value="Cash Payment">

Cash Payment

</option>


<option value="Saving Deposit">

Saving Deposit

</option>


<option value="Saving Withdrawal">

Saving Withdrawal

</option>


<option value="Advance">

Advance

</option>


<option value="Advance Repayment">

Advance Repayment

</option>


</select>


</div>







<!-- Amount -->

<div class="col-md-2 mb-3">


<label class="form-label">

Amount

</label>


<input

type="number"

step="0.01"

min="0"

name="amount"

class="form-control"

required>


</div>







<!-- Note -->

<div class="col-md-2 mb-3">


<label class="form-label">

Note

</label>


<input

type="text"

name="note"

class="form-control"


placeholder="Optional">


</div>



</div>







<div class="text-end">


<button

type="submit"

class="btn btn-success">


<i class="fa fa-save"></i>

Save Transaction


</button>


</div>



</form>


</div>


</div>









<!-- ========================================
     TRANSACTION HISTORY
======================================== -->


<div class="card shadow-sm">


<div class="card-header">


<h5>

<i class="fa fa-history"></i>

Recent Transactions

</h5>


</div>





<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-hover align-middle">


<thead class="table-dark">


<tr>

<th>No</th>

<th>Worker</th>

<th>Date</th>

<th>Type</th>

<th class="text-end">

Amount

</th>

<th>Note</th>

</tr>


</thead>





<tbody>



<?php


$no=1;



if($transactions->num_rows>0){



while($row=$transactions->fetch_assoc()){



?>



<tr>



<td>

<?=$no++?>

</td>





<td>

<strong>

<?=htmlspecialchars($row['name'])?>

</strong>

</td>





<td>

<?=$row['transaction_date']?>

</td>





<td>


<?php


if($row['type']=="Advance"){


?>

<span class="badge bg-danger">

<i class="fa fa-arrow-right"></i>

Advance

</span>


<?php


}elseif($row['type']=="Advance Repayment"){


?>

<span class="badge bg-warning text-dark">

<i class="fa fa-arrow-left"></i>

Advance Repayment

</span>


<?php


}elseif($row['type']=="Cash Payment"){


?>

<span class="badge bg-success">

<i class="fa fa-money"></i>

Cash Payment

</span>


<?php


}elseif($row['type']=="Saving Deposit"){


?>

<span class="badge bg-primary">

<i class="fa fa-plus"></i>

Saving Deposit

</span>


<?php


}elseif($row['type']=="Saving Withdrawal"){


?>

<span class="badge bg-info text-dark">

<i class="fa fa-minus"></i>

Saving Withdrawal

</span>


<?php


}else{


?>

<span class="badge bg-secondary">

<?=htmlspecialchars($row['type'])?>

</span>


<?php

}

?>


</td>





<td class="text-end">


<?=number_format($row['amount'],2)?>


</td>





<td>

<?=htmlspecialchars($row['note'] ?? '')?>

</td>



</tr>



<?php


}


}else{


?>



<tr>


<td colspan="6" class="text-center text-muted">


No transactions found.


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




</div>

</div>





<?php

include '../../includes/footer.php';

?>