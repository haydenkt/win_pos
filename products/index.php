<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";
include "../includes/header.php";
include "../includes/sidebar.php";



$search = "";


if(isset($_GET['search'])){

    $search = $_GET['search'];

}



$sql = "

SELECT *

FROM products

WHERE

name LIKE '%$search%'

OR category LIKE '%$search%'

ORDER BY id DESC

";



$result = mysqli_query($conn,$sql);


?>



<h2>
<i class="fa fa-cubes"></i>
Products / Inventory
</h2>




<div class="row mt-3">


<div class="col-md-6">


<form method="GET">


<div class="input-group">


<input type="text"
name="search"
class="form-control"
placeholder="Search product..."
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

Add Product


</a>


</div>



</div>





<div class="card mt-3">


<div class="card-body">



<table class="table table-bordered table-striped">


<thead>


<tr>


<th>
#
</th>


<th>
Product Name
</th>


<th>
Category
</th>


<th>
Unit
</th>


<th>
Purchase Price
</th>


<th>
Selling Price
</th>


<th>
Stock
</th>


<th>
Action
</th>


</tr>


</thead>



<tbody>



<?php


$i=1;


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

<?=$row['category'];?>

</td>



<td>

<?=$row['unit'];?>

</td>



<td>

<?=number_format($row['purchase_price']);?>

</td>



<td>

<?=number_format($row['selling_price']);?>

</td>



<td>



<?php


if($row['stock_qty'] <= $row['minimum_stock']){


?>


<span class="badge bg-danger">

<?=$row['stock_qty'];?>

Low Stock

</span>



<?php

}else{


?>


<span class="badge bg-success">

<?=$row['stock_qty'];?>

</span>


<?php

}


?>



</td>



<td>



<a href="edit.php?id=<?=$row['id'];?>"
class="btn btn-warning btn-sm">


<i class="fa fa-edit"></i>


</a>




<a href="delete.php?id=<?=$row['id'];?>"
class="btn btn-danger btn-sm"

onclick="return confirm('Delete this product?');">


<i class="fa fa-trash"></i>


</a>



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