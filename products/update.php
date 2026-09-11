<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



if(isset($_POST['id'])){



$id = intval($_POST['id']);



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





mysqli_query($conn,$sql);



header("Location:index.php");

exit();



}


?>