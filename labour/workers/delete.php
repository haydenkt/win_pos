<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location:/index.php");
    exit();
}

include "../../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location:index.php");
    exit();
}

$id = (int)$_GET['id'];


// Check labour records
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM labour_records
    WHERE worker_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$record_count = $stmt->get_result()->fetch_assoc()['total'];


// Check transactions
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM labour_transactions
    WHERE worker_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$transaction_count = $stmt->get_result()->fetch_assoc()['total'];


if ($record_count > 0 || $transaction_count > 0) {

    echo "
    <script>
        alert('This worker cannot be deleted because labour records or transactions already exist.');
        window.location='index.php';
    </script>
    ";
    exit();
}


// Safe to delete
$stmt = $conn->prepare("
    DELETE FROM workers
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    header("Location:index.php?success=deleted");
    exit();

} else {

    echo "
    <script>
        alert('Unable to delete worker.');
        window.location='index.php';
    </script>
    ";
}
?>