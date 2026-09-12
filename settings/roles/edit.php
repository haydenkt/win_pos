<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";

require_once __DIR__ . '/permission_catalog.php';
ensureV2PermissionCatalog($conn);



// ========================================
// GET ROLE ID
// ========================================

$id = intval($_GET['id'] ?? 0);


if($id <= 0){

    header("Location: index.php");
    exit();

}





// ========================================
// GET ROLE
// ========================================

$sql = "

SELECT *

FROM roles

WHERE id=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();

$role = $result->fetch_assoc();



if(!$role){

    die("Role not found.");

}





// ========================================
// GET ALL PERMISSIONS
// ========================================

$permission_sql = "

SELECT *

FROM permissions

WHERE permission_name NOT LIKE 'materials_%'
AND permission_name NOT LIKE 'material_usage_%'
AND permission_name NOT LIKE 'production_%'

ORDER BY id ASC

";


$permissions = $conn->query($permission_sql);





// ========================================
// GET ROLE PERMISSIONS
// ========================================

$role_permission_sql = "

SELECT permission_id

FROM role_permissions

WHERE role_id=?

";


$stmt = $conn->prepare($role_permission_sql);

$stmt->bind_param("i",$id);

$stmt->execute();

$role_permission_result = $stmt->get_result();





$selected_permissions = [];



while($rp = $role_permission_result->fetch_assoc()){

    $selected_permissions[] = intval($rp['permission_id']);

}





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

<i class="fa fa-user-shield"></i>

Edit Role

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

Edit Role Information

</h5>


</div>



<div class="card-body">


<form action="update.php" method="POST">



<input

type="hidden"

name="id"

value="<?= $role['id'] ?>">





<!-- Role Name -->


<div class="mb-4">


<label class="form-label fw-bold">

Role Name

</label>


<input

type="text"

name="role_name"

class="form-control"

value="<?= htmlspecialchars($role['role_name']) ?>"

required>


</div>







<!-- ========================================
     PERMISSIONS
======================================== -->


<div class="mb-3">


<div class="d-flex justify-content-between align-items-center mb-3">


<h5 class="mb-0">

Permissions

</h5>


<div>


<button

type="button"

class="btn btn-sm btn-outline-primary"

onclick="selectAll()">


<i class="fa fa-check-square"></i>

Select All

</button>



<button

type="button"

class="btn btn-sm btn-outline-secondary"

onclick="clearAll()">


<i class="fa fa-square"></i>

Clear All

</button>


</div>


</div>





<div class="row">


<?php


while($permission = $permissions->fetch_assoc()){



$permission_name = $permission['permission_name'];



// Friendly name

$display_name = v2PermissionDisplayName($permission_name);



$checked = in_array(

    intval($permission['id']),

    $selected_permissions

);

?>


<div class="col-md-6 col-lg-4 mb-3">


<div class="form-check border rounded p-3 h-100">


<input

class="form-check-input permission-check"

type="checkbox"

name="permissions[]"

value="<?= $permission['id'] ?>"

id="permission<?= $permission['id'] ?>"

<?= $checked ? 'checked' : '' ?>


>


<label

class="form-check-label"

for="permission<?= $permission['id'] ?>">


<strong>

<?= htmlspecialchars($display_name) ?>

</strong>


<br>


<small class="text-muted">

<?= htmlspecialchars($permission['description'] ?? '') ?>

</small>


</label>


</div>


</div>


<?php

}

?>


</div>


</div>







<!-- Buttons -->


<div class="text-end mt-4">


<a

href="index.php"

class="btn btn-secondary">


Cancel

</a>



<button

type="submit"

class="btn btn-primary">


<i class="fa fa-save"></i>

Update Role

</button>


</div>



</form>


</div>


</div>




</div>

</div>






<script>

function selectAll(){

    document

    .querySelectorAll('.permission-check')

    .forEach(function(checkbox){

        checkbox.checked = true;

    });

}


function clearAll(){

    document

    .querySelectorAll('.permission-check')

    .forEach(function(checkbox){

        checkbox.checked = false;

    });

}

</script>



<?php

include "../../includes/footer.php";

?>
