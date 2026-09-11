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
// GET DATA
// ========================================

$id = intval($_POST['id'] ?? 0);

$role_name = trim($_POST['role_name'] ?? '');

$permissions = $_POST['permissions'] ?? [];





// ========================================
// VALIDATION
// ========================================

if($id <= 0){

    die("Invalid role.");

}


if($role_name === ''){

    die("Role name is required.");

}





// ========================================
// CHECK ROLE EXISTS
// ========================================

$sql = "

SELECT id

FROM roles

WHERE id=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();



if($result->num_rows === 0){

    die("Role not found.");

}





// ========================================
// CHECK DUPLICATE ROLE NAME
// ========================================

$sql = "

SELECT id

FROM roles

WHERE role_name=?

AND id!=?

LIMIT 1

";


$stmt = $conn->prepare($sql);

$stmt->bind_param(

    "si",

    $role_name,

    $id

);

$stmt->execute();

$result = $stmt->get_result();



if($result->num_rows > 0){

    die("Another role already uses this name.");

}





// ========================================
// START TRANSACTION
// ========================================

$conn->begin_transaction();



try {



    // ========================================
    // UPDATE ROLE NAME
    // ========================================

    $sql = "

    UPDATE roles

    SET role_name=?

    WHERE id=?

    ";



    $stmt = $conn->prepare($sql);

    $stmt->bind_param(

        "si",

        $role_name,

        $id

    );



    $stmt->execute();





    // ========================================
    // REMOVE OLD PERMISSIONS
    // ========================================

    $sql = "

    DELETE FROM role_permissions

    WHERE role_id=?

    ";



    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i",$id);

    $stmt->execute();





    // ========================================
    // ADD NEW PERMISSIONS
    // ========================================

    if(!empty($permissions)){



        $sql = "

        INSERT INTO role_permissions

        (

            role_id,

            permission_id

        )

        VALUES (?,?)

        ";



        $permission_stmt = $conn->prepare($sql);



        foreach($permissions as $permission_id){



            $permission_id = intval($permission_id);



            if($permission_id <= 0){

                continue;

            }



            $permission_stmt->bind_param(

                "ii",

                $id,

                $permission_id

            );



            $permission_stmt->execute();

        }

    }





    // ========================================
    // COMMIT
    // ========================================

    $conn->commit();



    header(

        "Location: index.php?success="

        .urlencode("Role updated successfully")

    );

    exit();



} catch(Exception $e) {



    // Undo changes

    $conn->rollback();



    die(

        "Error updating role: "

        .$e->getMessage()

    );

}

?>