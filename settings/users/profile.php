<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../../index.php");
    exit();

}


include "../../config/database.php";




// CURRENT USER

$username = $_SESSION['user'];



$result = mysqli_query($conn,"

SELECT *

FROM users

WHERE username='$username'

LIMIT 1

");


$user = mysqli_fetch_assoc($result);



if(!$user){

    die("User not found");

}





// UPDATE PROFILE

if(isset($_POST['update'])){


    $full_name = mysqli_real_escape_string(
        $conn,
        $_POST['full_name']
    );


    $email = mysqli_real_escape_string(
        $conn,
        $_POST['email']
    );


    $phone = mysqli_real_escape_string(
        $conn,
        $_POST['phone']
    );



    $photo = $user['photo'];





    if(isset($_FILES['photo']) && $_FILES['photo']['name']!=""){


        $new_photo = time()."_".$_FILES['photo']['name'];



        move_uploaded_file(

            $_FILES['photo']['tmp_name'],

            "../../uploads/users/".$new_photo

        );



        if(
            $photo!="" &&
            file_exists("../../uploads/users/".$photo)
        ){

            unlink("../../uploads/users/".$photo);

        }



        $photo=$new_photo;


    }






    mysqli_query($conn,"

    UPDATE users SET

    full_name='$full_name',

    email='$email',

    phone='$phone',

    photo='$photo'


    WHERE id='".$user['id']."'


    ");




    $_SESSION['success']="Profile updated successfully";


    header("Location:profile.php");

    exit();


}





include "../../includes/header.php";

include "../../includes/sidebar.php";


?>



<div class="container-fluid">


<h2>

<i class="fa fa-user"></i>

My Profile

</h2>




<div class="card shadow mt-3">


<div class="card-body">



<form method="POST" enctype="multipart/form-data">



<div class="row">



<div class="col-md-4 text-center">


<?php if($user['photo']!=""){ ?>


<img src="../../uploads/users/<?=$user['photo'];?>"

width="150"

height="150"

style="object-fit:cover;border-radius:50%;">

<?php }else{ ?>


<i class="fa fa-user-circle fa-6x"></i>


<?php } ?>


<br><br>


<input type="file"

name="photo"

class="form-control">


</div>





<div class="col-md-8">



<div class="mb-3">

<label>
Username
</label>


<input type="text"

class="form-control"

value="<?=$user['username'];?>"

readonly>


</div>





<div class="mb-3">

<label>
Full Name
</label>


<input type="text"

name="full_name"

class="form-control"

value="<?=$user['full_name'];?>"

required>


</div>






<div class="mb-3">

<label>
Email
</label>


<input type="email"

name="email"

class="form-control"

value="<?=$user['email'];?>">


</div>






<div class="mb-3">

<label>
Phone
</label>


<input type="text"

name="phone"

class="form-control"

value="<?=$user['phone'];?>">


</div>





<button type="submit"

name="update"

class="btn btn-primary">


<i class="fa fa-save"></i>

Update Profile


</button>



<a href="change_password.php?id=<?=$user['id'];?>"

class="btn btn-warning">


<i class="fa fa-key"></i>

Change Password


</a>



</div>


</div>



</form>


</div>


</div>


</div>




<?php

include "../../includes/footer.php";

?>