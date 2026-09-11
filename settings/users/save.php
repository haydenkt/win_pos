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

if($username === ''){

    die("Username is required.");

}


if($password === ''){

    die("Password is required.");

}


if($role_id <= 0){

    die("Please select a role.");

}


if(!in_array($status,['Active','Inactive'])){

    die("Invalid status.");

}





// ========================================
// CHECK ROLE
// ========================================

$sql = "

SELECT id, role_name

FROM roles

WHERE id=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param("i",$role_id);

$stmt->execute();

$result = $stmt->get_result();

$role = $result->fetch_assoc();



if(!$role){

    die("Selected role does not exist.");

}





// ========================================
// CHECK USERNAME
// ========================================

$sql = "

SELECT id

FROM users

WHERE username=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param("s",$username);

$stmt->execute();

$result = $stmt->get_result();



if($result->num_rows > 0){

    die("Username already exists.");

}





// ========================================
// HASH PASSWORD
// ========================================

$hashed_password = password_hash(

    $password,

    PASSWORD_DEFAULT

);





// ========================================
// INSERT USER
// ========================================

$sql = "

INSERT INTO users

(

    username,

    full_name,

    email,

    phone,

    password,

    role_id,

    status

)

VALUES (?,?,?,?,?,?,?)

";



$stmt = $conn->prepare($sql);



if(!$stmt){

    die(

        "Prepare failed: "

        .$conn->error

    );

}



$stmt->bind_param(

    "sssssis",

    $username,

    $full_name,

    $email,

    $phone,

    $hashed_password,

    $role_id,

    $status

);



if(!$stmt->execute()){

    die(

        "Insert failed: "

        .$stmt->error

    );

}





// ========================================
// SUCCESS
// ========================================

header(

    "Location: index.php?success="

    .urlencode("User created successfully")

);

exit();

?>