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



if(!isset($_GET['survey_id'])){

    die("Survey ID missing");

}


$survey_id=intval($_GET['survey_id']);



?>


<div class="main-content">


<div class="container-fluid">



<div class="d-flex justify-content-between align-items-center mb-3">


<h3>
Add Measurements
</h3>


<a href="../view.php?id=<?=$survey_id;?>" 
class="btn btn-secondary">

Back

</a>


</div>






<form action="save.php" method="POST">


<input type="hidden" 
name="survey_id" 
value="<?=$survey_id;?>">





<div id="measurement_area">



<div class="card mb-3 measurement-box">


<div class="card-header d-flex justify-content-between">


<b>
Measurement #1
</b>


<button type="button" 
class="btn btn-danger btn-sm remove-btn">

Remove

</button>


</div>





<div class="card-body">



<div class="row">


<div class="col-md-6 mb-3">


<label class="form-label">

Item Name

</label>


<input 
type="text"
name="item_name[]"
class="form-control"
placeholder="Example: Living Room Window"
required>


</div>





<div class="col-md-3 mb-3">


<label class="form-label">

Width (mm)

</label>


<input 
type="number"
step="0.01"
name="width[]"
class="form-control"
required>


</div>





<div class="col-md-3 mb-3">


<label class="form-label">

Height (mm)

</label>


<input 
type="number"
step="0.01"
name="height[]"
class="form-control"
required>


</div>


</div>






<div class="row">


<div class="col-md-3 mb-3">


<label>

Quantity

</label>


<input 
type="number"
name="quantity[]"
value="1"
class="form-control">


</div>





<div class="col-md-3 mb-3">


<label>

Glass Type

</label>


<input 
type="text"
name="glass_type[]"
class="form-control"
placeholder="6mm Clear">


</div>





<div class="col-md-3 mb-3">


<label>

Frame Color

</label>


<input 
type="text"
name="frame_color[]"
class="form-control"
placeholder="Black">


</div>





<div class="col-md-3 mb-3">


<label>

Opening Type

</label>


<input 
type="text"
name="opening_type[]"
class="form-control"
placeholder="Sliding">


</div>


</div>








<div class="mb-3">


<label>

Notes

</label>


<textarea
name="notes[]"
class="form-control"
rows="3"></textarea>


</div>






</div>


</div>



</div>







<button type="button"
id="add_more"
class="btn btn-primary mb-3">

+ Add Another Measurement

</button>





<button class="btn btn-success mb-3">

Save All Measurements

</button>




</form>



</div>

</div>







<script>


let count = 1;



document.getElementById("add_more")
.addEventListener("click",function(){


count++;


let html = `


<div class="card mb-3 measurement-box">


<div class="card-header d-flex justify-content-between">


<b>
Measurement #${count}
</b>


<button type="button"
class="btn btn-danger btn-sm remove-btn">

Remove

</button>


</div>




<div class="card-body">


<div class="row">


<div class="col-md-6 mb-3">

<label>
Item Name
</label>

<input 
type="text"
name="item_name[]"
class="form-control"
required>

</div>



<div class="col-md-3 mb-3">

<label>
Width (mm)
</label>

<input 
type="number"
step="0.01"
name="width[]"
class="form-control"
required>

</div>




<div class="col-md-3 mb-3">

<label>
Height (mm)
</label>

<input 
type="number"
step="0.01"
name="height[]"
class="form-control"
required>

</div>



</div>





<div class="row">


<div class="col-md-3 mb-3">

<label>
Quantity
</label>

<input 
type="number"
name="quantity[]"
value="1"
class="form-control">

</div>



<div class="col-md-3 mb-3">

<label>
Glass Type
</label>

<input 
type="text"
name="glass_type[]"
class="form-control">

</div>




<div class="col-md-3 mb-3">

<label>
Frame Color
</label>

<input 
type="text"
name="frame_color[]"
class="form-control">

</div>




<div class="col-md-3 mb-3">

<label>
Opening Type
</label>

<input 
type="text"
name="opening_type[]"
class="form-control">

</div>



</div>





<div class="mb-3">


<label>
Notes
</label>


<textarea
name="notes[]"
class="form-control"
rows="3"></textarea>


</div>




</div>


</div>


`;



document.getElementById("measurement_area")
.insertAdjacentHTML("beforeend",html);



});





document.addEventListener("click",function(e){


if(e.target.classList.contains("remove-btn")){


let boxes=document.querySelectorAll(".measurement-box");


if(boxes.length > 1){

e.target.closest(".measurement-box").remove();

}


}


});



</script>






<?php

include "../../includes/footer.php";

?>