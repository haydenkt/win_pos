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

<style>
.customer-table {
    min-width: 680px;
}

.customer-table > tbody > tr > td {
    vertical-align: middle;
}

.customer-action-column {
    width: 140px;
    text-align: center;
    white-space: nowrap;
}

.customer-actions {
    display: grid;
    grid-template-columns: repeat(3, 2.25rem);
    justify-content: center;
    gap: .4rem;
}

.customer-actions .btn {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    align-items: center;
    justify-content: center;
    padding: 0;
}
</style>



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


<div class="table-responsive">

<table class="table table-bordered table-striped customer-table">


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


<th class="customer-action-column">
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





<td class="customer-action-column">

<div class="customer-actions">


<a href="view.php?id=<?=$row['id'];?>"

class="btn btn-info btn-sm"
title="View customer"
aria-label="View customer">

<i class="fa fa-eye"></i>

</a>



<a href="edit.php?id=<?=$row['id'];?>"

class="btn btn-warning btn-sm"
title="Edit customer"
aria-label="Edit customer">

<i class="fa fa-edit"></i>

</a>




<a href="delete.php?id=<?=$row['id'];?>"

class="btn btn-danger btn-sm"

title="Delete customer"

aria-label="Delete customer"

onclick="return confirm('Delete this customer?');">

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

<td colspan="5" class="text-center">

No customer found

</td>

</tr>



<?php } ?>



</tbody>


</table>

</div>



</div>


</div>





<?php

include "../includes/footer.php";

?>
