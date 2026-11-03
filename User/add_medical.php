<?php
session_start();
include '../db/db.php';

$pet_id = $_GET['pet_id'];

if(isset($_POST['save'])){

    $date = $_POST['date'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $notes = $_POST['notes'];

    $sql = "INSERT INTO medical_history (pet_id, date, diagnosis, treatment, notes)
            VALUES ('$pet_id', '$date', '$diagnosis', '$treatment', '$notes')";

    mysqli_query($conn, $sql);

        header("location: view.php?pet_id=" .$pet_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical</title>
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
<div class ="main">
<div class = "page-title">
<h2>Add Medical History</h2>
</div>
<form method="POST">
    <label>Date</label>
    <input type="date" name="date">
    <label>Diagnosis</label>
    <input type="text" name="diagnosis">
    <label>Treatment</label>
    <input type="text" name="treatment">
    <label>Notes</label>
   <textarea name="notes"></textarea><br>
    <button type="submit" name="save">Add Medical</button>

</form>
<br>
<button><a href="view.php?pet_id=<?php echo $pet_id;  ?>"> Back To Pet</a></button>

</div>
    
</body>
</html>