<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";

include "../includes/header.php";
include "../includes/sidebar.php";



// ======================
// CUSTOMERS
// ======================

$customers=mysqli_query($conn,"

SELECT *

FROM customers

ORDER BY name ASC

");




// ======================
// FACTORY PRODUCTS
// ======================

$products=mysqli_query($conn,"

SELECT

factory_products.*,

categories.name AS category_name,

material_types.name AS material_name


FROM factory_products


LEFT JOIN categories

ON factory_products.category_id = categories.id


LEFT JOIN material_types

ON factory_products.material_type_id = material_types.id


WHERE factory_products.status='Active'


ORDER BY factory_products.product_name ASC

");




// ======================
// CATEGORIES
// ======================

$categories=mysqli_query($conn,"

SELECT *

FROM categories

ORDER BY name ASC

");




// ======================
// MATERIAL TYPES
// ======================

$materials=mysqli_query($conn,"

SELECT *

FROM material_types

ORDER BY name ASC

");

?>



<h2>

<i class="fa fa-plus"></i>

New Order

</h2>




<form method="POST" action="save.php">





<!-- CUSTOMER INFO -->

<div class="card mt-3">

<div class="card-body">


<div class="row">


<div class="col-md-6">

<label>
Customer
</label>


<select name="customer_id"

class="form-control"

required>


<option value="">
Select Customer
</option>



<?php while($c=mysqli_fetch_assoc($customers)){ ?>


<option value="<?=$c['id'];?>">

<?=$c['name'];?>

</option>


<?php } ?>


</select>


</div>





<div class="col-md-3">


<label>
Status
</label>


<select name="status"

class="form-control">


<option value="Quotation">
Quotation
</option>


<option value="Confirmed">
Confirmed
</option>


<option value="Production">
In Progress
</option>


<option value="Completed">
Completed
</option>


</select>


</div>





<div class="col-md-3">


<label>
Notes
</label>


<input type="text"

name="notes"

class="form-control">


</div>


</div>


</div>

</div>









<!-- ITEMS -->


<div class="card mt-3">


<div class="card-body">


<h4>
Order Items
</h4>



<table class="table table-bordered"

id="items">


<thead>

<tr>

<th>Product</th>

<th>Category</th>

<th>Material</th>

<th>Type</th>

<th>Width</th>

<th>Height</th>

<th>Qty</th>

<th>SQFT</th>

<th>Price/SQFT</th>

<th>Unit Price</th>

<th>Total</th>

<th></th>

</tr>

</thead>



<tbody>


<tr>



<!-- PRODUCT -->

<td>


<select

name="factory_product_id[]"

class="form-control product_select">


<option value="">

Custom Product

</option>



<?php


mysqli_data_seek($products,0);


while($p=mysqli_fetch_assoc($products)){


?>


<option

value="<?=$p['id'];?>"

data-category-id="<?=$p['category_id'];?>"

data-category="<?=$p['category_name'];?>"

data-material-id="<?=$p['material_type_id'];?>"

data-material="<?=$p['material_name'];?>"

data-type="<?=$p['calculation_type'];?>"

data-price="<?=$p['default_price'];?>"

>


<?=$p['product_name'];?> - <?=$p['material_name'];?>


</option>



<?php } ?>


</select>



<input type="text"

name="custom_product[]"

class="form-control mt-2 custom_product"

placeholder="Custom Product Name">


</td>








<!-- CATEGORY -->


<td>


<select

name="category_id[]"

class="form-control category_id">


<option value="">

Select Category

</option>



<?php


mysqli_data_seek($categories,0);


while($cat=mysqli_fetch_assoc($categories)){


?>


<option value="<?=$cat['id'];?>">

<?=$cat['name'];?>

</option>


<?php } ?>


</select>



<input type="hidden"

name="category[]"

class="category_name">


</td>








<!-- MATERIAL -->


<td>


<select

name="material_type_id[]"

class="form-control material_id">


<option value="">

Select Material

</option>



<?php


mysqli_data_seek($materials,0);


while($mat=mysqli_fetch_assoc($materials)){


?>


<option value="<?=$mat['id'];?>">

<?=$mat['name'];?>

</option>


<?php } ?>


</select>



<input type="hidden"

name="material_name[]"

class="material_name">


</td>








<!-- TYPE -->


<td>


<select

name="calculation_type[]"

class="form-control type">


<option value="SQFT">

SQFT

</option>


<option value="MANUAL">

MANUAL

</option>


</select>


</td>








<!-- WIDTH -->


<td>

<input type="number"

step="0.01"

name="width[]"

class="form-control width">

</td>







<!-- HEIGHT -->


<td>

<input type="number"

step="0.01"

name="height[]"

class="form-control height">

</td>








<!-- QTY -->


<td>

<input type="number"

name="qty[]"

value="1"

class="form-control qty">

</td>








<!-- SQFT -->


<td>

<input type="text"

name="sqft[]"

class="form-control sqft"

readonly>

</td>








<!-- PRICE -->


<td>

<input type="number"

step="0.01"

name="price[]"

class="form-control price">

</td>








<!-- UNIT -->


<td>

<input type="number"

step="0.01"

name="unit_price[]"

class="form-control unit_price">

</td>








<!-- TOTAL -->


<td>

<input type="text"

name="total[]"

class="form-control total"

readonly>

</td>








<td>


<button type="button"

class="btn btn-danger remove">

X

</button>


</td>


</tr>


</tbody>


</table>



<button type="button"

class="btn btn-success"

id="addRow">

+ Add Item

</button>


</div>

</div>
<script>


// ==========================
// FACTORY PRODUCT SELECT
// ==========================

document.addEventListener("change",function(e){


if(e.target.classList.contains("product_select")){


let row=e.target.closest("tr");

let option=e.target.options[e.target.selectedIndex];



if(option.value==""){


    // CUSTOM PRODUCT


    row.querySelector(".custom_product").readOnly=false;


    row.querySelector(".category_id").disabled=false;

    row.querySelector(".material_id").disabled=false;


    row.querySelector(".category_name").value="";

    row.querySelector(".material_name").value="";


    row.querySelector(".type").value="MANUAL";


    toggleCalculation(row);


    return;

}





// FACTORY PRODUCT SELECTED


row.querySelector(".custom_product").value="";

row.querySelector(".custom_product").readOnly=true;




// AUTO CATEGORY

row.querySelector(".category_id").value =
option.dataset.categoryId;


row.querySelector(".category_name").value =
option.dataset.category;





// AUTO MATERIAL

row.querySelector(".material_id").value =
option.dataset.materialId;


row.querySelector(".material_name").value =
option.dataset.material;





// AUTO TYPE

row.querySelector(".type").value =
option.dataset.type;





// AUTO PRICE

row.querySelector(".price").value =
option.dataset.price;




// LOCK CATEGORY / MATERIAL

row.querySelector(".category_id").disabled=true;

row.querySelector(".material_id").disabled=true;




toggleCalculation(row);


calculate(row);


}


});







// ==========================
// TYPE CHANGE
// ==========================


document.addEventListener("change",function(e){


if(e.target.classList.contains("type")){


let row=e.target.closest("tr");


toggleCalculation(row);


calculate(row);


}


});







// ==========================
// ENABLE FIELDS
// ==========================


function toggleCalculation(row){


let type=row.querySelector(".type").value;


let width=row.querySelector(".width");

let height=row.querySelector(".height");

let price=row.querySelector(".price");

let unit=row.querySelector(".unit_price");



if(type=="SQFT"){


width.readOnly=false;

height.readOnly=false;

price.readOnly=false;


unit.readOnly=true;

unit.value="";


}
else{


width.readOnly=true;

height.readOnly=true;

price.readOnly=true;


width.value=0;

height.value=0;

price.value=0;


unit.readOnly=false;


}



}








// ==========================
// CALCULATE ITEM
// ==========================


function calculate(row){


let type=row.querySelector(".type").value;


let qty=

Number(row.querySelector(".qty").value)||0;



let total=0;



if(type=="SQFT"){


let width=

Number(row.querySelector(".width").value)||0;


let height=

Number(row.querySelector(".height").value)||0;


let price=

Number(row.querySelector(".price").value)||0;



let sqft=width*height*qty;


row.querySelector(".sqft").value=

sqft.toFixed(2);



total=sqft*price;



}
else{


let unit=

Number(row.querySelector(".unit_price").value)||0;



row.querySelector(".sqft").value="0";


total=qty*unit;


}



row.querySelector(".total").value=

total.toFixed(2);



calculateGrand();


}









// ==========================
// INPUT CHANGE
// ==========================


document.addEventListener("input",function(e){



if(e.target.closest("#items")){


let row=e.target.closest("tr");


calculate(row);


}




if(
e.target.id=="discount" ||
e.target.id=="installation_cost"

){


calculateGrand();


}



});








// ==========================
// GRAND TOTAL
// ==========================


function calculateGrand(){


let subtotal=0;



document.querySelectorAll(".total")

.forEach(function(t){


subtotal += Number(t.value)||0;


});



let discount=

Number(document.getElementById("discount")?.value)||0;



let installation=

Number(document.getElementById("installation_cost")?.value)||0;



let grand=

subtotal-discount+installation;



if(document.getElementById("grand_total")){


document.getElementById("grand_total").value=

grand.toFixed(2);


}



}









// ==========================
// ADD ROW
// ==========================


document.getElementById("addRow").onclick=function(){


let row=document.querySelector("#items tbody tr")
.cloneNode(true);





row.querySelectorAll("input").forEach(function(i){

i.value="";

});




row.querySelector(".product_select").value="";

row.querySelector(".custom_product").readOnly=false;



row.querySelector(".category_id").disabled=false;

row.querySelector(".material_id").disabled=false;


row.querySelector(".category_id").value="";

row.querySelector(".material_id").value="";



row.querySelector(".category_name").value="";

row.querySelector(".material_name").value="";



row.querySelector(".type").value="SQFT";

row.querySelector(".qty").value=1;



row.querySelector(".sqft").value=0;

row.querySelector(".total").value=0;



row.querySelector(".width").readOnly=false;

row.querySelector(".height").readOnly=false;

row.querySelector(".price").readOnly=false;

row.querySelector(".unit_price").readOnly=true;



document.querySelector("#items tbody")

.appendChild(row);



};








// ==========================
// REMOVE ROW
// ==========================


document.addEventListener("click",function(e){


if(e.target.classList.contains("remove")){


let rows=document.querySelectorAll("#items tbody tr");



if(rows.length>1){


e.target.closest("tr").remove();


calculateGrand();


}


}


});







// INITIAL

document.querySelectorAll("#items tbody tr")

.forEach(function(row){


toggleCalculation(row);


});



</script>






<!-- TOTAL SECTION -->

<div class="card mt-3">


<div class="card-body">


<div class="row">


<div class="col-md-3">

<label>
Discount
</label>

<input type="number"

step="0.01"

name="discount"

id="discount"

value="0"

class="form-control">

</div>





<div class="col-md-3">

<label>
Installation Cost
</label>

<input type="number"

step="0.01"

name="installation_cost"

id="installation_cost"

value="0"

class="form-control">

</div>





<div class="col-md-3">

<label>
Deposit
</label>

<input type="number"

step="0.01"

name="deposit"

value="0"

class="form-control">

</div>





<div class="col-md-3">

<label>
Grand Total
</label>

<input type="text"

name="total_amount"

id="grand_total"

class="form-control"

readonly>

</div>


</div>



<br>


<button type="submit"

class="btn btn-primary">

<i class="fa fa-save"></i>

Save Order

</button>



<a href="index.php"

class="btn btn-secondary">

Cancel

</a>


</div>


</div>




</form>





<?php

include "../includes/footer.php";

?>
