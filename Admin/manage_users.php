<?php
session_start();
include '../db/db.php';

/* CHECK ADMIN */
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

/* SEARCH */
$search = "";
if(isset($_GET['search'])){
    $search = $_GET['search'];
    $result = mysqli_query($conn,
        "SELECT * FROM users 
         WHERE role='user' 
         AND (name LIKE '%$search%' 
         OR username LIKE '%$search%' 
         OR email LIKE '%$search%')
         ORDER BY id DESC"
    );
} else {
    $result = mysqli_query($conn,
        "SELECT * FROM users 
         WHERE role='user' 
         ORDER BY id DESC"
    );
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
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
<h2>👤 Manage Users</h2>
</div>


<!-- ========================= -->
<!-- SEARCH BAR -->
<!-- ========================= -->
<form method="GET" style="margin-bottom:15px;">
    <input type="text" name="search" placeholder="Search user..." value="<?php echo $search; ?>">
    <button type="submit">Search</button>
</form>

<!-- ========================= -->
<!-- TABLE -->
<!-- ========================= -->
<table>
<tr>
    <th>Name</th>
    <th>Username</th>
    <th>Email</th>
    <th>Status</th>
    <th>Action</th>
</tr>


    <?php while($row = mysqli_fetch_assoc($result)){ ?>

    <tr>
        <td><?php echo $row['name']; ?></td>
        <td><?php echo $row['username']; ?></td>
        <td><?php echo $row['email']; ?></td>

        <td>
            <?php
            if($row['status'] == 'active'){
                echo "<span style='color:green;font-weight:bold;'>Active</span>";
            } elseif($row['status'] == 'pending'){
                echo "<span style='color:orange;font-weight:bold;'>Pending</span>";
            } else {
                echo "<span style='color:red;font-weight:bold;'>Deactivated</span>";
            }
            ?>
        </td>

        <td>

            <button><a href="user_approve.php?id=<?php echo $row['id']; ?>">Approve</a></button>
            <button><a href="user_deactivate.php?id=<?php echo $row['id']; ?>">Deactivate</a> </button>
            <button><a href="user_active.php?id=<?php echo $row['id']; ?>">active</a> </button>
           <button> <a href="user_delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete user?')">Delete</a></button>

        </td>
    </tr>




<?php } ?>

</table>

</div>

</body>
</html>