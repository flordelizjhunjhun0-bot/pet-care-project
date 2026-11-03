<?php
include '../db/db.php';

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM apppointments WHERE id='$id'");

header("Location: admin_appointment.php");
?>