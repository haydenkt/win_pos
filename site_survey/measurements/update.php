<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../../index.php");
    exit();

}


include "../../config/database.php";





if(!isset($_POST['id'])){

    die("Measurement ID missing");

}




$id=intval($_POST['id']);

$survey_id=intval($_POST['survey_id']);





$item_name=mysqli_real_escape_string(
    $conn,
    $_POST['item_name']
);



$width=floatval($_POST['width']);

$height=floatval($_POST['height']);




$glass_type=mysqli_real_escape_string(
    $conn,
    $_POST['glass_type']
);



$frame_color=mysqli_real_escape_string(
    $conn,
    $_POST['frame_color']
);



$opening_type=mysqli_real_escape_string(
    $conn,
    $_POST['opening_type']
);



$notes=mysqli_real_escape_string(
    $conn,
    $_POST['notes']
);







$query=mysqli_query($conn,"

UPDATE survey_measurements

SET

item_name='$item_name',

width='$width',

height='$height',

glass_type='$glass_type',

frame_color='$frame_color',

opening_type='$opening_type',

notes='$notes'


WHERE id='$id'


");





if(!$query){

    die("Update failed: ".mysqli_error($conn));

}





header("Location:../view.php?id=".$survey_id);

exit();


?>