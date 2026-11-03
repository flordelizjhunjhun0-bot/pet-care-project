<?php
session_start();
include '../db/db.php';

/* CHECK ADMIN */
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

$search = "";

/* SEARCH */
if(isset($_GET['search'])){
    $search = $_GET['search'];

    $result = mysqli_query($conn,
        "SELECT appointments.*, pets.pet_name, users.name AS owner
         FROM appointments
         JOIN pets ON pets.id = appointments.pet_id
         JOIN users ON users.id = pets.user_id
         WHERE pets.pet_name LIKE '%$search%'
         OR users.name LIKE '%$search%'
         OR appointments.reason LIKE '%$search%'
         ORDER BY appointments.date DESC"
    );
} else {
   $result = mysqli_query($conn,"SELECT appointments.*, pets.pet_name, users.name as owner
                                from appointments
                                join pets
                                on pets.id = appointments.pet_id
                                join users
                                on users.id = pets.user_id
                                order by appointments.date desc");
}

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments Management</title>
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


<div class = "main">
<div class = "page-title">
<h2>📅 Appointment Management</h2>
</div>
<!-- ========================= -->
<!-- SEARCH -->
<!-- ========================= -->
<form method="GET" style="margin-bottom:15px;">
    <input type="text" name="search" placeholder="Search appointment, pet, owner..."
           value="<?php echo $search; ?>">
    <button type="submit">Search</button>
</form>

<!-- ========================= -->
<!-- TABLE -->
<!-- ========================= -->
<table>
<tr>
    <th>Owner</th>
    <th>Pet</th>
    <th>Date</th>
    <th>Time</th>
    <th>Reason</th>
    <th>Status</th>
    <th>Action</th>
</tr>


    <?php while($row = mysqli_fetch_assoc($result)){ ?>

    <tr>

        <td><?php echo $row['owner']; ?></td>
        <td><?php echo $row['pet_name']; ?></td>
        <td><?php echo $row['date']; ?></td>
        <td><?php echo $row['time']; ?></td>
        <td><?php echo $row['reason']; ?></td>

        <td>
            <?php
            if($row['date'] < $today){
                echo "<span style = 'color:red; font-weight:bold;'>OVERDUE</span>";
            } else {
                echo "<span style = 'color:green; font-weight:bold;'>UPCOMING</span>";
            }
            ?>
        </td>

        <td>
           <button>
            <a href="delete_appointment_admin.php?id=<?php echo $row['id']; ?>"
               onclick="return confirm('Delete this appointment?')">Delete</a></button>
        </td>

    </tr>


<?php } ?>

</table>

</div>

</body>
</html>