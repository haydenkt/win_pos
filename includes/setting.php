<?php

if (!isset($conn)) {
    include __DIR__ . "/../config/database.php";
}

function getSetting($conn, $group, $key)
{
    $group = mysqli_real_escape_string($conn, $group);
    $key = mysqli_real_escape_string($conn, $key);

    $query = mysqli_query($conn,"
        SELECT setting_value
        FROM settings
        WHERE setting_group='$group'
        AND setting_key='$key'
        LIMIT 1
    ");

    if($query && mysqli_num_rows($query) > 0){

        $row = mysqli_fetch_assoc($query);

        return $row['setting_value'];

    }

    return "";
}