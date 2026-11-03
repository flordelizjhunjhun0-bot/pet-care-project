<?php
include '../db/db.php';

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM vaccines    WHERE id='$id'");

header("Location: admin_vaccine.php");
?>