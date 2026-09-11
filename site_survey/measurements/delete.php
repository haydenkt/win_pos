<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../../index.php");
    exit();

}


include "../../config/database.php";





if(!isset($_GET['id'])){

    die("Measurement ID missing");

}



$id=intval($_GET['id']);





if(!isset($_GET['survey_id'])){

    die("Survey ID missing");

}



$survey_id=intval($_GET['survey_id']);






$query=mysqli_query($conn,"

DELETE FROM survey_measurements

WHERE id='$id'

");





if(!$query){

    die("Delete failed: ".mysqli_error($conn));

}





header("Location:../view.php?id=".$survey_id);

exit();



?>