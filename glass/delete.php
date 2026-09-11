<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}


include_once "../config/database.php";


if (!isset($_GET['id'])) {

    header("Location: index.php");
    exit();

}


$id = intval($_GET['id']);



/*
|--------------------------------------------------------------------------
| CHECK IF GLASS IS USED IN INVOICES
|--------------------------------------------------------------------------
*/

$check = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM invoice_items
     WHERE glass_type_id = $id"
);



if ($check) {

    $row = mysqli_fetch_assoc($check);


    if ($row['total'] > 0) {


        /*
        If already used,
        do not delete.
        */

        mysqli_query(
            $conn,
            "UPDATE glass_types
             SET status='Inactive'
             WHERE id=$id"
        );


    } else {


        mysqli_query(
            $conn,
            "DELETE FROM glass_types
             WHERE id=$id"
        );


    }

}



header(
    "Location: index.php"
);

exit();

?>