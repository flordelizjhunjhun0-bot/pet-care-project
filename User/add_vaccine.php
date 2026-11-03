<?php
session_start();
include '../db/db.php';



$pet_id = $_GET['pet_id'];


// ✔ SAVE VACCINE
if(isset($_POST['save'])){

    $vaccine_name = $_POST['vaccine_name'];
    $date_given = $_POST['date_given'];

    // ✔ AUTO NEXT SCHEDULE (+21 days)
    $next_schedule = date('Y-m-d', strtotime($date_given . ' +21 days'));

    // ✔ INSERT QUERY
    $sql = "INSERT INTO vaccines (pet_id, vaccine_name, date_given, next_schedule)
            VALUES ('$pet_id', '$vaccine_name', '$date_given', '$next_schedule')";
            mysqli_query($conn,$sql);

            header("location: view.php?pet_id=" .$pet_id);
            
            
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Vaccine</title>
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
<div class = "main">
    <div class = "page-title">
<h2>Add Vaccine</h2>
</div>

<form method="POST">

    <label>Vaccine Name</label>
    <input type="text" name="vaccine_name" required>

    <label>Date Given</label>
    <input type="date" name="date_given" required><br>

    <button type="submit" name="save">Add Vaccine</button> 


</form>

<br>
<button>
<a href="view.php?pet_id=<?php echo $pet_id; ?>">Back to Pet</a></button>
</div>


</body>
</html>