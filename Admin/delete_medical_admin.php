<?php
include '../db/db.php';

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM medical_history WHERE id='$id'");

header("Location: admin_vaccine.php");
?>