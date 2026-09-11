<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}


include "../../config/database.php";
include "../../includes/permissions.php";

requirePermission('labour_view');

$sql = "

SELECT *

FROM workers

ORDER BY id DESC

";


$result = mysqli_query($conn,$sql);



include "../../includes/header.php";

include "../../includes/sidebar.php";

?>


<div class="container-fluid">



<div class="d-flex justify-content-between align-items-center mb-3">


<h2>

<i class="fa fa-users"></i>

Workers

</h2>




<a href="/labour/workers/add.php"

class="btn btn-success">


<i class="fa fa-plus"></i>

Add Worker


</a>



</div>







<div class="card shadow-sm">


<div class="card-body">



<div class="table-responsive">



<table class="table table-bordered table-striped table-hover">


<thead class="table-dark">


<tr>


<th>#</th>

<th>Name</th>

<th>Phone</th>

<th>Position</th>

<th>Daily Pay</th>

<th>Cash</th>

<th>Saving</th>

<th>Status</th>

<th width="140">Action</th>


</tr>


</thead>





<tbody>



<?php


$i=1;



if(mysqli_num_rows($result)>0){


while($row=mysqli_fetch_assoc($result)){


?>



<tr>



<td>

<?=$i++;?>

</td>




<td>

<strong>

<?=htmlspecialchars($row['name']);?>

</strong>

</td>




<td>

<?=htmlspecialchars($row['phone']);?>

</td>




<td>

<?=htmlspecialchars($row['position']);?>

</td>




<td class="text-end">

<?=number_format($row['daily_rate']);?>

</td>





<td class="text-end">

<?=number_format($row['default_cash']);?>

</td>





<td class="text-end">

<?=number_format($row['default_saving']);?>

</td>





<td>



<?php

if($row['status']=="Active"){

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







<td>

<div class="d-flex gap-1 justify-content-center">


<a href="view.php?id=<?=$row['id']?>"

class="btn btn-info btn-sm"

title="View">

<i class="fa fa-eye"></i>

</a>



<a href="edit.php?id=<?=$row['id']?>"

class="btn btn-warning btn-sm"

title="Edit">

<i class="fa fa-edit"></i>

</a>



<a href="delete.php?id=<?=$row['id']?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Delete this worker?')"

title="Delete">

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

<td colspan="9" class="text-center">


No workers found


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






<?php

include "../../includes/footer.php";

?>