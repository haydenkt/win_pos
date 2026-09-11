<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();


if(!isset($_SESSION['user'])){

    header("Location:../index.php");
    exit();

}


include "../config/database.php";




// SAVE SETTINGS FUNCTION

function saveSetting($conn,$group,$key,$value){

    $group = mysqli_real_escape_string($conn,$group);
    $key   = mysqli_real_escape_string($conn,$key);
    $value = mysqli_real_escape_string($conn,$value);


    $check = mysqli_query($conn,"

        SELECT id 
        FROM settings

        WHERE setting_group='$group'

        AND setting_key='$key'

    ");



    if(mysqli_num_rows($check)>0){


        mysqli_query($conn,"

            UPDATE settings SET

            setting_value='$value',

            updated_at=NOW()

            WHERE setting_group='$group'

            AND setting_key='$key'

        ");


    }
    else{


        mysqli_query($conn,"

            INSERT INTO settings

            (
                setting_group,
                setting_key,
                setting_value
            )

            VALUES

            (
                '$group',
                '$key',
                '$value'
            )

        ");


    }

}






// RESIZE LOGO FUNCTION

function resizeLogo($source,$destination){


    // Check file size first
    // max 5MB

    if(filesize($source) > 5242880){

        return false;

    }



    $info = @getimagesize($source);


    if(!$info){

        return false;

    }



    $width  = $info[0];

    $height = $info[1];

    $type   = $info[2];




    // Block very large images

    if($width > 4000 || $height > 4000){

        return false;

    }




    if($type == IMAGETYPE_JPEG){

        $image = imagecreatefromjpeg($source);

    }

    elseif($type == IMAGETYPE_PNG){

        $image = imagecreatefrompng($source);

    }

    else{

        return false;

    }





    $maxWidth = 300;

    $maxHeight = 300;



    $ratio = min(

        $maxWidth/$width,

        $maxHeight/$height

    );



    $newWidth = max(1,round($width*$ratio));

    $newHeight = max(1,round($height*$ratio));





    $newImage = imagecreatetruecolor(

        $newWidth,

        $newHeight

    );





    if($type == IMAGETYPE_PNG){

        imagealphablending($newImage,false);

        imagesavealpha($newImage,true);

    }





    imagecopyresampled(

        $newImage,

        $image,

        0,
        0,
        0,
        0,

        $newWidth,
        $newHeight,

        $width,
        $height

    );






    if($type == IMAGETYPE_JPEG){


        imagejpeg(

            $newImage,

            $destination,

            80

        );


    }
    else{


        imagepng(

            $newImage,

            $destination,

            7

        );


    }





    imagedestroy($image);

    imagedestroy($newImage);



    return true;


}






// COMPANY SAVE

if(isset($_POST['company_save'])){



    $name = $_POST['company_name'];

    $address = $_POST['address'];

    $phone = $_POST['phone'];

    $email = $_POST['email'];





    saveSetting(
        $conn,
        'company',
        'company_name',
        $name
    );


    saveSetting(
        $conn,
        'company',
        'address',
        $address
    );


    saveSetting(
        $conn,
        'company',
        'phone',
        $phone
    );


    saveSetting(
        $conn,
        'company',
        'email',
        $email
    );








    // LOGO UPLOAD

    if(isset($_FILES['logo']) && $_FILES['logo']['name']!=""){



        $folder="../uploads/logo/";



        if(!is_dir($folder)){

            mkdir($folder,0777,true);

        }




        $ext = strtolower(

            pathinfo(

                $_FILES['logo']['name'],

                PATHINFO_EXTENSION

            )

        );





        if(in_array($ext,['jpg','jpeg','png'])){



            $file_name = time()."_logo.".$ext;



            $target = $folder.$file_name;





            if(resizeLogo(

                $_FILES['logo']['tmp_name'],

                $target

            )){


                saveSetting(

                    $conn,

                    'company',

                    'logo',

                    $file_name

                );


            }
            else{


                die("Logo is too large. Please resize it first.");


            }



        }



    }






    echo "

    <script>

    alert('Company Settings Saved');

    window.location='company.php';

    </script>

    ";


}


?>