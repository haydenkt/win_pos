<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



// CHECK INVOICE ID

if(!isset($_GET['id'])){

    die("Invoice ID missing");

}


$invoice_id = intval($_GET['id']);



// UPDATE PRINT HISTORY

$print_update = mysqli_query($conn,"

UPDATE invoices SET

print_count = print_count + 1,

last_printed_at = NOW()

WHERE id='$invoice_id'

");


if(!$print_update){

    die(mysqli_error($conn));

}





// SETTINGS

function getSetting($conn,$key){

    $key = mysqli_real_escape_string($conn,$key);


    $query=mysqli_query($conn,"

        SELECT setting_value

        FROM settings

        WHERE setting_group='company'

        AND setting_key='$key'

        LIMIT 1

    ");


    if($row=mysqli_fetch_assoc($query)){

        return $row['setting_value'];

    }


    return "";

}




function getInvoiceSetting($conn,$key){

    $key=mysqli_real_escape_string($conn,$key);


    $query=mysqli_query($conn,"

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




$company_name=getSetting($conn,'company_name');

$company_address=getSetting($conn,'address');

$company_phone=getSetting($conn,'phone');

$company_email=getSetting($conn,'email');

$company_logo=getSetting($conn,'logo');



$invoice_title=getInvoiceSetting($conn,'title');

$invoice_prefix=getInvoiceSetting($conn,'number_prefix');

$footer_text=getInvoiceSetting($conn,'footer_text');

$warranty_text=getInvoiceSetting($conn,'warranty_text');

$show_logo=getInvoiceSetting($conn,'show_logo');

$show_notes=getInvoiceSetting($conn,'show_notes');
// INVOICE DATA


$invoice_query=mysqli_query($conn,"

SELECT

invoices.*,

customers.name AS customer_name,

customers.phone AS customer_phone,

customers.address AS customer_address


FROM invoices


LEFT JOIN customers

ON invoices.customer_id = customers.id


WHERE invoices.id='$invoice_id'


");



$invoice=mysqli_fetch_assoc($invoice_query);



if(!$invoice){

    die("Invoice not found");

}






// ITEMS


$item_query=mysqli_query($conn,"

SELECT *

FROM invoice_items

WHERE invoice_id='$invoice_id'

ORDER BY id ASC

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

Phone:
<?=$company_phone;?>

</p>


<p>

Email:
<?=$company_email;?>

</p>


</div>


</div>






<div class="title">


<?=htmlspecialchars($invoice_title);?>



<?php if(($invoice['print_count'] ?? 0) > 1){ ?>


<br>


<span style="

color:red;

font-size:18px;

font-weight:bold;

">

REPRINT

</span>


<?php } ?>


</div>









<div class="invoice-box">


<div>

<b>

ပြေစာအမှတ်:

</b>

<br>


<?=htmlspecialchars($invoice_prefix);?>-<?=date('Y');?>-<?=str_pad($invoice['id'],6,'0',STR_PAD_LEFT);?>


</div>





<div>

<b>

ရက်စွဲ:

</b>

<br>


<?=date('d-m-Y',strtotime($invoice['created_at']));?>


</div>




<div>

<b>

Print Copy:

</b>

<br>


<?=$invoice['print_count'];?>


</div>



</div>







<div class="section-title">

CUSTOMER INFORMATION

</div>



<div class="customer">


<b>

အမည်:

</b>

<?=htmlspecialchars($invoice['customer_name'] ?? '');?>


<br>


<b>

ဖုန်းနံပါတ်:

</b>

<?=htmlspecialchars($invoice['customer_phone'] ?? '');?>


<br>


<b>

နေရပ်လိပ်စာ:

</b>

<?=htmlspecialchars($invoice['customer_address'] ?? '');?>


</div>








<div class="section-title">

PRODUCT DETAILS

</div>




<table>


<thead>


<tr>

<th>အမှတ်စဥ်</th>

<th>အမျိုးမည်</th>

<th>အရွယ်အစား</th>

<th>အရေတွက်</th>

<th>နှုန်းထား</th>

<th>သင့်ငွေ</th>


</tr>


</thead>



<tbody>


<?php

$i=1;


while($item=mysqli_fetch_assoc($item_query)){


?>


<tr>


<td>

<?=$i++;?>

</td>



<td>

<strong>

<?=htmlspecialchars($item['product_name']);?>

</strong>


<br>


<small>

<?=htmlspecialchars($item['description'] ?? '');?>

</small>


</td>

<td>


<?php if($item['width']>0){ ?>


<?=number_format($item['width'],2);?>

×

<?=number_format($item['height'],2);?>


<?php }else{ ?>

-

<?php } ?>


</td>



<td>

<?=number_format($item['quantity']);?>

</td>

<td>

<?=number_format($item['price']);?>

</td>

<td>

<?=number_format($item['total']);?>

</td>


</tr>


<?php } ?>


</tbody>


</table>
<div class="section-title">

PAYMENT SUMMARY

</div>




<table class="payment">


<tr>

<td>

ကျသင့်ငွေ

</td>


<td>

<?=number_format($invoice['subtotal']);?>

</td>


</tr>





<tr>

<td>

လျှော့ငွေ

</td>


<td>

<?=number_format($invoice['discount']);?>

</td>


</tr>





<tr>

<td>

တပ်ဆင်ခ

</td>


<td>

<?=number_format($invoice['installation_cost']);?>

</td>


</tr>






<tr class="grand">


<td>

စုစုပေါင်းကျသင့်ငွေ

</td>


<td>

<?=number_format($invoice['grand_total']);?>

</td>


</tr>






<tr>


<td>

စရံငွေ

</td>



<td>

<?=number_format($invoice['deposit']);?>

</td>



</tr>






<tr class="balance">


<td>

ကျန်ငွေ

</td>



<td>

<?=number_format($invoice['balance']);?>

</td>



</tr>



</table>


<?php if($show_notes=="Yes"){ ?>


<div class="section-title">

မှတ်ချက်

</div>




<div class="notes">


<?=nl2br(htmlspecialchars($invoice['notes'] ?? ''));?>


</div>



<?php } ?>









<div class="footer">


<?=nl2br(htmlspecialchars($footer_text ?? ''));?>


<br><br>


<?=nl2br(htmlspecialchars($warranty_text ?? ''));?>


</div>






</div>


</body>


</html>