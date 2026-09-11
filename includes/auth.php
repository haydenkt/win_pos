<?php


function checkRole($roles=[]){


    if(!isset($_SESSION['role'])){

        header("Location:/index.php");
        exit();

    }



    if(!in_array($_SESSION['role'],$roles)){


        echo "<script>
        alert('You do not have permission to access this page');
        window.location.href='/dashboard.php';
        </script>";

        exit();


    }


}


?>