<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";




if(!isset($_POST['id'])){

    header("Location:index.php");
    exit();

}



$order_id = intval($_POST['id']);




$customer_id = intval($_POST['customer_id']);



$order_status = mysqli_real_escape_string(
    $conn,
    $_POST['order_status'] ?? 'Confirmed'
);



$notes = mysqli_real_escape_string(
    $conn,
    $_POST['notes'] ?? ''
);






$conn->begin_transaction();


try{



// ======================
// UPDATE ORDER
// ======================


$conn->query("

UPDATE orders SET

customer_id='$customer_id',

order_status='$order_status',

notes='$notes'


WHERE id='$order_id'

");







// ======================
// DELETE OLD ITEMS
// ======================


// Delete old order items before saving the edited rows.

$conn->query("

DELETE FROM order_items

WHERE order_id='$order_id'

");







// ======================
// ARRAYS
// ======================


$factory_products = $_POST['factory_product_id'] ?? [];

$categories = $_POST['category_id'] ?? [];

$materials = $_POST['material_type_id'] ?? [];

$types = $_POST['calculation_type'] ?? [];

$widths = $_POST['width'] ?? [];

$heights = $_POST['height'] ?? [];

$qtys = $_POST['qty'] ?? [];

$sqfts = $_POST['sqft'] ?? [];

$prices = $_POST['price'] ?? [];

$unit_prices = $_POST['unit_price'] ?? [];

$custom_products = $_POST['custom_product'] ?? [];







$count = count($types);






for($i=0;$i<$count;$i++){



$product_id = intval(
    $factory_products[$i] ?? 0
);



$product_name = "";





// factory product name

if($product_id > 0){


    $p=mysqli_query($conn,"

    SELECT product_name

    FROM factory_products

    WHERE id='$product_id'

    ");



    $product=mysqli_fetch_assoc($p);


    $product_name=$product['product_name'] ?? '';



}

else{


    $product_name=$custom_products[$i] ?? '';

}





if($product_name==""){

    continue;

}






$category_id = 0;
$material_id = 0;

if ($product_id > 0) {

    $fp_stmt = $conn->prepare("
        SELECT category_id, material_type_id
        FROM factory_products
        WHERE id = ?
        LIMIT 1
    ");

    if (!$fp_stmt) {
        throw new Exception(
            "Factory product lookup failed: " . $conn->error
        );
    }

    $fp_stmt->bind_param("i", $product_id);

    $fp_stmt->execute();

    $fp_result = $fp_stmt->get_result();

    $fp = $fp_result->fetch_assoc();

    if (!$fp) {
        throw new Exception(
            "Factory product not found: " . $product_id
        );
    }

    $category_id = intval($fp['category_id']);
    $material_id = intval($fp['material_type_id']);
}
else {

    // Custom product
    $category_id = intval(
        $categories[$i] ?? 0
    );

    $material_id = intval(
        $materials[$i] ?? 0
    );
}





$type=($types[$i] ?? '') === 'MANUAL' ? 'MANUAL' : 'SQFT';



$width=max(0, floatval(
    $widths[$i] ?? 0
));



$height=max(0, floatval(
    $heights[$i] ?? 0
));



$qty=max(1, intval(
    $qtys[$i] ?? 1
));



$price=max(0, floatval($prices[$i] ?? 0));
$unit_price=max(0, floatval($unit_prices[$i] ?? 0));

if ($type === 'SQFT') {
    $sqft = $width * $height * $qty;
    $unit_price = 0;
    $total_price = $sqft * $price;
    $stored_price = $price;
} else {
    $sqft = 0;
    $price = 0;
    $total_price = $qty * $unit_price;
    $stored_price = $unit_price;
}








// GET CATEGORY NAME

$category="";


if($category_id>0){


    $c=mysqli_query($conn,"

    SELECT name

    FROM categories

    WHERE id='$category_id'

    ");


    $cat=mysqli_fetch_assoc($c);


    $category=$cat['name'] ?? '';

}





// INSERT ITEM


$stmt=$conn->prepare("

INSERT INTO order_items

(
order_id,
category_id,
material_type_id,
factory_product_id,
category,
product_name,
calculation_type,
width,
height,
quantity,
sqft,
final_price_per_sqft
)

VALUES

(?,?,?,?,?,?,?,?,?,?,?,?)

");




$stmt->bind_param(

"iiiisssddidd",

$order_id,
$category_id,
$material_id,
$product_id,
$category,
$product_name,
$type,
$width,
$height,
$qty,
$sqft,
$stored_price

);




$stmt->execute();



}

$conn->commit();




header("Location:view.php?id=$order_id");

exit();




}

catch(Exception $e){


$conn->rollback();


echo $e->getMessage();


}



?>
