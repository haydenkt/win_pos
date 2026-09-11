<?php

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";

include "../includes/header.php";
include "../includes/sidebar.php";




// SAVE SETTINGS

if(isset($_POST['save'])){


    function updateSetting($conn,$key,$value){


        $key = mysqli_real_escape_string($conn,$key);

        $value = mysqli_real_escape_string($conn,$value);


        mysqli_query($conn,"

            INSERT INTO settings

            (setting_group, setting_key, setting_value)

            VALUES

            ('invoice','$key','$value')


            ON DUPLICATE KEY UPDATE

            setting_value='$value'

        ");

    }



    updateSetting(
        $conn,
        "title",
        $_POST['title']
    );


    updateSetting(
        $conn,
        "number_prefix",
        $_POST['number_prefix']
    );


    updateSetting(
        $conn,
        "footer_text",
        $_POST['footer_text']
    );


    updateSetting(
        $conn,
        "warranty_text",
        $_POST['warranty_text']
    );


    updateSetting(
        $conn,
        "show_logo",
        $_POST['show_logo']
    );


    updateSetting(
        $conn,
        "show_notes",
        $_POST['show_notes']
    );


    updateSetting(
        $conn,
        "paper_size",
        $_POST['paper_size']
    );



    echo "

    <script>

    alert('Invoice settings updated');

    window.location='invoice.php';

    </script>

    ";

}




// GET SETTINGS


function getInvoiceSetting($conn,$key){


    $key=mysqli_real_escape_string($conn,$key);


    $query=mysqli_query($conn,"

        SELECT setting_value

        FROM settings

        WHERE setting_group='invoice'

        AND setting_key='$key'

        LIMIT 1

    ");


    if($row=mysqli_fetch_assoc($query)){

        return $row['setting_value'];

    }


    return "";

}



$title = getInvoiceSetting($conn,'title');

$number_prefix = getInvoiceSetting($conn,'number_prefix');

$footer_text = getInvoiceSetting($conn,'footer_text');

$warranty_text = getInvoiceSetting($conn,'warranty_text');

$show_logo = getInvoiceSetting($conn,'show_logo');

$show_notes = getInvoiceSetting($conn,'show_notes');

$paper_size = getInvoiceSetting($conn,'paper_size');



?>



<div class="container-fluid">


<h2 class="mb-4">

<i class="fa fa-file-text"></i>

Invoice Settings

</h2>




<div class="card">


<div class="card-body">



<form method="POST">





<div class="mb-3">

<label>
Invoice Title
</label>


<input type="text"
name="title"
class="form-control"
value="<?=htmlspecialchars($title);?>">

</div>






<div class="mb-3">

<label>
Invoice Number Prefix
</label>


<input type="text"
name="number_prefix"
class="form-control"
value="<?=htmlspecialchars($number_prefix);?>">

</div>






<div class="mb-3">

<label>
Footer Text
</label>


<textarea
name="footer_text"
class="form-control"
rows="3"><?=htmlspecialchars($footer_text);?></textarea>


</div>






<div class="mb-3">

<label>
Warranty Text
</label>


<textarea
name="warranty_text"
class="form-control"
rows="3"><?=htmlspecialchars($warranty_text);?></textarea>


</div>






<div class="mb-3">

<label>
Show Company Logo
</label>


<select name="show_logo"
class="form-control">


<option value="Yes"
<?=($show_logo=="Yes")?'selected':'';?>>

Yes

</option>


<option value="No"
<?=($show_logo=="No")?'selected':'';?>>

No

</option>


</select>


</div>






<div class="mb-3">

<label>
Show Notes
</label>


<select name="show_notes"
class="form-control">


<option value="Yes"
<?=($show_notes=="Yes")?'selected':'';?>>

Yes

</option>


<option value="No"
<?=($show_notes=="No")?'selected':'';?>>

No

</option>


</select>


</div>







<div class="mb-3">

<label>
Default Paper Size
</label>


<select name="paper_size"
class="form-control">


<option value="A5"
<?=($paper_size=="A5")?'selected':'';?>>

A5 Portrait

</option>


<option value="A4"
<?=($paper_size=="A4")?'selected':'';?>>

A4 Portrait

</option>


</select>


</div>






<button type="submit"
name="save"
class="btn btn-success">


<i class="fa fa-save"></i>

Save Invoice Settings


</button>



<a href="../dashboard.php"
class="btn btn-secondary">

Back

</a>



</form>



</div>


</div>



</div>



<?php include "../includes/footer.php"; ?>