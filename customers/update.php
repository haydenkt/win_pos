<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include_once "../config/database.php";
require_once "../includes/audit.php";



if(isset($_POST['id'])){



$id = intval($_POST['id']);

$old_stmt = $conn->prepare('SELECT * FROM customers WHERE id=? LIMIT 1');
$old_stmt->bind_param('i',$id);
$old_stmt->execute();
$old_customer = $old_stmt->get_result()->fetch_assoc();



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

    auditLog($conn,'UPDATE','customer',$id,'Updated customer '.$name,$old_customer,['name'=>$name,'phone'=>$phone,'address'=>$address]);


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
