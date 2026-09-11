<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";


if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$id = intval($_GET['id']);



$query = mysqli_query($conn,

"SELECT *
FROM products
WHERE id='$id'"

);


$product = mysqli_fetch_assoc($query);



if(!$product){

    echo "Product not found";
    exit();

}



include "../includes/header.php";
include "../includes/sidebar.php";

?>



<h2>
<i class="fa fa-edit"></i>
Edit Product
</h2>




<div class="card mt-3">


<div class="card-body">


<form method="POST" action="update.php">



<input type="hidden"
name="id"
value="<?=$product['id'];?>">





<div class="row">



<div class="col-md-6">


<label>
Product Name
</label>


<input type="text"
name="name"
class="form-control"
value="<?=$product['name'];?>"
required>


</div>





<div class="col-md-6">


<label>
Category
</label>


<select name="category"
class="form-control">


<option value="Material"

<?php if($product['category']=="Material") echo "selected"; ?>

>
Material
</option>



<option value="Glass"

<?php if($product['category']=="Glass") echo "selected"; ?>

>
Glass
</option>



<option value="Accessory"

<?php if($product['category']=="Accessory") echo "selected"; ?>

>
Accessory
</option>



<option value="Finished Product"

<?php if($product['category']=="Finished Product") echo "selected"; ?>

>
Finished Product
</option>



</select>


</div>



</div>





<div class="row mt-3">



<div class="col-md-4">


<label>
Unit
</label>


<select name="unit"
class="form-control">


<option value="pcs"

<?php if($product['unit']=="pcs") echo "selected"; ?>

>
Pieces
</option>



<option value="meter"

<?php if($product['unit']=="meter") echo "selected"; ?>

>
Meter
</option>



<option value="sqft"

<?php if($product['unit']=="sqft") echo "selected"; ?>

>
SQFT
</option>



</select>


</div>





<div class="col-md-4">


<label>
Purchase Price
</label>


<input type="number"
step="0.01"
name="purchase_price"
class="form-control"
value="<?=$product['purchase_price'];?>">


</div>





<div class="col-md-4">


<label>
Selling Price
</label>


<input type="number"
step="0.01"
name="selling_price"
class="form-control"
value="<?=$product['selling_price'];?>">


</div>



</div>






<div class="row mt-3">



<div class="col-md-6">


<label>
Stock Quantity
</label>


<input type="number"
step="0.01"
name="stock_qty"
class="form-control"
value="<?=$product['stock_qty'];?>">


</div>





<div class="col-md-6">


<label>
Minimum Stock
</label>


<input type="number"
step="0.01"
name="minimum_stock"
class="form-control"
value="<?=$product['minimum_stock'];?>">


</div>



</div>






<br>


<button class="btn btn-primary">

<i class="fa fa-save"></i>

Update Product

</button>



<a href="index.php"
class="btn btn-secondary">

Cancel

</a>




</form>


</div>


</div>




<?php

include "../includes/footer.php";

?>