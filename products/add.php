<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../includes/header.php";
include "../includes/sidebar.php";

?>


<h2>
<i class="fa fa-plus"></i>
Add Product
</h2>




<div class="card mt-3">


<div class="card-body">


<form method="POST" action="save.php">



<div class="row">



<div class="col-md-6">


<label>
Product Name
</label>


<input type="text"
name="name"
class="form-control"
required>


</div>





<div class="col-md-6">


<label>
Category
</label>


<select name="category"
class="form-control">


<option value="Material">
Material
</option>


<option value="Glass">
Glass
</option>


<option value="Accessory">
Accessory
</option>


<option value="Finished Product">
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


<option value="pcs">
Pieces
</option>


<option value="meter">
Meter
</option>


<option value="sqft">
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
value="0">


</div>





<div class="col-md-4">


<label>
Selling Price
</label>


<input type="number"
step="0.01"
name="selling_price"
class="form-control"
value="0">


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
value="0">


</div>





<div class="col-md-6">


<label>
Minimum Stock
</label>


<input type="number"
step="0.01"
name="minimum_stock"
class="form-control"
value="0">


</div>



</div>





<br>


<button class="btn btn-success">

<i class="fa fa-save"></i>

Save Product

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