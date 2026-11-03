<?php
session_start();
include '../db/db.php';

if(!isset($_SESSION['admin'])){
    header("location: login.php");
    exit();
}

/* VACCINES */
$sql = "SELECT vaccines.*, pets.pet_name, users.name AS owner
        FROM vaccines
        JOIN pets ON vaccines.pet_id = pets.id
        JOIN users ON users.id = pets.user_id
        ORDER BY vaccines.date_given DESC";
$result = mysqli_query($conn, $sql);

/* MEDICAL */
$sql_med = "SELECT medical_history.*, pets.pet_name, users.name AS owner
            FROM medical_history
            JOIN pets ON medical_history.pet_id = pets.id
            JOIN users ON pets.user_id = users.id
            ORDER BY medical_history.date DESC";
$result_med = mysqli_query($conn, $sql_med);

/* APPOINTMENTS */
$sql_app = "SELECT appointments.*, pets.pet_name, users.name AS owner
            FROM appointments
            JOIN pets ON appointments.pet_id = pets.id
            JOIN users ON pets.user_id = users.id
            ORDER BY appointments.date DESC";
$result_app = mysqli_query($conn, $sql_app);

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <link rel="stylesheet" href="admin.css">

    <style>
    .print-btn {
        padding: 8px 15px;
        background: #0E6B7A;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-bottom: 15px;
    }

    @media print {
        .sidebar, .print-btn {
            display: none;
        }

        body {
            background: white;
        }

        .main {
            width: 100%;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
    }
    </style>
</head>

<body>

<!-- SIDEBAR -->

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

<!-- MAIN -->
<div class="main">

    <h2>Reports</h2>

    <!-- PRINT BUTTON -->
    <button onclick="window.print()" class="print-btn">Print</button>

    <!-- ================= VACCINE ================= -->
    <h3> 💉Vaccine Report</h3>

    <table>
    <tr>
        <th>Owner</th>
        <th>Pet Name</th>
        <th>Vaccine</th>
        <th>Date Given</th>
        <th>Next Schedule</th>
        <th>Status</th>
    </tr>

    <?php 
    if(mysqli_num_rows($result) == 0){
        echo "<tr><td colspan='6'>No records</td></tr>";
    }

    while($row = mysqli_fetch_assoc($result)){
    ?>
    <tr>
        <td><?php echo $row['owner']; ?></td>
        <td><?php echo $row['pet_name']; ?></td>
        <td><?php echo $row['vaccine_name']; ?></td>
        <td><?php echo $row['date_given']; ?></td>
        <td><?php echo $row['next_schedule']; ?></td>

        <td>
        <?php
        if(!empty($row['next_schedule']) && $row['next_schedule'] <= $today){
            echo "<span style='color:red;font-weight:bold;'>OVERDUE</span>";
        } else {
            echo "<span style='color:green;font-weight:bold;'>UPCOMING</span>";
        }
        ?>
        </td>
    </tr>
    <?php } ?>
    </table>
<br>
    <!-- ================= MEDICAL ================= -->
    <h3> 🩺 Medical History</h3>

    <table >
    <tr>
        <th>Owner</th>
        <th>Pet Name</th>
        <th>Date</th>
        <th>Diagnosis</th>
        <th>Treatment</th>
        <th>Notes</th>
    </tr>

    <?php 
    if(mysqli_num_rows($result_med) == 0){
        echo "<tr><td colspan='6'>No records</td></tr>";
    }

    while($row = mysqli_fetch_assoc($result_med)){
    ?>
    <tr>
        <td><?php echo $row['owner']; ?></td>
        <td><?php echo $row['pet_name']; ?></td>
        <td><?php echo $row['date']; ?></td>
        <td><?php echo $row['diagnosis']; ?></td>
        <td><?php echo $row['treatment']; ?></td>
        <td><?php echo $row['notes']; ?></td>
    </tr>
    <?php } ?>
    </table>
    <br>

    <!-- ================= APPOINTMENTS ================= -->
    <h3> 📅 Appointment Report</h3>

    <table>
    <tr>
        <th>Owner</th>
        <th>Pet Name</th>
        <th>Date</th>
        <th>Time</th>
        <th>Reason</th>
        <th>Status</th>
    </tr>

    <?php 
    if(mysqli_num_rows($result_app) == 0){
        echo "<tr><td colspan='6'>No records</td></tr>";
    }

    while($row = mysqli_fetch_assoc($result_app)){
    ?>
    <tr>
        <td><?php echo $row['owner']; ?></td>
        <td><?php echo $row['pet_name']; ?></td>
        <td><?php echo $row['date']; ?></td>
        <td><?php echo $row['time']; ?></td>
        <td><?php echo $row['reason']; ?></td>

        <td>
        <?php
        if(!empty($row['date']) && $row['date'] < $today){
            echo "<span style='color:red;font-weight:bold;'>OVERDUE</span>";
        } else {
            echo "<span style='color:green;font-weight:bold;'>UPCOMING</span>";
        }
        ?>
        </td>
    </tr>
    <?php } ?>
    </table>

</div>

</body>
</html>