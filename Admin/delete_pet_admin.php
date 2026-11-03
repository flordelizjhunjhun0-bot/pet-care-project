<?php
include '../db/db.php';

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM pets WHERE id='$id'");

header("Location: manage_pets.php");
?>