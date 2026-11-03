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
        "SELECT medical_history.id,
               users.name AS owner,
               pets.pet_name,
               medical_history.date,
               medical_history.diagnosis,
               medical_history.treatment,
               medical_history.notes
        FROM medical_history
        JOIN pets ON pets.id = medical_history.pet_id
        JOIN users ON users.id = pets.user_id
        WHERE users.name LIKE '%$search%'
        OR pets.pet_name LIKE '%$search%'
        OR medical_history.diagnosis LIKE '%$search%'
        ORDER BY medical_history.date DESC"
    );
} else {
    $result = mysqli_query($conn,
        "SELECT medical_history.id,
               users.name AS owner,
               pets.pet_name,
               medical_history.date,
               medical_history.diagnosis,
               medical_history.treatment,
               medical_history.notes
        FROM medical_history
        JOIN pets ON pets.id = medical_history.pet_id
        JOIN users ON users.id = pets.user_id
        ORDER BY medical_history.date DESC"
    );
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medical History</title>
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
    <div class ="page-title">
<h2>🩺 Medical History (All Pets)</h2>
</div>
<!-- ========================= -->
<!-- SEARCH -->
<!-- ========================= -->
<form method="GET" style="margin-bottom:15px;">
    <input type="text" name="search" placeholder="Search owner, pet, diagnosis..."
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
    <th>Diagnosis</th>
    <th>Treatment</th>
    <th>Notes</th>
    <th>Action</th>
</tr>



    <?php while($row = mysqli_fetch_assoc($result)){ ?>

    <tr>
        <td><?php echo $row['owner']; ?></td>
        <td><?php echo $row['pet_name']; ?></td>
        <td><?php echo $row['date']; ?></td>
        <td><?php echo $row['diagnosis']; ?></td>
        <td><?php echo $row['treatment']; ?></td>
        <td><?php echo $row['notes']; ?></td>

        <td>
          <button>
            <a class="btn delete" href="delete_medical_admin.php?id=<?php echo $row['id']; ?>"
               onclick="return confirm('Delete this record?')">Delete</a></button>
        </td>
    </tr>


<?php } ?>

</table>

</div>

</body>
</html>