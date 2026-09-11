<?php

session_start();

if(!isset($_SESSION['user'])){
    header("Location:../index.php");
    exit();
}

include_once "../config/database.php";

if(isset($_POST['save'])){

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $sql = "INSERT INTO customers(name, phone, address)
            VALUES('$name','$phone','$address')";

    if($conn->query($sql)){
        header("Location: index.php?success=1");
        exit();
    }else{
        $error = "Failed to save customer.";
    }
}

include "../includes/header.php";
include "../includes/sidebar.php";

?>

<div class="container-fluid">

    <div class="card">

        <div class="card-header">
            <h3><i class="fa fa-user-plus"></i> Add Customer</h3>
        </div>

        <div class="card-body">

            <?php if(isset($error)){ ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
            <?php } ?>

            <form method="POST">

                <div class="mb-3">
                    <label>Customer Name</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        required>
                </div>

                <div class="mb-3">
                    <label>Phone</label>
                    <input
                        type="text"
                        name="phone"
                        class="form-control">
                </div>

                <div class="mb-3">
                    <label>Address</label>
                    <textarea
                        name="address"
                        class="form-control"
                        rows="4"></textarea>
                </div>

                <button
                    class="btn btn-success"
                    name="save">

                    <i class="fa fa-save"></i>
                    Save Customer

                </button>

                <a href="index.php"
                   class="btn btn-secondary">

                    Back

                </a>

            </form>

        </div>

    </div>

</div>

<?php include "../includes/footer.php"; ?>
