<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";



// ========================================
// GET USERS + ROLE
// ========================================

$sql = "

SELECT

    users.*,

    roles.role_name

FROM users

LEFT JOIN roles

    ON users.role_id = roles.id

ORDER BY users.id DESC

";


$result = $conn->query($sql);



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

<i class="fa fa-users"></i>

Users

</h3>


<a href="add.php" class="btn btn-success">

<i class="fa fa-plus"></i>

Add User

</a>


</div>





<!-- ========================================
     USERS TABLE
======================================== -->


<div class="card shadow-sm">


<div class="card-header">


<h5 class="mb-0">

System Users

</h5>


</div>



<div class="card-body">


<div class="table-responsive">


<table class="table table-bordered table-hover align-middle">


<thead class="table-dark">


<tr>


<th width="60">

#

</th>


<th>

Username

</th>


<th>

Full Name

</th>


<th>

Email

</th>


<th>

Phone

</th>


<th>

Role

</th>


<th>

Status

</th>


<th width="130">

Action

</th>


</tr>


</thead>



<tbody>



<?php


$no = 1;


if($result->num_rows > 0){


while($row = $result->fetch_assoc()){


?>



<tr>


<!-- Number -->

<td>

<?= $no++ ?>

</td>





<!-- Username -->

<td>

<strong>

<?= htmlspecialchars($row['username']) ?>

</strong>

</td>





<!-- Full Name -->

<td>

<?= htmlspecialchars($row['full_name'] ?? '') ?>

</td>





<!-- Email -->

<td>

<?= htmlspecialchars($row['email'] ?? '') ?>

</td>





<!-- Phone -->

<td>

<?= htmlspecialchars($row['phone'] ?? '') ?>

</td>





<!-- Role -->

<td>


<?php


if(!empty($row['role_name'])){


?>


<span class="badge bg-primary">

<?= htmlspecialchars($row['role_name']) ?>

</span>


<?php


}else{


?>


<span class="badge bg-secondary">

No Role

</span>


<?php


}


?>


</td>





<!-- Status -->

<td>


<?php


if($row['status'] === 'Active'){


?>


<span class="badge bg-success">

Active

</span>


<?php


}else{


?>


<span class="badge bg-secondary">

Inactive

</span>


<?php


}


?>


</td>





<!-- Actions -->

<td>


<div class="d-flex gap-1 justify-content-center">


<a

href="edit.php?id=<?= $row['id'] ?>"

class="btn btn-warning btn-sm"

title="Edit">


<i class="fa fa-edit"></i>


</a>


</div>


</td>



</tr>



<?php


}


}else{


?>


<tr>


<td

colspan="8"

class="text-center text-muted">


No users found.


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