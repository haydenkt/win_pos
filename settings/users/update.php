<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";



// ========================================
// CHECK REQUEST
// ========================================

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    header("Location: index.php");
    exit();

}



// ========================================
// GET FORM DATA
// ========================================

$id = intval($_POST['id'] ?? 0);

$username = trim($_POST['username'] ?? '');

$full_name = trim($_POST['full_name'] ?? '');

$email = trim($_POST['email'] ?? '');

$phone = trim($_POST['phone'] ?? '');

$password = $_POST['password'] ?? '';

$role_id = intval($_POST['role_id'] ?? 0);

$status = $_POST['status'] ?? 'Active';





// ========================================
// VALIDATION
// ========================================

if($id <= 0){

    die("Invalid user.");

}


if($username === ''){

    die("Username is required.");

}


if($role_id <= 0){

    die("Please select a role.");

}


if(!in_array($status,['Active','Inactive'])){

    die("Invalid status.");

}





// ========================================
// CHECK USER
// ========================================

$sql = "

SELECT id, password

FROM users

WHERE id=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();



if(!$user){

    die("User not found.");

}





// ========================================
// CHECK ROLE
// ========================================

$sql = "

SELECT id

FROM roles

WHERE id=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param("i",$role_id);

$stmt->execute();

$result = $stmt->get_result();



if($result->num_rows === 0){

    die("Selected role does not exist.");

}





// ========================================
// CHECK DUPLICATE USERNAME
// ========================================

$sql = "

SELECT id

FROM users

WHERE username=?

AND id!=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param(

    "si",

    $username,

    $id

);

$stmt->execute();

$result = $stmt->get_result();



if($result->num_rows > 0){

    die("Username already exists.");

}





// ========================================
// UPDATE USER
// ========================================


// Password changed

if($password !== ''){



    $hashed_password = password_hash(

        $password,

        PASSWORD_DEFAULT

    );



    $sql = "

    UPDATE users

    SET

        username=?,

        full_name=?,

        email=?,

        phone=?,

        password=?,

        role_id=?,

        status=?

    WHERE id=?

    ";



    $stmt = $conn->prepare($sql);



    $stmt->bind_param(

        "sssssisi",

        $username,

        $full_name,

        $email,

        $phone,

        $hashed_password,

        $role_id,

        $status,

        $id

    );



}else{



    // Keep current password

    $sql = "

    UPDATE users

    SET

        username=?,

        full_name=?,

        email=?,

        phone=?,

        role_id=?,

        status=?

    WHERE id=?

    ";



    $stmt = $conn->prepare($sql);



    $stmt->bind_param(

        "ssssisi",

        $username,

        $full_name,

        $email,

        $phone,

        $role_id,

        $status,

        $id

    );

}



if(!$stmt->execute()){

    die(

        "Update failed: "

        .$stmt->error

    );

}





// ========================================
// SUCCESS
// ========================================

header(

    "Location: index.php?success="

    .urlencode("User updated successfully")

);

exit();

?>