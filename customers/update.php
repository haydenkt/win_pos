<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include_once "../config/database.php";



if(isset($_POST['id'])){



$id = intval($_POST['id']);



$name = mysqli_real_escape_string(
    $conn,
    $_POST['name']
);



$phone = mysqli_real_escape_string(
    $conn,
    $_POST['phone']
);



$address = mysqli_real_escape_string(
    $conn,
    $_POST['address']
);





$sql = "

UPDATE customers SET

name='$name',

phone='$phone',

address='$address'

WHERE id='$id'

";





if(mysqli_query($conn,$sql)){


    header("Location:index.php");
    exit();



}else{


    echo "Update Error: ".mysqli_error($conn);


}



}else{


header("Location:index.php");
exit();


}


?>