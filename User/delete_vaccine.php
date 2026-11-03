<?php
session_start();
include '../db/db.php';

$id = $_GET['id'];
$pet_id = $_GET['pet_id'];  
/* GET DATA */
 $sql = mysqli_query($conn, "DELETE  FROM vaccines WHERE id='$id'");



    header("Location: view.php?pet_id=" . $pet_id);
?>