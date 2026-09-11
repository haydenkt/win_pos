<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";



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




mysqli_query($conn,$sql);



header("Location:index.php");

exit();



}


?>