<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include_once "../config/database.php";
include "../includes/permissions.php";

requirePermission('customers_view');

include "../includes/header.php";
include "../includes/sidebar.php";



$search = "";



if(isset($_GET['search'])){

    $search = mysqli_real_escape_string($conn,$_GET['search']);

}



$sql = "

SELECT *

FROM customers

WHERE

name LIKE '%$search%'

OR phone LIKE '%$search%'

OR address LIKE '%$search%'


ORDER BY id DESC

";



$result = mysqli_query($conn,$sql);



?>



<h2>

<i class="fa fa-users"></i>

Customers

</h2>





<div class="row mt-3">



<div class="col-md-6">


<form method="GET">


<div class="input-group">


<input type="text"

name="search"

class="form-control"

placeholder="Search customer name, phone, address..."

value="<?=$search;?>">



<button class="btn btn-primary">


<i class="fa fa-search"></i>

Search


</button>



</div>


</form>


</div>





<div class="col-md-6 text-end">


<a href="add.php"

class="btn btn-success">


<i class="fa fa-plus"></i>

Add Customer


</a>


</div>


</div>







<div class="card mt-3">


<div class="card-body">



<table class="table table-bordered table-striped">


<thead>


<tr>


<th width="50">
#
</th>


<th>
Name
</th>


<th>
Phone
</th>


<th>
Address
</th>


<th width="150">
Action
</th>


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

<?=$row['name'];?>

</td>




<td>

<?=$row['phone'];?>

</td>




<td>

<?=$row['address'];?>

</td>





<td>



<a href="view.php?id=<?=$row['id'];?>"

class="btn btn-info btn-sm">

<i class="fa fa-eye"></i>

</a>



<a href="edit.php?id=<?=$row['id'];?>"

class="btn btn-warning btn-sm">

<i class="fa fa-edit"></i>

</a>




<a href="delete.php?id=<?=$row['id'];?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Delete this customer?');">

<i class="fa fa-trash"></i>

</a>



</td>


</tr>



<?php



}



}else{



?>


<tr>

<td colspan="5" class="text-center">

No customer found

</td>

</tr>



<?php } ?>



</tbody>


</table>



</div>


</div>





<?php

include "../includes/footer.php";

?>