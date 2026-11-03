<?php
session_start();
include '../db/db.php';

$id = $_GET['id'];


$result = mysqli_query($conn, "SELECT * FROM vaccines where id = '$id'");
$row = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){
    $vaccine_name = $_POST['vaccine_name'];

    $sql = "UPDATE vaccines set vaccine_name = '$vaccine_name' where id = '$id'";
    mysqli_query($conn,$sql);

    header("Location: view.php?pet_id=" .$row['pet_id']);
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
<div class="main">
<div class = "page-title">
    <h2>💉 Edit Vaccine</h2>
    </div>


        <form method="POST">
            <label>vaccine</label>
            <input type="text" name = "vaccine_name" value = "<?php echo $row['vaccine_name']; ?> ">


            <button name="update">Update Vaccine</button>

        </form>

    </div>

</div>