<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:/index.php");
    exit();

}

include "../../config/database.php";



if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    header("Location: index.php");
    exit();

}



// Get role name

$role_name = trim($_POST['role_name'] ?? '');



// Get selected permissions

$permissions = $_POST['permissions'] ?? [];





// Validate role name

if($role_name === ''){

    die("Role name is required.");

}



// Check duplicate role

$check_sql = "

SELECT id

FROM roles

WHERE role_name = ?

LIMIT 1

";



$stmt = $conn->prepare($check_sql);

$stmt->bind_param("s", $role_name);

$stmt->execute();

$check_result = $stmt->get_result();



if($check_result->num_rows > 0){

    die("A role with this name already exists.");

}





// Start transaction

$conn->begin_transaction();



try {



    // ========================================
    // INSERT ROLE
    // ========================================

    $sql = "

    INSERT INTO roles (role_name)

    VALUES (?)

    ";



    $stmt = $conn->prepare($sql);

    $stmt->bind_param("s", $role_name);

    $stmt->execute();



    $role_id = $conn->insert_id;





    // ========================================
    // INSERT PERMISSIONS
    // ========================================

    if(!empty($permissions)){



        $permission_sql = "

        INSERT INTO role_permissions

        (role_id, permission_id)

        VALUES (?,?)

        ";



        $permission_stmt = $conn->prepare($permission_sql);



        foreach($permissions as $permission_id){



            $permission_id = intval($permission_id);



            if($permission_id <= 0){

                continue;

            }



            $permission_stmt->bind_param(

                "ii",

                $role_id,

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

        .urlencode("Role created successfully")

    );

    exit();



} catch(Exception $e) {



    // Undo everything if something fails

    $conn->rollback();



    die(

        "Error creating role: "

        .$e->getMessage()

    );

}

?>