<?php

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}


include_once "../config/database.php";
include "../includes/header.php";
include "../includes/sidebar.php";


if (!isset($_GET['id'])) {

    header("Location: index.php");
    exit();

}


$id = intval($_GET['id']);



$result = mysqli_query(
    $conn,
    "SELECT *
     FROM glass_types
     WHERE id=$id"
);



$glass = mysqli_fetch_assoc($result);



if (!$glass) {

    header("Location: index.php");
    exit();

}



$error = "";



if ($_SERVER['REQUEST_METHOD']=="POST") {


    $name =
    trim($_POST['name']);


    $price =
    $_POST['price_per_sqft'];


    $status =
    $_POST['status'];



    if ($name=="") {


        $error =
        "Glass name is required.";


    } else {



        $stmt =
        mysqli_prepare(
            $conn,
            "UPDATE glass_types
             SET
                name=?,
                price_per_sqft=?,
                status=?
             WHERE id=?"
        );



        mysqli_stmt_bind_param(
            $stmt,
            "sdsi",
            $name,
            $price,
            $status,
            $id
        );



        if(mysqli_stmt_execute($stmt)){


            header(
                "Location:index.php"
            );

            exit();


        }else{


            $error =
            mysqli_error($conn);


        }



    }



}



?>



<div class="container-fluid">


<div class="d-flex justify-content-between align-items-center mb-3">


<h2>

<i class="fa fa-edit"></i>

Edit Glass Type

</h2>



<a href="index.php" class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>




<div class="card">


<div class="card-header">

<strong>

Update Glass

</strong>

</div>



<div class="card-body">


<?php if($error): ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php endif; ?>




<form method="POST">



<div class="row mb-3">


<div class="col-md-6">


<label>

Glass Name

</label>


<input

type="text"

name="name"

class="form-control"

value="<?= htmlspecialchars($glass['name']) ?>"

required

>


</div>





<div class="col-md-6">


<label>

Price Per Sqft

</label>


<input

type="number"

name="price_per_sqft"

class="form-control"

step="0.01"

value="<?= $glass['price_per_sqft'] ?>"

>


</div>


</div>





<div class="row mb-3">


<div class="col-md-6">


<label>

Status

</label>


<select

name="status"

class="form-control"

>


<option

value="Active"

<?= $glass['status']=="Active"?'selected':'' ?>

>

Active

</option>



<option

value="Inactive"

<?= $glass['status']=="Inactive"?'selected':'' ?>

>

Inactive

</option>


</select>


</div>


</div>





<div class="text-end">


<button class="btn btn-success">


<i class="fa fa-save"></i>

Update Glass


</button>


</div>



</form>



</div>


</div>


</div>




<?php

include "../includes/footer.php";

?>