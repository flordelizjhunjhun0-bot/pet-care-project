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
        "SELECT pets.*, users.name AS owner
         FROM pets
         JOIN users ON pets.user_id = users.id
         WHERE pets.pet_name LIKE '%$search%'
         OR pets.type LIKE '%$search%'
         OR users.name LIKE '%$search%'
         ORDER BY pets.id DESC"
    );
} else {
    $result = mysqli_query($conn,
        "SELECT pets.*, users.name AS owner
         FROM pets
         JOIN users ON pets.user_id = users.id
         ORDER BY pets.id DESC"
    );
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Pets</title>
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
<div class="main">
    <div class="page-title">
<h2>🐾 Manage Pets</h2>
</div>

<!-- ========================= -->
<!-- SEARCH BAR -->
<!-- ========================= -->
<form method="GET" style="margin-bottom:15px;">
    <input type="text" name="search" placeholder="Search pet or owner..."
           value="<?php echo $search; ?>">

    <button type="submit">Search</button>
</form>

<!-- ========================= -->
<!-- TABLE -->
<!-- ========================= -->
<table>
<tr>
    <th>Owner</th>
    <th>Pet Name</th>
    <th>Type</th>
    <th>Action</th>
</tr>



    <?php while($row = mysqli_fetch_assoc($result)){ ?>

    <tr>
        <td><?php echo $row['owner']; ?></td>
        <td><?php echo $row['pet_name']; ?></td>
        <td><?php echo $row['type']; ?></td>

        <td>
           <button><a href="delete_pet_admin.php?id=<?php echo $row['id']; ?>"
               onclick="return confirm('Delete this pet?')">Delete</a></button>
       
        </td>
    </tr>

    <?php } ?>



</table>

</div>

</body>
</html>