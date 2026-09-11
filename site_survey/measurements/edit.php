<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../../index.php");
    exit();

}


include "../../config/database.php";

include "../../includes/header.php";
include "../../includes/sidebar.php";




if(!isset($_GET['id'])){

    die("Measurement ID missing");

}


$id=intval($_GET['id']);





// GET MEASUREMENT

$query=mysqli_query($conn,"

SELECT *

FROM survey_measurements

WHERE id='$id'

");


$measurement=mysqli_fetch_assoc($query);



if(!$measurement){

    die("Measurement not found");

}




?>



<div class="main-content">


<div class="container-fluid">



<div class="d-flex justify-content-between align-items-center mb-3">


<h3>
Edit Measurement
</h3>



<a href="../view.php?id=<?=$measurement['survey_id'];?>"
class="btn btn-secondary">

Back

</a>


</div>






<div class="card">


<div class="card-body">





<form action="update.php" method="POST">



<input type="hidden"
name="id"
value="<?=$measurement['id'];?>">


<input type="hidden"
name="survey_id"
value="<?=$measurement['survey_id'];?>">





<div class="row">



<div class="col-md-6 mb-3">


<label class="form-label">

Item Name

</label>


<input 
type="text"
name="item_name"
class="form-control"
value="<?=htmlspecialchars($measurement['item_name']);?>"
required>


</div>





<div class="col-md-3 mb-3">


<label>

Width (mm)

</label>


<input 
type="number"
step="0.01"
name="width"
class="form-control"
value="<?=$measurement['width'];?>"
required>


</div>





<div class="col-md-3 mb-3">


<label>

Height (mm)

</label>


<input 
type="number"
step="0.01"
name="height"
class="form-control"
value="<?=$measurement['height'];?>"
required>


</div>



</div>









<div class="row">



<div class="col-md-4 mb-3">


<label>

Glass Type

</label>


<input 
type="text"
name="glass_type"
class="form-control"
value="<?=htmlspecialchars($measurement['glass_type']);?>">


</div>





<div class="col-md-4 mb-3">


<label>

Frame Color

</label>


<input 
type="text"
name="frame_color"
class="form-control"
value="<?=htmlspecialchars($measurement['frame_color']);?>">


</div>





<div class="col-md-4 mb-3">


<label>

Opening Type

</label>


<input 
type="text"
name="opening_type"
class="form-control"
value="<?=htmlspecialchars($measurement['opening_type']);?>">


</div>



</div>








<div class="mb-3">


<label>

Notes

</label>


<textarea
name="notes"
class="form-control"
rows="4"><?=htmlspecialchars($measurement['notes']);?></textarea>


</div>







<button class="btn btn-success">

<i class="fa fa-save"></i>

Update Measurement

</button>



</form>




</div>


</div>





</div>


</div>







<?php

include "../../includes/footer.php";

?>