<?php
session_start();
include '../db/db.php';

/* CHECK LOGIN */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

/* GET ID */
if(!isset($_GET['id'])){
    die("No pet selected");
}

$id = $_GET['id'];

/* GET PET DATA */
$result = mysqli_query($conn, "SELECT * FROM pets WHERE id='$id'");
$row = mysqli_fetch_assoc($result);

$message = "";

/* UPDATE */
if(isset($_POST['update'])){

    $pet_name = $_POST['pet_name'];
    $type = $_POST['type'];



        mysqli_query($conn,
            "UPDATE pets 
             SET pet_name='$pet_name', type='$type' 
             WHERE id='$id'"
        );

        header("Location: my_pet.php");
        exit();
    }

?>
<title>Edit_vaccine</title>
<link rel="stylesheet" href="users.css">

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

<div class = "main">
<div class="page-title">

    <h2>✏️ Edit Pet</h2>
</div>

    <form method="POST">

        <label>Pet Name</label>
        <input type="text" name="pet_name" value="<?php echo $row['pet_name']; ?>">

        <label>Pet Type</label>
        <input type="text" name="type" value="<?php echo $row['type']; ?>"><br><br>

        <button type="submit" name="update">Update Pet</button>

    </form>

</div>