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
        "SELECT vaccines.*, pets.pet_name, users.name AS owner
         FROM vaccines
         JOIN pets ON vaccines.pet_id = pets.id
         JOIN users ON pets.user_id = users.id
         WHERE vaccines.vaccine_name LIKE '%$search%'
         OR pets.pet_name LIKE '%$search%'
         OR users.name LIKE '%$search%'
         ORDER BY vaccines.id DESC"
    );
} else {
    $result = mysqli_query($conn,
        "SELECT vaccines.*, pets.pet_name, users.name AS owner
         FROM vaccines
         JOIN pets ON vaccines.pet_id = pets.id
         JOIN users ON pets.user_id = users.id
         ORDER BY vaccines.id DESC"
    );
}

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vaccines Management</title>
    <link rel="stylesheet" href="admin.css">
</head>

<body>

<!-- ========================= -->
<!-- SIDEBAR -->
<!-- ========================= -->

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
<div class="page-title">
<h2>💉 Vaccine Management</h2>
</div>

<!-- ========================= -->
<!-- SEARCH -->
<!-- ========================= -->
<form method="GET" style="margin-bottom:15px;">
    <input type="text" name="search" placeholder="Search vaccine, pet, owner..."
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
    <th>Vaccine</th>
    <th>Date Given</th>
    <th>Next Schedule</th>
    <th>Status</th>
    <th>Action</th>
</tr>



    <?php while($row = mysqli_fetch_assoc($result)){ ?>

    <tr>

        <td><?php echo $row['owner']; ?></td>
        <td><?php echo $row['pet_name']; ?></td>
        <td><?php echo $row['vaccine_name']; ?></td>
        <td><?php echo $row['date_given']; ?></td>
        <td><?php echo $row['next_schedule']; ?></td>

        <td>
            <?php
            if($row['next_schedule'] <= $today){
                echo "<span style = 'color:red; font-weight:bold;'>OVERDUE</span>";
            } else {
                echo "<span style = 'color:green; font-weight:bold;'>UPCOMING</span>";
            }
            ?>
        </td>

        <td>
          <button> 
            <a  href="delete_vaccine_admin.php?id=<?php echo $row['id']; ?>"
             onclick="return confirm('Delete vaccine?')">Delete</a></button>
        </td>

    </tr>



<?php } ?>

</table>

</div>

</body>
</html>