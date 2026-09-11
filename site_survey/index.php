<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";
include "../includes/permissions.php";

requirePermission('site_survey_view');

include "../includes/header.php";
include "../includes/sidebar.php";





// GET SURVEYS

$query=mysqli_query($conn,"

SELECT

site_surveys.*,

customers.name AS customer_name,

customers.phone AS customer_phone,

customers.address AS customer_address


FROM site_surveys


LEFT JOIN customers

ON site_surveys.customer_id = customers.id


ORDER BY site_surveys.id DESC


");



?>



<div class="main-content">


<div class="container-fluid">





<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

Site Surveys

</h3>



<a href="add.php"
class="btn btn-primary">

<i class="fa fa-plus"></i>

New Survey

</a>


</div>







<!-- ================= DESKTOP TABLE ================= -->



<div class="d-none d-md-block">



<div class="card">


<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-hover">


<thead>


<tr>

<th>No</th>

<th>Customer</th>

<th>Phone</th>

<th>Address</th>

<th>Date</th>

<th>Status</th>

<th width="150">
Action
</th>


</tr>


</thead>



<tbody>



<?php


$i=1;


while($row=mysqli_fetch_assoc($query)){


?>



<tr>


<td>

<?=$i++;?>

</td>



<td>

<?=htmlspecialchars($row['customer_name']);?>

</td>



<td>

<?=htmlspecialchars($row['customer_phone']);?>

</td>




<td>

<?=htmlspecialchars($row['customer_address']);?>

</td>



<td>

<?=date('d-m-Y',strtotime($row['created_at']));?>

</td>




<td>


<?php if($row['status']=="Completed"){ ?>


<span class="badge bg-success">

Completed

</span>


<?php }else{ ?>


<span class="badge bg-warning text-dark">

<?=$row['status'];?>

</span>


<?php } ?>



</td>





<td>


<a href="view.php?id=<?=$row['id'];?>"
class="btn btn-sm btn-info"
title="View">

<i class="fa fa-eye"></i>

</a>



<a href="edit.php?id=<?=$row['id'];?>"
class="btn btn-sm btn-warning"
title="Edit">

<i class="fa fa-edit"></i>

</a>



<a href="delete.php?id=<?=$row['id'];?>"
class="btn btn-sm btn-danger"
title="Delete"
onclick="return confirm('Delete this survey?');">

<i class="fa fa-trash"></i>

</a>



</td>



</tr>



<?php } ?>



</tbody>



</table>


</div>


</div>


</div>


</div>









<!-- ================= MOBILE CARD ================= -->



<div class="d-block d-md-none">



<?php


mysqli_data_seek($query,0);


while($row=mysqli_fetch_assoc($query)){


?>



<div class="card mb-3">



<div class="card-header d-flex justify-content-between">


<b>

<?=htmlspecialchars($row['customer_name']);?>

</b>



<span>


<?php if($row['status']=="Completed"){ ?>


<span class="badge bg-success">

Completed

</span>


<?php }else{ ?>


<span class="badge bg-warning text-dark">

<?=$row['status'];?>

</span>


<?php } ?>


</span>


</div>





<div class="card-body">



<p>

<i class="fa fa-phone"></i>

<?=htmlspecialchars($row['customer_phone']);?>

</p>



<p>

<i class="fa fa-map-marker"></i>

<?=htmlspecialchars($row['customer_address']);?>

</p>



<p>

<i class="fa fa-calendar"></i>

<?=date('d-m-Y',strtotime($row['created_at']));?>

</p>




<div class="text-end">


<a href="view.php?id=<?=$row['id'];?>"
class="btn btn-sm btn-info">

<i class="fa fa-eye"></i>

</a>



<a href="edit.php?id=<?=$row['id'];?>"
class="btn btn-sm btn-warning">

<i class="fa fa-edit"></i>

</a>



<a href="delete.php?id=<?=$row['id'];?>"
class="btn btn-sm btn-danger"
onclick="return confirm('Delete this survey?');">

<i class="fa fa-trash"></i>

</a>



</div>



</div>


</div>



<?php } ?>



</div>







</div>


</div>





<?php

include "../includes/footer.php";

?>