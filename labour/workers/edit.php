<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}


include "../../config/database.php";



$id = $_GET['id'];



$sql = "

SELECT *

FROM workers

WHERE id='$id'

";


$result = mysqli_query($conn,$sql);


$worker = mysqli_fetch_assoc($result);



include "../../includes/header.php";

include "../../includes/sidebar.php";


?>



<div class="container-fluid">



<div class="d-flex justify-content-between align-items-center mb-3">


<h2>

<i class="fa fa-edit"></i>

Edit Worker

</h2>



<a href="index.php"

class="btn btn-secondary">


<i class="fa fa-arrow-left"></i>

Back


</a>


</div>







<div class="card shadow-sm">


<div class="card-body">



<form action="update.php" method="POST">



<input type="hidden"

name="id"

value="<?= $worker['id'] ?>">





<div class="row">





<div class="col-md-6 mb-3">


<label class="form-label">

Name

</label>


<input type="text"

name="name"

class="form-control"

value="<?= htmlspecialchars($worker['name']) ?>"

required>


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Phone

</label>


<input type="text"

name="phone"

class="form-control"

value="<?= htmlspecialchars($worker['phone']) ?>">


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Position

</label>


<input type="text"

name="position"

class="form-control"

value="<?= htmlspecialchars($worker['position']) ?>">


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Daily Pay

</label>


<input type="number"

name="daily_rate"

class="form-control"

value="<?= $worker['daily_rate'] ?>"

required>


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Default Cash

</label>


<input type="number"

name="default_cash"

class="form-control"

value="<?= $worker['default_cash'] ?>">


<small class="text-muted">

Normal cash taken daily

</small>


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Default Saving

</label>


<input type="number"

name="default_saving"

class="form-control"

value="<?= $worker['default_saving'] ?>">


<small class="text-muted">

Normal daily saving

</small>


</div>







<div class="col-md-6 mb-3">


<label class="form-label">

Status

</label>


<select name="status"

class="form-select">



<option value="Active"

<?= $worker['status']=="Active"?'selected':'' ?>>

Active

</option>




<option value="Inactive"

<?= $worker['status']=="Inactive"?'selected':'' ?>>

Inactive

</option>



</select>


</div>






</div>








<div class="text-end">


<button type="submit"

class="btn btn-primary">


<i class="fa fa-save"></i>

Update Worker


</button>


</div>






</form>



</div>


</div>




</div>




<?php

include "../../includes/footer.php";

?>