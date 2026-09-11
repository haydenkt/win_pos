<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";



// Get permissions

$sql = "

SELECT *

FROM permissions

WHERE permission_name NOT LIKE 'materials_%'
AND permission_name NOT LIKE 'material_usage_%'
AND permission_name NOT LIKE 'production_%'

ORDER BY id ASC

";

$result = $conn->query($sql);



include "../../includes/header.php";

include "../../includes/sidebar.php";

?>



<div class="content-wrapper">

<div class="container-fluid">



<!-- Header -->

<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

<i class="fa fa-user-shield"></i>

Add Role

</h3>


<a href="index.php" class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>





<!-- Form -->

<div class="card shadow-sm">


<div class="card-header">

<h5 class="mb-0">

Role Information

</h5>

</div>



<div class="card-body">


<form action="save.php" method="POST">



<!-- Role Name -->

<div class="mb-4">


<label class="form-label fw-bold">

Role Name

</label>


<input

type="text"

name="role_name"

class="form-control"

placeholder="Example: Sales Person"

required>


</div>







<!-- Permissions -->

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

while($permission = $result->fetch_assoc()){


$permission_name = $permission['permission_name'];



// Friendly display name

$display_name = match($permission_name){

    'dashboard_view' => 'View Dashboard',

    'customers_view' => 'View Customers',

    'customers_manage' => 'Manage Customers',

    'invoices_view' => 'View Invoices',

    'invoices_manage' => 'Manage Invoices',

    'payments_view' => 'View Payments',

    'payments_manage' => 'Manage Payments',

    'labour_view' => 'View Labour',

    'labour_manage' => 'Manage Labour',

    'site_survey_view' => 'View Site Survey',

    'site_survey_manage' => 'Manage Site Survey',

    'reports_view' => 'View Reports',

    'settings_manage' => 'Manage Settings',

    'users_manage' => 'Manage Users & Roles',

    default => ucwords(str_replace('_',' ',$permission_name))

};


?>


<div class="col-md-6 col-lg-4 mb-3">


<div class="form-check border rounded p-3 h-100">


<input

class="form-check-input permission-check"

type="checkbox"

name="permissions[]"

value="<?= $permission['id'] ?>"

id="permission<?= $permission['id'] ?>">


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

class="btn btn-success">


<i class="fa fa-save"></i>

Save Role

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
