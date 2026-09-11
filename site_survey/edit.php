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

SELECT *

FROM site_surveys

WHERE id='$id'

");


$survey=mysqli_fetch_assoc($query);



if(!$survey){

    die("Survey not found");

}






// GET CUSTOMERS

$customers=mysqli_query($conn,"

SELECT *

FROM customers

ORDER BY name ASC

");



?>



<div class="main-content">


<div class="container-fluid">





<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

<i class="fa fa-edit"></i>

Edit Site Survey

</h3>



<a href="view.php?id=<?=$id;?>"
class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>








<div class="card">


<div class="card-body">



<form action="update.php" method="POST">



<input type="hidden"
name="id"
value="<?=$survey['id'];?>">






<div class="row">



<div class="col-md-6 mb-3">


<label class="form-label">

Customer

</label>


<select name="customer_id"
class="form-control"
required>



<?php while($c=mysqli_fetch_assoc($customers)){ ?>


<option value="<?=$c['id'];?>"

<?php

if($c['id']==$survey['customer_id']){

echo "selected";

}

?>

>


<?=htmlspecialchars($c['name'] ?? '');?>


-

<?=htmlspecialchars($c['phone'] ?? '');?>


</option>



<?php } ?>


</select>


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Survey Date

</label>


<input

type="date"

name="survey_date"

class="form-control"

value="<?=htmlspecialchars($survey['survey_date'] ?? '');?>"

required>


</div>



</div>









<div class="mb-3">


<label class="form-label">

Site Address

</label>


<textarea

name="address"

class="form-control"

rows="3"

placeholder="Installation address"><?=htmlspecialchars($survey['address'] ?? '');?></textarea>


</div>









<div class="mb-3">


<label class="form-label">

Status

</label>


<select name="status"
class="form-control">



<option value="Pending"

<?=($survey['status']=="Pending")?'selected':'';?>>

Pending

</option>




<option value="Approved"

<?=($survey['status']=="Approved")?'selected':'';?>>

Approved

</option>





<option value="Completed"

<?=($survey['status']=="Completed")?'selected':'';?>>

Completed

</option>





<option value="Cancelled"

<?=($survey['status']=="Cancelled")?'selected':'';?>>

Cancelled

</option>



</select>


</div>









<div class="mb-3">


<label class="form-label">

Notes

</label>


<textarea

name="notes"

class="form-control"

rows="4"

placeholder="Survey notes"><?=htmlspecialchars($survey['notes'] ?? '');?></textarea>


</div>







<button class="btn btn-success">

<i class="fa fa-save"></i>

Update Survey

</button>



</form>



</div>


</div>






</div>


</div>







<?php

include "../includes/footer.php";

?>