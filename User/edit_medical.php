<?php
session_start();
include '../db/db.php';



$id = $_GET['id'];

/* FETCH DATA */
$result = mysqli_query($conn, "SELECT * FROM medical_history WHERE id = '$id'");
$row = mysqli_fetch_assoc($result);

/* UPDATE */
if(isset($_POST['save'])){

    $date = $_POST['date'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $notes = $_POST['notes'];

    mysqli_query($conn,
        "UPDATE medical_history 
         SET date='$date',
             diagnosis='$diagnosis',
             treatment='$treatment',
             notes='$notes'
         WHERE id='$id'"
    );

    header("Location: view.php?pet_id=". $row['pet_id']);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Medical History</title>
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
<div class= "page-title">
<h2>🩺 Edit Medical History</h2>
</div>
<form method="POST">

    <label>Date</label><br>
    <input type="date" name="date" value="<?php echo $row['date']; ?>"><br><br>

    <label>Diagnosis</label><br>
    <input type="text" name="diagnosis" value="<?php echo $row['diagnosis']; ?>"><br><br>

    <label>Treatment</label><br>
    <input type="text" name="treatment" value="<?php echo $row['treatment']; ?>"><br><br>

    <label>Notes</label><br>
    <textarea name="notes" rows="4"><?php echo $row['notes']; ?></textarea><br><br>

    <button type="submit" name="save">Update Record</button>

</form>



</div>

</body>
</html>