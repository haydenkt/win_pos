<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";
require_once "../includes/audit.php";



if(isset($_POST['name'])){



$name = mysqli_real_escape_string($conn,$_POST['name']);

$category = mysqli_real_escape_string($conn,$_POST['category']);

$unit = mysqli_real_escape_string($conn,$_POST['unit']);


$purchase_price = floatval($_POST['purchase_price']);

$selling_price = floatval($_POST['selling_price']);

$stock_qty = floatval($_POST['stock_qty']);

$minimum_stock = floatval($_POST['minimum_stock']);





$sql = "

INSERT INTO products

(

name,

category,

unit,

purchase_price,

selling_price,

stock_qty,

minimum_stock

)


VALUES


(

'$name',

'$category',

'$unit',

'$purchase_price',

'$selling_price',

'$stock_qty',

'$minimum_stock'

)


";




if(mysqli_query($conn,$sql)){
    $product_id = (int) $conn->insert_id;
    $history_type = 'OPENING';
    $history_note = 'Opening stock';
    $source_type = 'PRODUCT';
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $stmt = $conn->prepare('INSERT INTO stock_history (product_id,type,quantity,balance_after,note,source_type,source_id,user_id) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('isddssii',$product_id,$history_type,$stock_qty,$stock_qty,$history_note,$source_type,$product_id,$user_id);
    $stmt->execute();
    auditLog($conn,'CREATE','product',$product_id,'Created product '.$name,null,['stock_qty'=>$stock_qty]);
}



header("Location:index.php");

exit();



}


?>
