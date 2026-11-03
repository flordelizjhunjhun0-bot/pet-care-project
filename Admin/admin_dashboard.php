<?php
session_start();
include '../db/db.php';

/* CHECK ADMIN */
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

/* COUNTS */
$Users = mysqli_query($conn, "SELECT COUNT(*) as total from users where role = 'user'");
$row = mysqli_fetch_assoc($Users);
$totalUsers = $row['total'];

$totalPets = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM pets"))['total'];

$totalVaccines = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM vaccines"))['total'];

$totalAppointments = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM appointments"))['total'];

$today = date('Y-m-d');

$overdueVaccines = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM vaccines WHERE next_schedule <= '$today'"))['total'];

/* ========================= */
/* RECENT USERS */
/* ========================= */
$recentuser = mysqli_query($conn,
    "SELECT name, username, status 
     FROM users 
     WHERE role='user' 
     ORDER BY id DESC 
     LIMIT 5"







);

/* ========================= */
/* RECENT PETS */
/* ========================= */
$recentpets = mysqli_query($conn,
    "SELECT p.pet_name, p.type, u.username 
     FROM pets p
     JOIN users u ON p.user_id = u.id
     ORDER BY p.id DESC 
     LIMIT 5"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>

<body>


<div class="sidebar">
    <h2>🐾 Admin Panel</h2>

    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="manage_pets.php">Manage Pets</a>
    <a href="admin_vaccine.php">Vaccines</a>
    <a href="admin_medical.php">Medical</a>
    <a href="admin_appointment.php">Appointments</a>
    <a href="reports.php">Reports</a>
    <a href="../Account/logout.php">Logout</a>
</div>

<!-- ========================= -->
<!-- MAIN CONTENT -->
<!-- ========================= -->
<div class="main">
<div class = "page-title">
    <h2>Admin Dashboard 📊</h2>
</div>
    <!-- STATS -->
    <div class="box">
        <h3>Total Users</h3>
        <p><?php echo $totalUsers; ?></p>
    </div>

    <div class="box">
        <h3>Total Pets</h3>
        <p><?php echo $totalPets; ?></p>
    </div>
     <div class="box">
        <h3>Total Vaccine</h3>
        <p><?php echo $totalVaccines; ?></p>
    </div>

     <div class="box">
        <h3>    Appointments</h3>
        <p><?php echo $totalAppointments; ?></p>
    </div>
     <div class="box">
        <h3>Overdue</h3>
        <p><?php echo $overdueVaccines; ?></p>
    </div>
   

    <hr>

    <!-- RECENT USERS -->
    <h3>👤 Recent Users</h3>

    <table>
        <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Status</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($recentuser)){ ?>
        <tr>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['username']; ?></td>
            <td>
                <?php
                if($row['status'] == 'active'){
                    echo "<span style='color:green;'>Active</span>";
                } else if ($row['status'] == 'pending'){
                    echo "<span style='color:yellow;'>Pending</span>";
                }else{
                    echo "<span style='color:red;'>Inactive</span>";
                }
                ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <hr>

    <!-- RECENT PETS -->
    <h3>🐾 Recent Pets</h3>

    <table>
        <tr>
            <th>Owner</th>
            <th>Pet Name</th>
            <th>Type</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($recentpets)){ ?>
        <tr>
            <td><?php echo $row['username']; ?></td>
            <td><?php echo $row['pet_name']; ?></td>
            <td><?php echo $row['type']; ?></td>
        </tr>
        <?php } ?>
    </table>

</div>


</body>
</html>