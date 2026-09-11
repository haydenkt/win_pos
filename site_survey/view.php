<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";

include "../includes/header.php";
include "../includes/sidebar.php";



if(!isset($_GET['id'])){

    die("Survey ID missing");

}


$id=intval($_GET['id']);




// GET SURVEY


$query=mysqli_query($conn,"

SELECT

site_surveys.*,

customers.name AS customer_name,

customers.phone AS customer_phone,

customers.address AS customer_address


FROM site_surveys


LEFT JOIN customers

ON site_surveys.customer_id = customers.id


WHERE site_surveys.id='$id'


");



$survey=mysqli_fetch_assoc($query);



if(!$survey){

    die("Survey not found");

}





// GET MEASUREMENTS


$measurements=mysqli_query($conn,"

SELECT *

FROM survey_measurements

WHERE survey_id='$id'

ORDER BY id ASC

");



?>




<div class="main-content">


<div class="container-fluid">






<!-- HEADER -->


<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

<i class="fa fa-map-marker"></i>

Site Survey

</h3>



<div>


<a href="index.php"
class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

</a>



<a href="edit.php?id=<?=$id;?>"
class="btn btn-warning">

<i class="fa fa-edit"></i>

</a>



<a href="print.php?id=<?=$id;?>"
class="btn btn-primary">

<i class="fa fa-print"></i>

</a>


</div>



</div>









<!-- CUSTOMER CARD -->


<div class="card mb-3">


<div class="card-header">

<i class="fa fa-user"></i>

Customer Information

</div>



<div class="card-body">


<div class="row">


<div class="col-md-4 mb-3">


<b>Name</b>

<br>

<?=htmlspecialchars($survey['customer_name']);?>


</div>




<div class="col-md-4 mb-3">


<b>Phone</b>

<br>

<?=htmlspecialchars($survey['customer_phone']);?>


</div>




<div class="col-md-4 mb-3">


<b>Customer Address</b>

<br>

<?=htmlspecialchars($survey['customer_address']);?>


</div>



</div>


</div>


</div>









<!-- SURVEY INFO -->


<div class="card mb-3">


<div class="card-header">

<i class="fa fa-info-circle"></i>

Survey Information

</div>



<div class="card-body">


<div class="row">



<div class="col-md-4 mb-3">


<b>Survey Date</b>

<br>

<?=date('d-m-Y',strtotime($survey['survey_date']));?>


</div>





<div class="col-md-4 mb-3">


<b>Status</b>

<br>


<?php if($survey['status']=="Completed"){ ?>


<span class="badge bg-success">

Completed

</span>


<?php }elseif($survey['status']=="Approved"){ ?>


<span class="badge bg-primary">

Approved

</span>


<?php }else{ ?>


<span class="badge bg-warning text-dark">

<?=$survey['status'];?>

</span>


<?php } ?>



</div>





<div class="col-md-4 mb-3">


<b>Created</b>

<br>

<?=date('d-m-Y H:i',strtotime($survey['created_at']));?>


</div>



</div>


</div>


</div>









<!-- SITE ADDRESS -->


<div class="card mb-3">


<div class="card-header">

<i class="fa fa-home"></i>

Site Address

</div>



<div class="card-body">


<?=nl2br(htmlspecialchars($survey['address']));?>


</div>


</div>








<!-- NOTES -->


<div class="card mb-3">


<div class="card-header">

<i class="fa fa-sticky-note"></i>

Notes

</div>



<div class="card-body">


<?=nl2br(htmlspecialchars($survey['notes'] ?? ''));?>


</div>


</div>









<!-- MEASUREMENTS -->


<div class="card mb-3">


<div class="card-header d-flex justify-content-between">


<span>

<i class="fa fa-ruler"></i>

Measurements

</span>



<a href="measurements/add.php?survey_id=<?=$id;?>"
class="btn btn-sm btn-primary">


<i class="fa fa-plus"></i>

Add


</a>



</div>





<div class="card-body">



<?php if(mysqli_num_rows($measurements)>0){ ?>



<div class="row">



<?php


$i=1;


while($m=mysqli_fetch_assoc($measurements)){


?>



<div class="col-md-6 mb-3">


<div class="card h-100">



<div class="card-header d-flex justify-content-between">


<b>

<?=$i++;?>.

<?=htmlspecialchars($m['item_name']);?>

</b>




<div>


<a href="measurements/edit.php?id=<?=$m['id'];?>"
class="btn btn-sm btn-warning">


<i class="fa fa-edit"></i>


</a>




<a href="measurements/delete.php?id=<?=$m['id'];?>&survey_id=<?=$id;?>"
class="btn btn-sm btn-danger"
onclick="return confirm('Delete this measurement?');">


<i class="fa fa-trash"></i>


</a>


</div>



</div>






<div class="card-body">



<p>

<b>Size:</b>

<?=number_format($m['width']);?>

×

<?=number_format($m['height']);?>

mm


</p>



<p>

<b>Glass:</b>

<?=htmlspecialchars($m['glass_type']);?>

</p>



<p>

<b>Frame:</b>

<?=htmlspecialchars($m['frame_color']);?>

</p>



<p>

<b>Opening:</b>

<?=htmlspecialchars($m['opening_type']);?>

</p>



<p>

<b>Notes:</b>

<?=htmlspecialchars($m['notes']);?>

</p>



</div>



</div>



</div>



<?php } ?>



</div>



<?php }else{ ?>


<div class="text-center text-muted">

No measurements added yet

</div>


<?php } ?>



</div>


</div>









<!-- PHOTO PLACE HOLDER -->


<div class="card mb-3">


<div class="card-header">

<i class="fa fa-image"></i>

Photos

</div>


<div class="card-body text-center text-muted">


Photo module will be added later


</div>


</div>






</div>


</div>





<?php

include "../includes/footer.php";

?>