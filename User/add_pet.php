<?php
session_start();
include '../db/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}



if(isset($_POST['submit'])){

    $user_id = $_SESSION['user_id'];
    $name = $_POST['pet_name'];
    $type = $_POST['type'];


        mysqli_query($conn,
            "INSERT INTO pets (user_id, pet_name, type)
             VALUES ('$user_id', '$name', '$type')"
        );

        header("Location: my_pet.php");
       
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Pet</title>
    <link rel="stylesheet" href="users.css">
</head>

<body>
<!-- ========================= -->
<!-- SIDEBAR -->
<!-- ========================= -->
<div class="sidebar">
    <h1>🐾 Pet Care</h1>

    <a href="user_dashboard.php">Dashboard</a>
    <a href="add_pet.php">Add Pet</a>
    <a href="my_pet.php">My Pets</a>
     <a href="../Account/logout.php">Logout</a>
</div>


<div class="main">

    <div class="page-title">
        <h2>🐾 Add New Pet</h2>
    </div>

   

        <form method="POST">

            <div class="form-group">
                <label>Pet Name</label>
                <input type="text" name="pet_name" placeholder="Enter pet name">
            </div>

            <div class="form-group">
                <label>Pet Type</label>
                <input type="text" name="type" placeholder="Dog, Cat, Bird...">
            </div>

            <button type="submit" name="submit">➕ Add Pet</button>

        </form>


</div>

</body>
</html>