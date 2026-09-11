<?php


function getInvoiceSetting($conn,$key)
{

    $key = mysqli_real_escape_string($conn,$key);


    $sql = "

    SELECT setting_value

    FROM settings

    WHERE setting_group='invoice'

    AND setting_key='$key'

    LIMIT 1

    ";


    $result = mysqli_query($conn,$sql);


    if($row = mysqli_fetch_assoc($result)){

        return $row['setting_value'];

    }


    return "";

}

?>