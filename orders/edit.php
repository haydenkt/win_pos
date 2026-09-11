<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

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



$id=intval($_GET['id']);





// GET ORDER

$order_query=mysqli_query($conn,"

SELECT

orders.*,

customers.name AS customer_name


FROM orders


LEFT JOIN customers

ON orders.customer_id=customers.id


WHERE orders.id='$id'

");



$order=mysqli_fetch_assoc($order_query);



if(!$order){

    die("Order not found");

}





// CUSTOMERS

$customers=mysqli_query($conn,"

SELECT *

FROM customers

ORDER BY name ASC

");







// PRODUCTS

$products=mysqli_query($conn,"

SELECT

factory_products.*,

categories.name AS category_name,

material_types.name AS material_name


FROM factory_products


LEFT JOIN categories

ON factory_products.category_id=categories.id


LEFT JOIN material_types

ON factory_products.material_type_id=material_types.id


WHERE factory_products.status='Active'


ORDER BY factory_products.product_name ASC

");






// ITEMS

$items=mysqli_query($conn,"

SELECT *

FROM order_items

WHERE order_id='$id'

");





include "../includes/header.php";
include "../includes/sidebar.php";


?>





<h2>

<i class="fa fa-industry"></i>

Edit Order #<?=$id;?>

</h2>





<form method="POST" action="update.php">



<input type="hidden"

name="id"

value="<?=$id;?>">







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


<?php while($c=mysqli_fetch_assoc($customers)){ ?>


<option value="<?=$c['id'];?>"

<?=($c['id']==$order['customer_id'])?'selected':'';?>

>

<?=$c['name'];?>

</option>


<?php } ?>


</select>



</div>








<div class="col-md-6">


<label>

Order Status

</label>



<select name="order_status"

class="form-control">


<?php


$status=[

"Confirmed",

"Production",

"Ready",

"Installation",

"Completed"

];


foreach($status as $s){


?>


<option value="<?=$s;?>"

<?=($order['order_status']==$s)?'selected':'';?>

>


<?=$s === 'Production' ? 'In Progress' : htmlspecialchars($s);?>

</option>


<?php } ?>


</select>



</div>




</div>


</div>


</div>









<div class="card mt-3">


<div class="card-body">


<h4>

<i class="fa fa-window-maximize"></i>

Products

</h4>






<table class="table table-bordered">


<thead>


<tr>

<th>

Product

</th>


<th>

Size

</th>


<th>

Qty

</th>


<th>

SQFT

</th>


<th>

Type

</th>

<th>Price / SQFT</th>

<th>Unit Price</th>

<th>Total</th>


</tr>


</thead>




<tbody>


<?php while($item=mysqli_fetch_assoc($items)){ ?>


<tr class="order-item-row">



<td>


<select name="factory_product_id[]"

class="form-control">


<option value="<?=$item['factory_product_id'];?>">

<?=$item['product_name'];?>

</option>



<?php


mysqli_data_seek($products,0);


while($p=mysqli_fetch_assoc($products)){


?>


<option value="<?=$p['id'];?>"

<?=($p['id']==$item['factory_product_id'])?'selected':'';?>

>

<?=$p['product_name'];?>

</option>



<?php } ?>


</select>


</td>







<td>


<div class="input-group">


<input type="number"

step="0.01"

name="width[]"

class="form-control width"

value="<?=$item['width'];?>">


<span class="input-group-text">

×

</span>


<input type="number"

step="0.01"

name="height[]"

class="form-control height"

value="<?=$item['height'];?>">


</div>


</td>







<td>


<input type="number"

name="qty[]"

class="form-control qty"

value="<?=$item['quantity'];?>">


</td>







<td>


<input type="text"

name="sqft[]"

class="form-control sqft"

value="<?=$item['sqft'];?>"

readonly>


</td>








<td>


<select name="calculation_type[]"

class="form-control calculation-type">


<option value="SQFT"

<?=($item['calculation_type']=="SQFT")?'selected':'';?>

>

SQFT

</option>


<option value="MANUAL"

<?=($item['calculation_type']=="MANUAL")?'selected':'';?>

>

MANUAL

</option>



</select>


</td>

<td>
<input type="number" step="0.01" min="0" name="price[]" class="form-control price-per-sqft" value="<?=htmlspecialchars((string) $item['price_per_sqft']);?>">
</td>

<td>
<input type="number" step="0.01" min="0" name="unit_price[]" class="form-control unit-price" value="<?=htmlspecialchars((string) $item['unit_price']);?>">
</td>

<td>
<input type="text" name="total[]" class="form-control item-total" value="<?=number_format((float) $item['total_price'], 2, '.', '');?>" readonly>
</td>



</tr>


<?php } ?>



</tbody>



</table>





</div>


</div>










<div class="card mt-3">


<div class="card-body">


<label>

Order Notes

</label>


<textarea name="notes"

class="form-control"

rows="4"><?=htmlspecialchars($order['notes']);?></textarea>



<br>


<button class="btn btn-primary">

<i class="fa fa-save"></i>

Update Order

</button>




<a href="view.php?id=<?=$id;?>"

class="btn btn-secondary">

Cancel

</a>




</div>


</div>




</form>

<script>
document.querySelectorAll('.order-item-row').forEach(function (row) {
    function calculateRow() {
        const type = row.querySelector('.calculation-type').value;
        const quantity = Math.max(1, Number(row.querySelector('.qty').value) || 1);
        let total = 0;

        if (type === 'SQFT') {
            const width = Math.max(0, Number(row.querySelector('.width').value) || 0);
            const height = Math.max(0, Number(row.querySelector('.height').value) || 0);
            const price = Math.max(0, Number(row.querySelector('.price-per-sqft').value) || 0);
            const sqft = width * height * quantity;
            row.querySelector('.sqft').value = sqft.toFixed(2);
            total = sqft * price;
        } else {
            const unitPrice = Math.max(0, Number(row.querySelector('.unit-price').value) || 0);
            row.querySelector('.sqft').value = '0.00';
            total = quantity * unitPrice;
        }

        row.querySelector('.item-total').value = total.toFixed(2);
    }

    row.querySelectorAll('input, select').forEach(function (field) {
        field.addEventListener('input', calculateRow);
        field.addEventListener('change', calculateRow);
    });
});
</script>









<?php

include "../includes/footer.php";

?>
