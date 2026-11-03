<?php
session_start();
include '../db/db.php';


$id = $_GET['id'];

/* GET DATA */
$result = mysqli_query($conn, "SELECT * FROM appointments WHERE id='$id'");
$row = mysqli_fetch_assoc($result);

/* UPDATE */
if(isset($_POST['update'])){

    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];

        mysqli_query($conn,
            "UPDATE appointments 
             SET date='$date',
            time='$time',
            reason='$reason'
            WHERE id='$id'");

        header("Location: view.php?pet_id=" . $row['pet_id']);
        exit();
    }

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Appointment</title>
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
<div class = "page-title">
<h2>✏️ Edit Appointment</h2>
</div>


<form method="POST">

    <label>Date</label><br>
    <input type="date" name="date" value="<?php echo $row['date']; ?>"><br><br>

    <label>Time</label><br>
    <input type="time" name="time" value="<?php echo $row['time']; ?>"><br><br>

    <label>Reason</label><br>
    <input type="text" name="reason" value="<?php echo $row['reason']; ?>"><br><br>

    <button type="submit" name="update">Update Appointment</button>

</form>


</div>

</body>
</html>