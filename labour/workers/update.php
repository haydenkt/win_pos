<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}


include "../../config/database.php";



if($_SERVER['REQUEST_METHOD']=="POST"){



    $id = $_POST['id'];

    $name = $_POST['name'];

    $phone = $_POST['phone'];

    $position = $_POST['position'];

    $daily_rate = $_POST['daily_rate'];

    $default_cash = $_POST['default_cash'];

    $default_saving = $_POST['default_saving'];

    $status = $_POST['status'];





    $sql = "

    UPDATE workers SET

    name=?,

    phone=?,

    position=?,

    daily_rate=?,

    default_cash=?,

    default_saving=?,

    status=?

    WHERE id=?

    ";





    $stmt = $conn->prepare($sql);



    if(!$stmt){

        die("Prepare failed: ".$conn->error);

    }





    $stmt->bind_param(

        "sssdddsi",

        $name,

        $phone,

        $position,

        $daily_rate,

        $default_cash,

        $default_saving,

        $status,

        $id

    );







    if($stmt->execute()){



        header("Location:index.php");

        exit();



    }else{


        die("Update failed: ".$stmt->error);


    }






}else{


    header("Location:index.php");

    exit();


}


?>