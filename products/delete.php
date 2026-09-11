<?php

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



$id = intval($_GET['id']);




// Delete product

mysqli_query($conn,

"DELETE FROM products
WHERE id='$id'"

);




// Return to product list

header("Location:index.php");

exit();


?>