<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";



// ========================================
// GET USER ID
// ========================================

$id = intval($_GET['id'] ?? 0);


if($id <= 0){

    header("Location: index.php");
    exit();

}





// ========================================
// GET USER
// ========================================

$sql = "

SELECT *

FROM users

WHERE id=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();



if(!$user){

    die("User not found.");

}





// ========================================
// GET ROLES
// ========================================

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



<!-- ========================================
     HEADER
======================================== -->


<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

<i class="fa fa-user-edit"></i>

Edit User

</h3>


<a href="index.php" class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>





<!-- ========================================
     FORM
======================================== -->


<div class="card shadow-sm">


<div class="card-header">

<h5 class="mb-0">

User Information

</h5>

</div>



<div class="card-body">


<form action="update.php" method="POST">



<input

type="hidden"

name="id"

value="<?= $user['id'] ?>">



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

value="<?= htmlspecialchars($user['username']) ?>"

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

class="form-control"

value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">


</div>





<!-- Email -->

<div class="col-md-6 mb-3">


<label class="form-label">

Email

</label>


<input

type="email"

name="email"

class="form-control"

value="<?= htmlspecialchars($user['email'] ?? '') ?>">


</div>





<!-- Phone -->

<div class="col-md-6 mb-3">


<label class="form-label">

Phone

</label>


<input

type="text"

name="phone"

class="form-control"

value="<?= htmlspecialchars($user['phone'] ?? '') ?>">


</div>





<!-- Password -->

<div class="col-md-6 mb-3">


<label class="form-label">

New Password

</label>


<input

type="password"

name="password"

class="form-control"

autocomplete="new-password"

placeholder="Leave blank to keep current password">


<small class="text-muted">

Leave blank if you don't want to change the password.

</small>


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


$selected = (

intval($user['role_id']) ==

intval($role['id'])

)

? 'selected'

: '';

?>


<option

value="<?= $role['id'] ?>"

<?= $selected ?>>

<?= htmlspecialchars($role['role_name']) ?>

</option>


<?php


}


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


<option

value="Active"

<?= $user['status'] == 'Active' ? 'selected' : '' ?>>

Active

</option>


<option

value="Inactive"

<?= $user['status'] == 'Inactive' ? 'selected' : '' ?>>

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

class="btn btn-primary">


<i class="fa fa-save"></i>

Update User


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