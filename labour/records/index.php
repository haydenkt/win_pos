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



$record_date = $_GET['date'] ?? date('Y-m-d');



// Total workers

$count_sql = "

SELECT COUNT(*) AS total

FROM workers

WHERE status='Active'

";


$count_result = $conn->query($count_sql);

$total_workers = $count_result->fetch_assoc()['total'];





// Summary

$summary_sql = "

SELECT

SUM(status='Present') AS present,

SUM(status='Absent') AS absent,

SUM(earned_amount) AS total_amount

FROM labour_records

WHERE record_date='$record_date'

";


$summary_result = $conn->query($summary_sql);

$summary = $summary_result->fetch_assoc();



$present_count = $summary['present'] ?? 0;

$absent_count = $summary['absent'] ?? 0;

$total_labour_cost = $summary['total_amount'] ?? 0;





// Workers

$workers = $conn->query("

SELECT *

FROM workers

WHERE status='Active'

ORDER BY name ASC

");


?>



<div class="content-wrapper">


<div class="container-fluid">



<h3 class="mb-3">

<i class="fa fa-calendar-check"></i>

Daily Labour Settlement

</h3>







<!-- Summary -->

<div class="row mb-3">



<div class="col-md-3">

<div class="card shadow-sm">

<div class="card-body">

<h6>စုစုပေါင်းလူဦးရေ</h6>

<h3><?=$total_workers?></h3>


</div>

</div>

</div>





<div class="col-md-3">

<div class="card shadow-sm">

<div class="card-body">

<h6>အလုပ်လာ</h6>

<h3><?=$present_count?></h3>


</div>

</div>

</div>






<div class="col-md-3">

<div class="card shadow-sm">

<div class="card-body">

<h6>ပျက်ကွက်</h6>

<h3><?=$absent_count?></h3>


</div>

</div>

</div>






<div class="col-md-3">

<div class="card shadow-sm">

<div class="card-body">

<h6>ကျသင့်ငွေ</h6>

<h3>

<?=number_format($total_labour_cost,2)?>

</h3>


</div>

</div>

</div>



</div>










<!-- Date -->

<div class="card shadow-sm mb-3">


<div class="card-body">


<form method="GET">


<div class="row">


<div class="col-md-4">


<label>

Date

</label>


<input type="date"

name="date"

class="form-control"

value="<?=$record_date?>">


</div>




<div class="col-md-2 mt-4">


<button class="btn btn-primary">


<i class="fa fa-search"></i>

Load


</button>


</div>



</div>


</form>


</div>


</div>









<!-- Records -->

<form action="save.php" method="POST">


<input type="hidden"

name="record_date"

value="<?=$record_date?>">





<div class="card shadow-sm">


<div class="card-header">


<h5>

Worker Payment

</h5>


</div>





<div class="card-body table-responsive">


<table class="table table-bordered table-hover">


<thead class="table-dark">


<tr>

<th>#</th>

<th>Name</th>

<th>Position</th>

<th>Daily Pay</th>

<th>Status</th>

<th>Days</th>

<th>Earned</th>

<th>Cash</th>

<th>Saving</th>

</tr>


</thead>





<tbody>



<?php


$no=1;



while($worker=$workers->fetch_assoc()){



$old_sql="

SELECT *

FROM labour_records

WHERE worker_id='{$worker['id']}'

AND record_date='$record_date'

";



$old_result=$conn->query($old_sql);


$old=$old_result->fetch_assoc();



$status=$old['status'] ?? 'Present';


$days=$old['days'] ?? 1;


$earned_amount=$old['earned_amount'] ?? $worker['daily_rate'];



$cash = $worker['default_cash'];

$saving = $worker['default_saving'];



?>



<tr>



<td>

<?=$no++?>

</td>







<td>


<strong>

<?=htmlspecialchars($worker['name'])?>

</strong>



<input type="hidden"

name="worker_id[]"

value="<?=$worker['id']?>">


</td>







<td>

<?=htmlspecialchars($worker['position'])?>

</td>








<td>


<?=number_format($worker['daily_rate'],2)?>


<input type="hidden"

class="rate"

value="<?=$worker['daily_rate']?>">


</td>







<td>


<select name="status[]"

class="form-select"

onchange="calculate(this)">



<option value="Present"

<?=$status=='Present'?'selected':''?>>

Present

</option>




<option value="Half Day"

<?=$status=='Half Day'?'selected':''?>>

Half Day

</option>





<option value="Absent"

<?=$status=='Absent'?'selected':''?>>

Absent

</option>


</select>


</td>








<td>


<input type="text"

name="days[]"

class="form-control days"

value="<?=$days?>"

readonly>


</td>








<td>


<input type="text"

name="earned_amount[]"

class="form-control earned"

value="<?=$earned_amount?>"

readonly>


</td>









<td>


<input type="number"

name="cash[]"

class="form-control cash"

value="<?=$cash?>"

onkeyup="calculateSaving(this)">


</td>








<td>


<input type="text"

name="saving[]"

class="form-control saving"

data-value="<?=$saving?>"

value="<?=number_format($saving,2,'.','')?>"

readonly>


</td>





</tr>




<?php } ?>



</tbody>



</table>



</div>







<div class="card-footer text-end">


<button class="btn btn-success">


<i class="fa fa-save"></i>

Save Settlement


</button>



</div>



</div>




</form>




</div>

</div>









<script>


function calculate(select){

    let row = select.closest("tr");


    let rate = parseFloat(
        row.querySelector(".rate").value
    ) || 0;


    let days = 1;


    if(select.value === "Half Day"){

        days = 0.5;

    }


    if(select.value === "Absent"){

        days = 0;

    }



    row.querySelector(".days").value = days;



    let earned = rate * days;



    row.querySelector(".earned").value =
        earned.toFixed(2);



    /*
       IMPORTANT

       Cash and Saving are not changed
       when attendance changes.

       Saving belongs to worker account.
    */


}



function calculateSaving(input){

    let row = input.closest("tr");


    let earned =
        parseFloat(
            row.querySelector(".earned").value
        ) || 0;


    let cash =
        parseFloat(input.value) || 0;



    let saving =
        parseFloat(
            row.querySelector(".saving").dataset.value
        ) || 0;



    /*
       Do not make saving negative

       Salary balance will be handled
       in transaction page.
    */


    if(cash > earned){

        input.value = earned.toFixed(2);

    }


}



</script>






<?php

include '../../includes/footer.php';

?>