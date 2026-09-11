<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";

include "../../includes/header.php";
include "../../includes/sidebar.php";



// Get roles

$sql = "

SELECT

r.id,

r.role_name,

r.created_at,

COUNT(rp.permission_id) AS permission_count

FROM roles r

LEFT JOIN role_permissions rp

ON r.id = rp.role_id

GROUP BY r.id

ORDER BY r.id DESC

";

$result = $conn->query($sql);

?>



<div class="content-wrapper">

<div class="container-fluid">



<!-- Header -->

<div class="d-flex justify-content-between align-items-center mb-3">


<h3>

<i class="fa fa-users-cog"></i>

Roles & Permissions

</h3>


<a href="add.php" class="btn btn-success">

<i class="fa fa-plus"></i>

Add Role

</a>


</div>





<!-- Roles Table -->

<div class="card shadow-sm">


<div class="card-header">

<h5 class="mb-0">

Roles

</h5>

</div>



<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-hover align-middle">


<thead class="table-dark">


<tr>

<th width="60">#</th>

<th>Role Name</th>

<th>Permissions</th>

<th>Created</th>

<th width="150">Action</th>

</tr>


</thead>



<tbody>



<?php

$no = 1;


if($result->num_rows > 0){


while($row = $result->fetch_assoc()){

?>


<tr>


<td>

<?= $no++ ?>

</td>



<td>

<strong>

<?= htmlspecialchars($row['role_name']) ?>

</strong>

</td>



<td>

<span class="badge bg-primary">

<?= $row['permission_count'] ?>

Permissions

</span>

</td>



<td>

<?= $row['created_at'] ?>

</td>



<td>


<div class="d-flex gap-1 justify-content-center">


<a

href="edit.php?id=<?= $row['id'] ?>"

class="btn btn-warning btn-sm"

title="Edit">


<i class="fa fa-edit"></i>


</a>



<a

href="delete.php?id=<?= $row['id'] ?>"

class="btn btn-danger btn-sm"

title="Delete"

onclick="return confirm('Delete this role?')">


<i class="fa fa-trash"></i>


</a>


</div>


</td>



</tr>


<?php


}


}else{


?>


<tr>

<td colspan="5"

class="text-center text-muted">


No roles found.


</td>

</tr>


<?php


}


?>


</tbody>


</table>


</div>


</div>


</div>




</div>

</div>



<?php

include "../../includes/footer.php";

?>