<?php
include '../db/db.php';

$id = $_GET['id'];

mysqli_query($conn,
    "UPDATE users SET status='active' , attempts = 0 WHERE id='$id'"
);

header("Location: manage_users.php");
?>