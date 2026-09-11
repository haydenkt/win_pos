<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



// CHECK ORDER ID

if(!isset($_GET['id'])){

    die("Invoice ID missing");

}


$order_id = intval($_GET['id']);




// SETTINGS

function getSetting($conn,$key){

    $key = mysqli_real_escape_string($conn,$key);


    $query=mysqli_query($conn,"

        SELECT setting_value

        FROM settings

        WHERE setting_group='company'

        AND setting_key='$key'

    ");


    if($row=mysqli_fetch_assoc($query)){

        return $row['setting_value'];

    }


    return "";

}

function getInvoiceSetting($conn,$key){

    $key = mysqli_real_escape_string($conn,$key);

    $query = mysqli_query($conn,"
        SELECT setting_value
        FROM settings
        WHERE setting_group='invoice'
        AND setting_key='$key'
        LIMIT 1
    ");

    if($row=mysqli_fetch_assoc($query)){
        return $row['setting_value'];
    }

    return "";
}

$company_name = getSetting($conn,'company_name');
$company_address = getSetting($conn,'address');
$company_phone = getSetting($conn,'phone');
$company_email = getSetting($conn,'email');
$company_logo = getSetting($conn,'logo');

$invoice_title   = getInvoiceSetting($conn,'title');
$invoice_prefix  = getInvoiceSetting($conn,'number_prefix');
$footer_text     = getInvoiceSetting($conn,'footer_text');
$warranty_text   = getInvoiceSetting($conn,'warranty_text');
$show_logo       = getInvoiceSetting($conn,'show_logo');
$show_notes      = getInvoiceSetting($conn,'show_notes');



// ORDER DATA


$order_query=mysqli_query($conn,"

SELECT

orders.*,

customers.name AS customer_name,
customers.phone AS customer_phone,
customers.address AS customer_address


FROM orders


LEFT JOIN customers

ON orders.customer_id = customers.id


WHERE orders.id='$order_id'


");



$order=mysqli_fetch_assoc($order_query);



if(!$order){

    die("Order not found");

}




// ITEMS

$item_query=mysqli_query($conn,"

SELECT

order_items.*,

categories.name AS category_name,

material_types.name AS material_name,

factory_products.product_name AS factory_product_name

FROM order_items

LEFT JOIN categories
ON order_items.category_id = categories.id

LEFT JOIN material_types
ON order_items.material_type_id = material_types.id

LEFT JOIN factory_products
ON order_items.factory_product_id = factory_products.id

WHERE order_items.order_id='$order_id'

");



?>

<!DOCTYPE html>

<html>

<head>

<title>
Invoice
</title>


<link rel="stylesheet" href="../assets/css/invoice_A5.css">


</head>



<body>


<div class="invoice">



<!-- HEADER -->


<div class="header">


<div class="logo">


<?php if(
    $show_logo=="Yes" &&
    $company_logo!="" &&
    file_exists("../uploads/logo/".$company_logo)
){ ?>


<img src="../uploads/logo/<?=$company_logo;?>">


<?php }else{ ?>


<div class="logo-placeholder">
LOGO
</div>


<?php } ?>


</div>



<div class="company">


<h2>
<?=$company_name;?>
</h2>


<p>
Steel | Aluminium | UPVC Windows
</p>


<p>
<?=$company_address;?>
</p>


<p>
Phone: <?=$company_phone;?>
</p>


<p>
Email: <?=$company_email;?>
</p>


</div>



</div>





<div class="title">

<?= htmlspecialchars($invoice_title) ?>

</div>





<div class="invoice-box">


<div>


<b>
Invoice No:
</b>

<br>

<?= htmlspecialchars($invoice_prefix) ?>-<?=date('Y');?>-<?=str_pad($order['id'],6,'0',STR_PAD_LEFT);?>


</div>



<div>


<b>
Date:
</b>

<br>


<?=date('d-m-Y',strtotime($order['created_at']));?>


</div>


</div>







<!-- CUSTOMER -->


<div class="section-title">

CUSTOMER INFORMATION

</div>


<div class="customer">


<b>
Name:
</b>

<?=$order['customer_name'];?>


<br>


<b>
Phone:
</b>

<?=$order['customer_phone'];?>


<br>


<b>
Address:
</b>

<?=$order['customer_address'];?>


</div>









<!-- ITEMS -->


<div class="section-title">

PRODUCT DETAILS

</div>



<table>


<thead>


<tr>

<th>No</th>

<th>Product</th>

<th>Size</th>

<th>Qty</th>

<th>SQFT</th>

<th>Total</th>


</tr>


</thead>



<tbody>


<?php

$i=1;


while($item=mysqli_fetch_assoc($item_query)){


?>


<tr>


<td><?=$i++;?></td>


<td>

<strong>
<?=htmlspecialchars($item['product_name']);?>
</strong>

<?php if(!empty($item['category_name'])){ ?>

<br>

<small>

Category:
<?=htmlspecialchars($item['category_name']);?>

</small>

<?php } ?>

<?php if(!empty($item['material_name'])){ ?>

<br>

<small>

Material:
<?=htmlspecialchars($item['material_name']);?>

</small>

<?php } ?>

<br>

<small>

<?=htmlspecialchars($item['calculation_type']);?>

</small>

</td>



<td>


<?php if($item['calculation_type']=="SQFT"){ ?>


<?=number_format($item['width'],2);?> × <?=number_format($item['height'],2);?>


<?php }else{ ?>


-


<?php } ?>


</td>




<td>

<?=number_format($item['quantity']);?>

</td>



<td>

<?=number_format($item['sqft'],2);?>

</td>



<td>

<?=number_format($item['total_price']);?>

</td>


</tr>


<?php } ?>


</tbody>


</table>








<!-- PAYMENT -->


<div class="section-title">

PAYMENT SUMMARY

</div>




<table class="payment">


<tr>

<td>
Subtotal
</td>

<td>
<?=number_format($order['subtotal']);?>
</td>


</tr>



<tr>

<td>
Discount
</td>

<td>
<?=number_format($order['discount']);?>
</td>


</tr>



<tr>

<td>
Installation
</td>

<td>
<?=number_format($order['installation_cost']);?>
</td>


</tr>



<tr class="grand">


<td>
GRAND TOTAL
</td>


<td>

<?=number_format($order['grand_total']);?>

</td>


</tr>




<tr>

<td>
Deposit
</td>


<td>
<?=number_format($order['deposit']);?>
</td>


</tr>



<tr class="balance">


<td>
BALANCE
</td>


<td>

<?=number_format($order['balance']);?>

</td>


</tr>



</table>







<div class="status">


Payment Status:

<?=$order['payment_status'];?>


</div>







<!-- NOTES -->


<?php if($show_notes=="Yes"){ ?>

<div class="section-title">

NOTES

</div>

<div class="notes">

<?= nl2br(htmlspecialchars($order['notes'])) ?>

</div>

<?php } ?>






<div class="footer">


<?= nl2br(htmlspecialchars($footer_text)) ?>

<br><br>

<?= nl2br(htmlspecialchars($warranty_text)) ?>


</div>



</div>


</body>

</html>