<?php

session_start();

if(!isset($_SESSION['user'])){
    header("Location:../index.php");
    exit();
}

include_once "../config/database.php";
require_once "../includes/audit.php";


if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$id = $_GET['id'];

$old_stmt = $conn->prepare('SELECT * FROM customers WHERE id=? LIMIT 1');
$old_stmt->bind_param('i',$id);
$old_stmt->execute();
$old_customer = $old_stmt->get_result()->fetch_assoc();


// Check customer orders first

$check = $conn->prepare("
    SELECT id 
    FROM orders 
    WHERE customer_id=?
");

$check->bind_param("i",$id);

$check->execute();

$result = $check->get_result();


if($result->num_rows > 0){

    echo "
    <script src='/assets/js/pos-dialog.js'></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
 await posDialog.alert('Warning: This customer has existing orders and cannot be deleted.');
 window.location.href='index.php';
});
</script>
    ";

    exit();

}



// Delete customer

$stmt = $conn->prepare("
    DELETE FROM customers
    WHERE id=?
");


$stmt->bind_param(
    "i",
    $id
);



if($stmt->execute()){

    auditLog($conn,'DELETE','customer',$id,'Deleted customer '.($old_customer['name']??('#'.$id)),$old_customer,null);

    echo "
    <script src='/assets/js/pos-dialog.js'></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
 await posDialog.alert('Customer Deleted Successfully');
 window.location.href='index.php';
});
</script>
    ";

}
else{

    echo "
    <script src='/assets/js/pos-dialog.js'></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
 await posDialog.alert('Delete Failed');
 window.location.href='index.php';
});
</script>
    ";

}

?>
