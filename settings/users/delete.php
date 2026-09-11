<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../../index.php");
    exit();

}


include "../../config/database.php";



// CHECK ID

if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$id = intval($_GET['id']);




// CHECK USER EXISTS

$result = mysqli_query($conn,"

SELECT *

FROM users

WHERE id='$id'

");


$user = mysqli_fetch_assoc($result);



if(!$user){

    header("Location:index.php");
    exit();

}





// PREVENT DELETE LAST ADMIN


if($user['role']=="admin"){


    $admin_check = mysqli_query($conn,"

    SELECT COUNT(*) AS total

    FROM users

    WHERE role='admin'

    AND status='Active'

    ");


    $admin = mysqli_fetch_assoc($admin_check);



    if($admin['total'] <= 1){


        $_SESSION['error'] = "Cannot delete the last active admin user";


        header("Location:index.php");

        exit();


    }


}







// DELETE PHOTO


if($user['photo']!=""){


    $photo_path="../../uploads/users/".$user['photo'];


    if(file_exists($photo_path)){


        unlink($photo_path);


    }


}







// DELETE USER


mysqli_query($conn,"

DELETE FROM users

WHERE id='$id'

");





$_SESSION['success']="User deleted successfully";



header("Location:index.php");

exit();


?>