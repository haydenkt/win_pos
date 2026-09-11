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

    die("Survey ID missing");

}



$id=intval($_POST['id']);





$customer_id=intval($_POST['customer_id']);



$survey_date=mysqli_real_escape_string(
    $conn,
    $_POST['survey_date']
);



$address=mysqli_real_escape_string(
    $conn,
    $_POST['address']
);



$status=mysqli_real_escape_string(
    $conn,
    $_POST['status']
);



$notes=mysqli_real_escape_string(
    $conn,
    $_POST['notes']
);








$query=mysqli_query($conn,"

UPDATE site_surveys

SET


customer_id='$customer_id',

survey_date='$survey_date',

address='$address',

status='$status',

notes='$notes'


WHERE id='$id'


");







if(!$query){

    die("Update failed: ".mysqli_error($conn));

}






header("Location:view.php?id=".$id);

exit();


?>