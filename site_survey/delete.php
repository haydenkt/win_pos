<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";





if(!isset($_GET['id'])){

    die("Survey ID missing");

}



$id=intval($_GET['id']);






// DELETE MEASUREMENTS FIRST

$delete_measurements=mysqli_query($conn,"

DELETE FROM survey_measurements

WHERE survey_id='$id'

");



if(!$delete_measurements){

    die("Delete measurements failed: ".mysqli_error($conn));

}






// DELETE SURVEY

$delete_survey=mysqli_query($conn,"

DELETE FROM site_surveys

WHERE id='$id'

");



if(!$delete_survey){

    die("Delete survey failed: ".mysqli_error($conn));

}







header("Location:index.php");

exit();



?>