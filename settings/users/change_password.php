<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../../index.php");
    exit();

}


include "../../config/database.php";



// CHECK ID

if(!isset($_GET['id'])){

    header("Location:index.php");
    exit();

}


$id = intval($_GET['id']);




// GET USER

$result=mysqli_query($conn,"

SELECT *

FROM users

WHERE id='$id'

");


$user=mysqli_fetch_assoc($result);



if(!$user){

    die("User not found");

}





// UPDATE PASSWORD

if(isset($_POST['update'])){


    $password = $_POST['password'];


    if($password!=""){


        $hash = password_hash(

            $password,

            PASSWORD_DEFAULT

        );



        mysqli_query($conn,"

        UPDATE users SET

        password='$hash'

        WHERE id='$id'

        ");



        $_SESSION['success']="Password changed successfully";


        header("Location:index.php");

        exit();


    }


}





include "../../includes/header.php";

include "../../includes/sidebar.php";


?>





<div class="container-fluid">


<h2>

<i class="fa fa-key"></i>

Change Password

</h2>





<div class="card shadow mt-3">


<div class="card-body">



<form method="POST">



<div class="mb-3">


<label>

User

</label>


<input type="text"

class="form-control"

value="<?=$user['username'];?>"

readonly>


</div>





<div class="mb-3">


<label>

New Password

</label>


<input type="password"

name="password"

class="form-control"

required>


</div>





<button type="submit"

name="update"

class="btn btn-primary">


<i class="fa fa-save"></i>

Update Password


</button>




<a href="index.php"

class="btn btn-secondary">

Cancel

</a>



</form>


</div>


</div>


</div>






<?php

include "../../includes/footer.php";

?>