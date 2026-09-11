<?php


include_once __DIR__ . "/config.php";



$conn = mysqli_init();

mysqli_ssl_set(
    $conn,
    null,
    null,
    null,
    null,
    null
);

mysqli_real_connect(
    $conn,
    DB_HOST,
    DB_USER,
    DB_PASSWORD,
    DB_NAME,
    DB_PORT,
    null,
    MYSQLI_CLIENT_SSL
);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}



mysqli_set_charset($conn,"utf8mb4");


?>