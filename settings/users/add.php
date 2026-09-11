<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";



// Get roles

$roles = $conn->query("

SELECT id, role_name

FROM roles

ORDER BY role_name ASC

");



include "../../includes/header.php";

include "../../includes/sidebar.php";

?>



<div class="content-wrapper">

<div class="container-fluid">



<!-- Header -->

<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

<i class="fa fa-user-plus"></i>

Add User

</h3>


<a href="index.php" class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>





<!-- User Form -->

<div class="card shadow-sm">


<div class="card-header">

<h5 class="mb-0">

User Information

</h5>

</div>



<div class="card-body">


<form action="save.php" method="POST">



<div class="row">



<!-- Username -->

<div class="col-md-6 mb-3">


<label class="form-label">

Username

</label>


<input

type="text"

name="username"

class="form-control"

required

autocomplete="off">


</div>





<!-- Full Name -->

<div class="col-md-6 mb-3">


<label class="form-label">

Full Name

</label>


<input

type="text"

name="full_name"

class="form-control">


</div>





<!-- Email -->

<div class="col-md-6 mb-3">


<label class="form-label">

Email

</label>


<input

type="email"

name="email"

class="form-control">


</div>





<!-- Phone -->

<div class="col-md-6 mb-3">


<label class="form-label">

Phone

</label>


<input

type="text"

name="phone"

class="form-control">


</div>





<!-- Password -->

<div class="col-md-6 mb-3">


<label class="form-label">

Password

</label>


<input

type="password"

name="password"

class="form-control"

required

autocomplete="new-password">


</div>





<!-- Role -->

<div class="col-md-6 mb-3">


<label class="form-label fw-bold">

Role

</label>


<select

name="role_id"

class="form-select"

required>


<option value="">

Select Role

</option>


<?php


if($roles && $roles->num_rows > 0){


while($role = $roles->fetch_assoc()){


?>


<option value="<?= $role['id'] ?>">

<?= htmlspecialchars($role['role_name']) ?>

</option>


<?php


}


}else{


?>


<option value="">

No roles available

</option>


<?php


}


?>


</select>


</div>





<!-- Status -->

<div class="col-md-6 mb-3">


<label class="form-label">

Status

</label>


<select

name="status"

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







<!-- Buttons -->

<div class="text-end mt-3">


<a

href="index.php"

class="btn btn-secondary">


Cancel

</a>



<button

type="submit"

class="btn btn-success">


<i class="fa fa-save"></i>

Save User


</button>


</div>



</form>


</div>


</div>




</div>

</div>



<?php

include "../../includes/footer.php";

?>