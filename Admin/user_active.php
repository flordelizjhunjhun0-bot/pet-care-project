<?php
include '../db/db.php';

$id = $_GET['id'];

mysqli_query($conn,
    "UPDATE users SET status='pending' WHERE id='$id'"
);

header("Location: manage_users.php");
?>