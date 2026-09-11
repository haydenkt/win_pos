<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}



include "../config/database.php";

if(!isset($_POST['customer_id'])){


    header("Location:add.php");

    exit();


}






// ======================
// ORDER DATA
// ======================


$customer_id = intval($_POST['customer_id']);



$order_status = $_POST['status'] ?? "Quotation";


$notes = $_POST['notes'] ?? "";





$discount = floatval($_POST['discount'] ?? 0);


$installation_cost = floatval($_POST['installation_cost'] ?? 0);


$deposit = floatval($_POST['deposit'] ?? 0);







// ======================
// CALCULATE SUBTOTAL
// ======================


$subtotal = 0;
$posted_types = $_POST['calculation_type'] ?? [];

foreach ($posted_types as $i => $posted_type) {
    $posted_qty = max(1, (int) ($_POST['qty'][$i] ?? 1));

    if ($posted_type === 'SQFT') {
        $posted_width = max(0, (float) ($_POST['width'][$i] ?? 0));
        $posted_height = max(0, (float) ($_POST['height'][$i] ?? 0));
        $posted_price = max(0, (float) ($_POST['price'][$i] ?? 0));
        $subtotal += $posted_width * $posted_height * $posted_qty * $posted_price;
    } else {
        $posted_unit_price = max(0, (float) ($_POST['unit_price'][$i] ?? 0));
        $subtotal += $posted_qty * $posted_unit_price;
    }
}







$grand_total =

$subtotal - $discount + $installation_cost;







$balance =

$grand_total - $deposit;








if($balance <= 0){


    $payment_status="Paid";


}
elseif($deposit > 0){


    $payment_status="Partial";


}
else{


    $payment_status="Unpaid";


}








// ======================
// INVOICE NUMBER
// ======================


$result=mysqli_query($conn,

"SELECT MAX(id) AS last_id FROM orders"

);



$row=mysqli_fetch_assoc($result);



$next_id=($row['last_id'] ?? 0)+1;




$invoice_no="INV".str_pad(

$next_id,

5,

"0",

STR_PAD_LEFT

);







// ======================
// SAVE ORDER
// ======================


$sql="INSERT INTO orders

(

invoice_no,

customer_id,

subtotal,

discount,

installation_cost,

grand_total,

deposit,

balance,

payment_status,

order_status,

notes

)

VALUES

(

'$invoice_no',

'$customer_id',

'$subtotal',

'$discount',

'$installation_cost',

'$grand_total',

'$deposit',

'$balance',

'$payment_status',

'$order_status',

'$notes'

)";





if(!mysqli_query($conn,$sql)){


    die("Order Error : ".mysqli_error($conn));


}




$order_id=mysqli_insert_id($conn);
// ======================
// SAVE ORDER ITEMS
// ======================



$factory_product_ids = $_POST['factory_product_id'] ?? [];

$custom_products = $_POST['custom_product'] ?? [];

$category_ids = $_POST['category_id'] ?? [];

$material_ids = $_POST['material_type_id'] ?? [];

$calculation_types = $_POST['calculation_type'] ?? [];

$widths = $_POST['width'] ?? [];

$heights = $_POST['height'] ?? [];

$qtys = $_POST['qty'] ?? [];

$sqfts = $_POST['sqft'] ?? [];

$prices = $_POST['price'] ?? [];

$unit_prices = $_POST['unit_price'] ?? [];

$totals = $_POST['total'] ?? [];





$count = count($calculation_types);

for($i=0; $i<$count; $i++){



    // ======================
    // PRODUCT NAME
    // ======================


    $factory_product_id = NULL;


    $product_name="";




    if(
        isset($factory_product_ids[$i])
        &&
        $factory_product_ids[$i] != ""
    ){



        // factory product


        $factory_product_id = intval(
            $factory_product_ids[$i]
        );



        $product_query=mysqli_query($conn,"

        SELECT product_name

        FROM factory_products

        WHERE id='$factory_product_id'

        ");




        $product_row=mysqli_fetch_assoc($product_query);



        $product_name=$product_row['product_name'] ?? "";



    }

    else{


        // custom product


        $product_name =

        trim($custom_products[$i] ?? "");



    }








    // skip empty row


    if($product_name==""){


        continue;


    }









    // ======================
    // CATEGORY
    // ======================


    $category_id = intval(

        $category_ids[$i] ?? 0

    );



    $category_name="";




    if($category_id > 0){



        $cat_query=mysqli_query($conn,"

        SELECT name

        FROM categories

        WHERE id='$category_id'

        ");



        $cat=mysqli_fetch_assoc($cat_query);



        $category_name=$cat['name'] ?? "";



    }










    // ======================
    // MATERIAL
    // ======================


    $material_type_id=intval(

        $material_ids[$i] ?? 0

    );



    $material_name="";





    if($material_type_id > 0){



        $mat_query=mysqli_query($conn,"

        SELECT name

        FROM material_types

        WHERE id='$material_type_id'

        ");



        $mat=mysqli_fetch_assoc($mat_query);



        $material_name=$mat['name'] ?? "";



    }








    // ======================
    // ITEM VALUES
    // ======================


    $type=($calculation_types[$i] ?? '') === 'MANUAL' ? 'MANUAL' : 'SQFT';


    $width=max(0, floatval($widths[$i] ?? 0));


    $height=max(0, floatval($heights[$i] ?? 0));


    $quantity=max(1, intval($qtys[$i] ?? 1));


    $price=max(0, floatval($prices[$i] ?? 0));
    $unit_price=max(0, floatval($unit_prices[$i] ?? 0));

    if ($type === 'SQFT') {
        $sqft = $width * $height * $quantity;
        $unit_price = 0;
        $total_price = $sqft * $price;
    } else {
        $sqft = 0;
        $price = 0;
        $total_price = $quantity * $unit_price;
    }
// ======================
// INSERT ORDER ITEM
// ======================



$sql2="INSERT INTO order_items

(

order_id,

category,

product_name,

calculation_type,

width,

height,

quantity,

sqft,

price_per_sqft,

unit_price,

total_price,

category_id,

material_type_id,

factory_product_id

)

VALUES

(

'$order_id',

'$category_name',

'$product_name',

'$type',

'$width',

'$height',

'$quantity',

'$sqft',

'$price',

'$unit_price',

'$total_price',

".($category_id > 0 ? $category_id : "NULL").",

".($material_type_id > 0 ? $material_type_id : "NULL").",

".($factory_product_id ? $factory_product_id : "NULL")."

)";






if(!mysqli_query($conn,$sql2)){


    die("Item Save Error : ".mysqli_error($conn));


}



}






// ======================
// FINISH
// ======================



header("Location:index.php");


exit();



?>
