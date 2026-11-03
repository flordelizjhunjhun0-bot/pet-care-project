<?php
session_start();
include '../db/db.php';

$pet_id = $_GET['pet_id'];

if(isset($_POST['save'])){

    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];

    $sql = "INSERT INTO appointments (pet_id, date, time, reason)
            VALUES ('$pet_id', '$date', '$time', '$reason')";

    mysqli_query($conn, $sql);
    header("location: view.php?pet_id=" .$pet_id);
}
?>
<title>Add Appointments</title>
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
<div class ="main">
    <div class = "page-title"></div>
        <h2>Add Appointment</h2>
        <form method="POST">
        <label >Date</label>
        <input type="date" name="date">
        <label >Time</label>
        <input type="time" name="time">
        <label >Reason</label>
        <input type="text" name="reason"><br>
        <button name="save">Add Appointments</button>

        </form>
        <br>
        <button><a href="view.php?pet_id=<?php echo $pet_id; ?>">Back to Pet</a></button>
</div>