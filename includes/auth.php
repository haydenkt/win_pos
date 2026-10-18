<?php


function checkRole($roles=[]){


    if(!isset($_SESSION['role'])){

        header("Location:/index.php");
        exit();

    }



    if(!in_array($_SESSION['role'],$roles)){


        echo "<script src='/assets/js/pos-dialog.js'></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
 await posDialog.alert('You do not have permission to access this page');
 window.location.href='/dashboard.php';
});
</script>";

        exit();


    }


}


?>
