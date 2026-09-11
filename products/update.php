<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";
require_once "../includes/audit.php";



if(isset($_POST['id'])){



$id = intval($_POST['id']);

$old_result = $conn->query("SELECT * FROM products WHERE id='$id' LIMIT 1");
$old_product = $old_result ? $old_result->fetch_assoc() : null;



$name = mysqli_real_escape_string($conn,$_POST['name']);

$category = mysqli_real_escape_string($conn,$_POST['category']);

$unit = mysqli_real_escape_string($conn,$_POST['unit']);



$purchase_price = floatval($_POST['purchase_price']);

$selling_price = floatval($_POST['selling_price']);

$stock_qty = floatval($_POST['stock_qty']);

$minimum_stock = floatval($_POST['minimum_stock']);





$sql = "

UPDATE products SET


name='$name',

category='$category',

unit='$unit',

purchase_price='$purchase_price',

selling_price='$selling_price',

stock_qty='$stock_qty',

minimum_stock='$minimum_stock'


WHERE id='$id'


";





if(mysqli_query($conn,$sql)){
    $old_stock = (float) ($old_product['stock_qty'] ?? 0);
    $difference = $stock_qty - $old_stock;
    if(abs($difference) > 0.0001){
        $history_type = $difference > 0 ? 'ADJUSTMENT IN' : 'ADJUSTMENT OUT';
        $history_note = 'Manual product stock adjustment';
        $source_type = 'PRODUCT';
        $user_id = (int) ($_SESSION['user_id'] ?? 0);
        $quantity = abs($difference);
        $stmt = $conn->prepare('INSERT INTO stock_history (product_id,type,quantity,balance_after,note,source_type,source_id,user_id) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->bind_param('isddssii',$id,$history_type,$quantity,$stock_qty,$history_note,$source_type,$id,$user_id);
        $stmt->execute();
    }
    auditLog($conn,'UPDATE','product',$id,'Updated product '.$name,$old_product,['name'=>$name,'stock_qty'=>$stock_qty]);
}



header("Location:index.php");

exit();



}


?>
