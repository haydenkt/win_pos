<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}


include "../../config/database.php";


include "../../includes/header.php";

include "../../includes/sidebar.php";

?>


<div class="container-fluid">



<div class="d-flex justify-content-between align-items-center mb-3">


<h2>

<i class="fa fa-user-plus"></i>

Add Worker

</h2>



<a href="index.php"

class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>






<div class="card shadow-sm">


<div class="card-body">



<form action="save.php" method="POST">



<div class="row">



<div class="col-md-6 mb-3">


<label class="form-label">

Name

</label>


<input type="text"

name="name"

class="form-control"

required>


</div>





<div class="col-md-6 mb-3">


<label class="form-label">

Phone

</label>


<input type="text"

name="phone"

class="form-control">


</div>






<div class="col-md-6 mb-3">


<label class="form-label">

Position

</label>


<input type="text"

name="position"

class="form-control"

placeholder="Example: Aluminium Worker">


</div>






<div class="col-md-6 mb-3">


<label class="form-label">

Daily Pay

</label>


<input type="number"

name="daily_rate"

class="form-control"

value="0"

required>


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Default Cash

</label>


<input type="number"

name="default_cash"

class="form-control"

value="0">


<small class="text-muted">

Daily money worker takes

</small>


</div>








<div class="col-md-6 mb-3">


<label class="form-label">

Default Saving

</label>


<input type="number"

name="default_saving"

class="form-control"

value="0">


<small class="text-muted">

Daily savings amount

</small>


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Status

</label>


<select name="status"

class="form-select">


<option value="Active">

Active

</option>


<option value="Inactive">

Inactive

</option>


</select>


</div>






</div>







<div class="text-end">


<button type="submit"

class="btn btn-success">


<i class="fa fa-save"></i>

Save Worker


</button>


</div>




</form>



</div>


</div>



</div>




<?php

include "../../includes/footer.php";

?>
