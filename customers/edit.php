<?php

session_start();

if(!isset($_SESSION['user'])){
    header("Location:../index.php");
    exit();
}

include_once "../config/database.php";

include "../includes/header.php";
include "../includes/sidebar.php";


if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$id = $_GET['id'];



$result = mysqli_query($conn,"
    SELECT *
    FROM customers
    WHERE id='$id'
");


$customer = mysqli_fetch_assoc($result);



if(!$customer){

    echo "Customer not found";
    exit();

}

?>



<div class="container-fluid">


<h2 class="mb-4">

<i class="fa fa-user-edit"></i>
Edit Customer

</h2>



<div class="card">

<div class="card-body">


<form method="POST" action="update.php">


<input type="hidden" 
name="id" 
value="<?= $customer['id']; ?>">



<div class="mb-3">

<label>Name</label>

<input type="text"
name="name"
class="form-control"
value="<?= $customer['name']; ?>"
required>

</div>




<div class="mb-3">

<label>Phone</label>

<input type="text"
name="phone"
class="form-control"
value="<?= $customer['phone']; ?>">

</div>

<div class="mb-3">

<label>Address</label>

<textarea 
name="address"
class="form-control"
rows="4"><?= $customer['address']; ?></textarea>

</div>




<button type="submit"
class="btn btn-success">

<i class="fa fa-save"></i>
Update Customer

</button>



<a href="index.php"
class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>
Back

</a>


</form>


</div>

</div>


</div>



<?php include "../includes/footer.php"; ?>