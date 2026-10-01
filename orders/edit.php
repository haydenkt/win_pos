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

SELECT
    order_items.*,
    factory_products.id AS current_factory_product_id,
    factory_products.default_price AS product_default_price,
    (
        SELECT invoice_items.price
        FROM invoice_items
        WHERE invoice_items.order_item_id = order_items.id
        ORDER BY invoice_items.id ASC
        LIMIT 1
    ) AS linked_invoice_price

FROM order_items

LEFT JOIN factory_products
ON factory_products.id = order_items.factory_product_id

WHERE order_items.order_id='$id'

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


<?php while($item=mysqli_fetch_assoc($items)){

$stored_price = max(
    0,
    (float) (
        $item['final_price_per_sqft']
        ?? $item['price_per_sqft']
        ?? 0
    )
);

if ($stored_price <= 0) {
    $stored_price = max(
        0,
        (float) ($item['linked_invoice_price'] ?? 0)
    );
}

if ($stored_price <= 0) {
    $stored_price = max(
        0,
        (float) ($item['product_default_price'] ?? 0)
    );
}

$item_type =
    ($item['calculation_type'] ?? 'SQFT') === 'MANUAL'
        ? 'MANUAL'
        : 'SQFT';

$price_per_sqft =
    $item_type === 'SQFT'
        ? $stored_price
        : 0;

$unit_price =
    $item_type === 'MANUAL'
        ? $stored_price
        : 0;

$item_total =
    $item_type === 'SQFT'
        ? (float) ($item['sqft'] ?? 0) * $price_per_sqft
        : (float) ($item['quantity'] ?? 0) * $unit_price;

$has_factory_product =
    (int) ($item['current_factory_product_id'] ?? 0) > 0;

$current_product_name =
    (string) ($item['product_name'] ?? '');

?>


<tr class="order-item-row">



<td>


<select name="factory_product_id[]"

class="form-control product-select">


<option value="<?=$has_factory_product ? (int) $item['factory_product_id'] : '';?>"
        data-type="<?=htmlspecialchars($item_type);?>"
        data-price="<?=htmlspecialchars((string) $stored_price);?>"
        selected>

<?=htmlspecialchars($current_product_name !== '' ? $current_product_name : 'Custom Product');?>

</option>

<?php if ($has_factory_product) { ?>
<option value="" data-type="MANUAL" data-price="0">Custom Product</option>
<?php } ?>



<?php


mysqli_data_seek($products,0);


while($p=mysqli_fetch_assoc($products)){

if ((int) $p['id'] === (int) ($item['factory_product_id'] ?? 0)) {
    continue;
}


?>


<option value="<?=$p['id'];?>"
        data-type="<?=htmlspecialchars((string) $p['calculation_type']);?>"
        data-price="<?=htmlspecialchars((string) $p['default_price']);?>"

>

<?=htmlspecialchars((string) $p['product_name']);?>

</option>



<?php } ?>


</select>

<input type="text"
       name="custom_product[]"
       class="form-control mt-2 custom-product"
       value="<?=!$has_factory_product ? htmlspecialchars($current_product_name) : '';?>"
       placeholder="Custom Product Name"
       <?=$has_factory_product ? 'hidden' : '';?>>

<input type="hidden"
       name="original_product_name[]"
       value="<?=htmlspecialchars($current_product_name);?>">


</td>







<td>


<div class="input-group">


<input type="number"

step="0.5"

name="width[]"

class="form-control width"

value="<?=$item['width'];?>">


<span class="input-group-text">

×

</span>


<input type="number"

step="0.5"

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

<?=($item_type==="SQFT")?'selected':'';?>

>

SQFT

</option>


<option value="MANUAL"

<?=($item_type==="MANUAL")?'selected':'';?>

>

MANUAL

</option>



</select>


</td>

<td>
<input type="number" step="50" min="0" name="price[]" class="form-control price-per-sqft js-comma-price" value="<?=htmlspecialchars((string) $price_per_sqft);?>">
</td>

<td>
<input type="number" step="50" min="0" name="unit_price[]" class="form-control unit-price js-comma-price" value="<?=htmlspecialchars((string) $unit_price);?>">
</td>

<td>
<input type="text" name="total[]" class="form-control item-total" value="<?=number_format($item_total, 2, '.', '');?>" readonly>
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
const orderPriceNumber = (value) => Number(String(value ?? '').replaceAll(',', '')) || 0;

document.querySelectorAll('.order-item-row').forEach(function (row) {
    function syncCalculationFields() {
        const isSqft = row.querySelector('.calculation-type').value === 'SQFT';
        row.querySelector('.width').readOnly = !isSqft;
        row.querySelector('.height').readOnly = !isSqft;
        row.querySelector('.price-per-sqft').readOnly = !isSqft;
        row.querySelector('.unit-price').readOnly = isSqft;
    }

    function calculateRow() {
        window.formatCommaPriceInput?.(row.querySelector('.price-per-sqft'));
        window.formatCommaPriceInput?.(row.querySelector('.unit-price'));
        const type = row.querySelector('.calculation-type').value;
        const quantity = Math.max(1, Number(row.querySelector('.qty').value) || 1);
        let total = 0;

        if (type === 'SQFT') {
            const width = Math.max(0, Number(row.querySelector('.width').value) || 0);
            const height = Math.max(0, Number(row.querySelector('.height').value) || 0);
            const price = Math.max(0, orderPriceNumber(row.querySelector('.price-per-sqft').value));
            const sqft = width * height * quantity;
            row.querySelector('.sqft').value = sqft.toFixed(2);
            total = sqft * price;
        } else {
            const unitPrice = Math.max(0, orderPriceNumber(row.querySelector('.unit-price').value));
            row.querySelector('.sqft').value = '0.00';
            total = quantity * unitPrice;
        }

        row.querySelector('.item-total').value = total.toFixed(2);
    }

    row.querySelector('.product-select').addEventListener('change', function (event) {
        const option = event.target.selectedOptions[0];
        const type = option.dataset.type === 'MANUAL' ? 'MANUAL' : 'SQFT';
        const defaultPrice = Math.max(0, Number(option.dataset.price) || 0);
        const customProduct = row.querySelector('.custom-product');

        row.querySelector('.calculation-type').value = type;
        row.querySelector('.price-per-sqft').value = type === 'SQFT' ? defaultPrice : 0;
        row.querySelector('.unit-price').value = type === 'MANUAL' ? defaultPrice : 0;
        customProduct.hidden = event.target.value !== '';

        if (customProduct.hidden) {
            customProduct.value = '';
        } else {
            customProduct.focus();
        }

        syncCalculationFields();
        calculateRow();
    });

    row.querySelector('.calculation-type').addEventListener('change', function () {
        syncCalculationFields();
    });

    row.querySelectorAll('input, select').forEach(function (field) {
        field.addEventListener('input', calculateRow);
        field.addEventListener('change', calculateRow);
    });

    syncCalculationFields();
    calculateRow();
});
</script>









<?php

include "../includes/footer.php";

?>
