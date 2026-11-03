<?php
session_start();
include '../db/db.php';

/* CHECK */
if(!isset($_GET['pet_id'])){
    die("No pet selected");
}

$pet_id = $_GET['pet_id'];


/* PET INFO */
$sql = mysqli_query($conn, "SELECT * from pets where id = '$pet_id'");
$pet = mysqli_fetch_assoc($sql);


/* VACCINES */
$vaccine_query = mysqli_query($conn, "SELECT * from vaccines where pet_id = '$pet_id'");



/* MEDICAL */
$medical_query = mysqli_query($conn, "SELECT * from medical_history where pet_id = '$pet_id'");


/* APPOINTMENTS */
$appointment_query = mysqli_query($conn, "SELECT * from appointments where pet_id = '$pet_id'");

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Pet</title>
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

<!-- ========================= -->
<!-- PET INFO CARD -->
<!-- ========================= -->

    <div class="page-title">
    <h2>🐾 Pet Details</h2>
    </div>
    
<div class="box1">
    <p><strong>Name: </strong><?php echo $pet['pet_name']; ?></p>
    <p><strong>Type: </strong> <?php echo $pet['type']; ?></p>
</div>

<br>

<button><a href="add_vaccine.php?pet_id=<?php echo $pet_id; ?>">Add Vaccine</a></button>
<button><a  href="add_medical.php?pet_id=<?php echo $pet_id; ?>"> Add Medical</a></button>
<button><a  href="add_appointment.php?pet_id=<?php echo $pet_id; ?>">Add Appointment</a></button>

<hr>
<br><br>

<!-- ========================= -->
<!-- VACCINES -->
<!-- ========================= -->
<h3>💉 Vaccine History</h3>

<table>
<tr>
    <th>Vaccine</th>
    <th>Date Given</th>
    <th>Next Schedule</th>
    <th>Status</th>
     <th>Action</th>

</tr>
<?php while($row=mysqli_fetch_assoc($vaccine_query)){  ?>
<tr>
    <td><?php echo $row['vaccine_name'] ;?></td>
    <td><?php echo $row['date_given']; ?></td>
    <td><?php echo $row['next_schedule']; ?></td>
   <td><?php if($row['next_schedule'] >= $today){
    echo "<span style ='color:green; font-weght:bold;' >Upcoming </span>";

    
   }else{
     echo "<span style ='color:red; font-weght:bold;' >Overdue</span>";
   }
    
    ?></td>
     <td><button><a href="edit_vaccine.php?id=<?php echo $row['id']; ?>">edit</a></button>
     <button><a href="delete_vaccine.php?id=<?php echo $row['id']; ?>&pet_id=<?php echo $pet_id;?>" onclick = 'return confirm("delete vaccine")'>delete</a></button>
</td>
</tr>

<?php } ?>
</table>

<hr>
<br><br>

<!-- ========================= -->
<!-- MEDICAL HISTORY -->
<!-- ========================= -->
<h3>🩺 Medical History</h3>

<table>
<tr>
    <th>Date</th>
    <th>Diagnosis</th>
    <th>Treatment</th>
    <th>Notes</th>
    <th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($medical_query)) { ?>

<tr>
    <td><?php echo $row['date']; ?></td>
    <td><?php echo $row['diagnosis']; ?></td>
    <td><?php echo $row['treatment']; ?></td>
    <td><?php echo $row['notes']; ?></td>
    <td>
                        <button><a href="edit_medical.php?id=<?php echo $row['id']; ?>">Edit</a></button>
                      <button><a href="delete_medical.php?id=<?php echo $row['id']; ?>&pet_id=<?php echo $pet_id; ?>"
   onclick="return confirm('Delete this vaccine?')">
   Delete
</a></button>
</td>
</tr>

<?php } ?>

</table>

<hr>
<br><br>
<!-- ========================= -->
<!-- APPOINTMENTS -->
<!-- ========================= -->
<h3>📅 Appointments</h3>

<table>
<tr>
    <th>Date</th>
    <th>Time</th>
    <th>Reason</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($appointment_query)) { ?>

<tr>
    <td><?php echo $row['date']; ?></td>
    <td><?php echo $row['time']; ?></td>
    <td><?php echo $row['reason']; ?></td>


    <td>
        <?php
        if($row['date'] < $today){
            echo "<span style='color:red;font-weight:bold;'>OVERDUE</span>";
        } else {
            echo "<span style='color:green;font-weight:bold;'>UPCOMING</span>";
        }
        ?>
    </td>
       <td>
        <button><a href="edit_appointment.php?id=<?php echo $row['id']; ?>">Edit</a></button>
         <button><a href="delete_appointment.php?id=<?php echo $row['id']; ?>&pet_id=<?php echo $pet_id; ?>"
   onclick="return confirm('Delete this vaccine?')">
   Delete
</a></button>
</td>
    
</tr>

<?php } ?>

</table>

<br>

<button><a href="my_pet.php">Back to My Pets</a></button>

</div>

</body>
</html>