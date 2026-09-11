<?php

session_start();

if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


if(!isset($_GET['id'])){

    die("Invoice ID missing");

}


$id = intval($_GET['id']);

?>

<!DOCTYPE html>
<html>

<head>

<title>
Print Invoice
</title>


<link href="../assets/css/bootstrap.min.css" rel="stylesheet">


<style>

body{

    background:#f5f5f5;
    font-family:Arial;

}


.print-box{

    width:400px;
    margin:100px auto;
    background:white;
    padding:30px;
    text-align:center;
    border-radius:10px;
    box-shadow:0 0 15px #ccc;

}


h2{

    color:#1f4e79;

}


.btn{

    display:block;
    margin:15px 0;
    padding:15px;
    font-size:18px;
    text-decoration:none;
    border-radius:5px;

}


.a5{

    background:#1f4e79;
    color:white;

}


.a4{

    background:#198754;
    color:white;

}


.back{

    background:#6c757d;
    color:white;

}


</style>


</head>


<body>


<div class="print-box">


<h2>

<i class="fa fa-file-invoice"></i>

Print Invoice

</h2>



<p>

Choose paper size

</p>



<a class="btn a5"

href="print_a5.php?id=<?=$id;?>"

target="_blank">

🖨 Print A5 Invoice

</a>




<a class="btn a4"

href="print_a4.php?id=<?=$id;?>"

target="_blank">

🖨 Print A4 Invoice

</a>




<a class="btn back"

href="index.php">

Back

</a>



</div>



</body>

</html>