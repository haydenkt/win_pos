<?php

session_start();

include "../config/database.php";



$username = mysqli_real_escape_string(
    $conn,
    $_POST['username']
);


$password = $_POST['password'];




// GET USER

$sql = "

SELECT *

FROM users

WHERE username='$username'

LIMIT 1

";



$result = mysqli_query($conn,$sql);



if(mysqli_num_rows($result)>0){


    $user = mysqli_fetch_assoc($result);



    // CHECK STATUS

    if($user['status']!="Active"){


        echo "Account is inactive";

        exit();


    }




    // CHECK PASSWORD

    if(password_verify($password,$user['password'])){



        $_SESSION['user'] = $user['username'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['role_id'] = $user['role_id'];


// ========================================
// REDIRECT USER TO FIRST ALLOWED PAGE
// ========================================

// Admin
if ($user['role_id'] == 1) {

    header("Location: ../dashboard.php");
    exit();

}


// Site Survey
if ($user['role_id'] == 2) {

    header("Location: ../site_survey/index.php");
    exit();

}


// Sales
if ($user['role_id'] == 3) {

    header("Location: ../invoices/index.php");
    exit();

}


// Fallback
header("Location: ../dashboard.php");
exit();



    }



}




echo "Login Failed";


?>