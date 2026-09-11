<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../../index.php");
    exit();

}


include "../../config/database.php";





if(!isset($_POST['survey_id'])){

    die("Survey ID missing");

}



$survey_id = intval($_POST['survey_id']);





$item_names = $_POST['item_name'] ?? [];

$widths = $_POST['width'] ?? [];

$heights = $_POST['height'] ?? [];

$quantities = $_POST['quantity'] ?? [];

$glass_types = $_POST['glass_type'] ?? [];

$frame_colors = $_POST['frame_color'] ?? [];

$opening_types = $_POST['opening_type'] ?? [];

$notes = $_POST['notes'] ?? [];





$count = count($item_names);





for($i=0; $i<$count; $i++){



    $item_name = mysqli_real_escape_string(
        $conn,
        $item_names[$i]
    );


    $width = floatval($widths[$i]);

    $height = floatval($heights[$i]);


    $quantity = intval($quantities[$i] ?? 1);



    $glass = mysqli_real_escape_string(
        $conn,
        $glass_types[$i] ?? ''
    );


    $color = mysqli_real_escape_string(
        $conn,
        $frame_colors[$i] ?? ''
    );


    $opening = mysqli_real_escape_string(
        $conn,
        $opening_types[$i] ?? ''
    );


    $note = mysqli_real_escape_string(
        $conn,
        $notes[$i] ?? ''
    );





    mysqli_query($conn,"

    INSERT INTO survey_measurements

    (

    survey_id,

    item_name,

    width,

    height,

    quantity,

    glass_type,

    frame_color,

    opening_type,

    notes

    )


    VALUES

    (

    '$survey_id',

    '$item_name',

    '$width',

    '$height',

    '$quantity',

    '$glass',

    '$color',

    '$opening',

    '$note'

    )


    ");



}






header("Location:../view.php?id=".$survey_id);

exit();


?>