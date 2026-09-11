<?php

session_start();

if (!isset($_SESSION['user'])) {

    header("Location: ../index.php");
    exit();

}


include_once "../config/database.php";


$error = "";



/*
|--------------------------------------------------------------------------
| SAVE GLASS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] == 'POST') {


    $name = trim($_POST['name']);

    $price = $_POST['price_per_sqft'];

    $status = $_POST['status'];



    if ($name == "") {


        $error = "Glass name is required.";


    } else {


        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO glass_types
            (
                name,
                price_per_sqft,
                status
            )
            VALUES
            (?,?,?)"
        );



        mysqli_stmt_bind_param(
            $stmt,
            "sds",
            $name,
            $price,
            $status
        );



        if (mysqli_stmt_execute($stmt)) {


            header("Location: index.php");
            exit();


        } else {


            $error =
                "Database Error: "
                . mysqli_error($conn);


        }


    }


}



/*
|--------------------------------------------------------------------------
| PAGE DESIGN
|--------------------------------------------------------------------------
*/

include "../includes/header.php";
include "../includes/sidebar.php";

?>



<div class="container-fluid">



<div class="d-flex justify-content-between align-items-center mb-3">


<h2>

<i class="fa fa-window-maximize"></i>

Add Glass Type

</h2>



<a href="index.php" class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>


</div>





<div class="card">


<div class="card-header">

<strong>

<i class="fa fa-plus"></i>

New Glass

</strong>

</div>





<div class="card-body">



<?php if($error): ?>


<div class="alert alert-danger">

<?= htmlspecialchars($error); ?>

</div>


<?php endif; ?>





<form method="POST">



<div class="row mb-3">



<div class="col-md-6">


<label class="form-label">

Glass Name

</label>



<input

type="text"

name="name"

class="form-control"

placeholder="Example: Clear Glass 5mm"

required

>



</div>





<div class="col-md-6">


<label class="form-label">

Price Per Sqft

</label>



<input

type="number"

name="price_per_sqft"

class="form-control"

step="0.01"

min="0"

value="0"

required

>



</div>



</div>








<div class="row mb-3">



<div class="col-md-6">


<label class="form-label">

Status

</label>



<select

name="status"

class="form-control"

>


<option value="Active">

Active

</option>



<option value="Inactive">

Inactive

</option>


</select>



</div>



</div>








<div class="text-end">


<button

type="submit"

class="btn btn-success"

>


<i class="fa fa-save"></i>

Save Glass


</button>



</div>





</form>





</div>


</div>


</div>





<?php

include "../includes/footer.php";

?>