<?php

session_start();

if(!isset($_SESSION['user'])){
    header("Location:../index.php");
    exit();
}

include "../config/database.php";

include "../includes/header.php";
include "../includes/sidebar.php";

?>

<div class="container-fluid">


<h2 class="mb-4">
<i class="fa fa-cog"></i>
Settings
</h2>



<div class="row">


<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<i class="fa fa-building fa-3x mb-3"></i>

<h5>
Company Settings
</h5>


<a href="company.php" 
class="btn btn-primary">

Open

</a>


</div>

</div>

</div>





<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<i class="fa fa-file-text fa-3x mb-3"></i>

<h5>
Invoice Settings
</h5>


<a href="invoice.php" 
class="btn btn-primary">

Open

</a>


</div>

</div>

</div>





<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<i class="fa fa-users fa-3x mb-3"></i>

<h5>
User Settings
</h5>

<?php if($_SESSION['role']=="admin"){ ?>

<a href="./users/index.php" 
class="btn btn-primary">

Open

</a>

<?php } ?>


</div>

</div>

</div>





<div class="col-md-3">

<div class="card text-center">

<div class="card-body">

<i class="fa fa-cogs fa-3x mb-3"></i>

<h5>
System Settings
</h5>


<a href="system.php" 
class="btn btn-primary">

Open

</a>


</div>

</div>

</div>



</div>


</div>


<?php include "../includes/footer.php"; ?>