<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "project";

$conn = mysqli_connect($host, $user, $pass, $db);

/* CHECK CONNECTION */
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

/* SET TIMEZONE (important sa date like vaccines) */
date_default_timezone_set('Asia/Manila');

?>