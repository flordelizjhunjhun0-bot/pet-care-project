<?php
session_start();
include '../db/db.php';

/* CHECK LOGIN */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$currentuser = $_SESSION['user_id'];



/* ========================= */
/* TOTAL PETS */
/* ========================= */
$sql = mysqli_query($conn, "SELECT COUNT(*) as total from pets 
        where  user_id = '$currentuser' and  type = 'dog'");

$rowpets = mysqli_fetch_assoc($sql);
$totalpets = $rowpets['total'];


$sql = mysqli_query($conn, "SELECT COUNT(*) as total from pets 
        where  user_id = '$currentuser' and  type = 'cat'");

$rowpets2 = mysqli_fetch_assoc($sql);
$totalpets2 = $rowpets2['total'];

$sql = mysqli_query($conn, "SELECT COUNT(*) as total from pets 
        where  user_id = '$currentuser' and  type != 'dog' and 'cat' ");

$rowpets3 = mysqli_fetch_assoc($sql);
$totalpets3 = $rowpets3['total'];
/* ========================= */
/* TOTAL VACCINES */
/* ========================= */

$sql = mysqli_query($conn, "SELECT COUNT(*) as total
                            from vaccines v
                            join pets p
                            on v.pet_id = p.id
                            where p.user_id = '$currentuser'");


$rowv = mysqli_fetch_assoc($sql);
$vac = $rowv['total'];



/* ========================= */
/* APPOINTMENTS */
/* ========================= */

$res3 = mysqli_query($conn, "SELECT *
                            from appointments a
                            join pets p
                            on a.pet_id = p.id
                            where p.user_id = '$currentuser'");



$today = date('Y-m-d');

?>

<!DOCTYPE html>
<html lang="en">
<head>
   
    <title>User Dashboard</title>
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

<!-- ========================= -->
<!-- MAIN -->
<!-- ========================= -->
<div class="main">

    <div class="text">
        <h1>Welcome, <?php echo $_SESSION['username']; ?> 👋</h1>
        <p>Your Pet Care Overview</p>
    </div>

    <!-- ========================= -->
    <!-- STATS BOX -->
    <!-- ========================= -->
  

    <div class="box">
        <h3>Dog</h3>
        <p><?php echo $totalpets; ?></p>
    </div>

    <div class="box">
        <h3>Cat</h3>
        <p><?php echo $totalpets2; ?></p>
    </div>

    <div class="box">
        <h3>Others</h3>
        <p><?php echo $totalpets3; ?></p>
    </div>

    <div class="box">
        <h3> Total Vaccines</h3>
        <p><?php echo $vac; ?></p>
    </div>

    

    <!-- ========================= -->
    <!-- APPOINTMENTS -->
    <!-- ========================= -->
    <h3>📅 Appointments</h3>

    <table>
        <tr>
            <th>Pet</th>
            <th>Date</th>
            <th>Time</th>
            <th>Reason</th>
            <th>Status</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($res3)) { ?>

        <tr>
            <td><?php echo $row['pet_name']; ?></td>
            <td><?php echo $row['date']; ?></td>
            <td><?php echo $row['time']; ?></td>
            <td><?php echo $row['reason']; ?></td>

            <td>
            <?php
            if($row['date'] > $today){
                echo "<span style = 'color:red; font-weight:bold;'> overdue</span>";
            }else{
             echo "<span style = 'color:green; font-weight:bold;'>upcoming</span>";
            }


?>
            </td>
        </tr>

        <?php } ?>

    </table>

    <hr>
<br>
    <div class="text">
        <h3>Status</h3>
        <p>No Alerts today ✅</p>
    </div>

</div>

</body>
</html>