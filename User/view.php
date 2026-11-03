
<?php

require_once '../db/db.php';

$user_id = require_user($conn);

$pet_id = valid_id($_GET['pet_id'] ?? 0);

if (!$pet_id) {
    http_response_code(400);
    exit("Invalid pet ID.");
}

/* Get pet information owned by logged-in user */
$stmt = $conn->prepare(
    "SELECT *
     FROM pets
     WHERE id = ?
     AND user_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();

$pet = $stmt->get_result()->fetch_assoc();

if (!$pet) {
    http_response_code(404);
    exit("Pet not found or access denied.");
}

/* Get vaccine records */
$stmt = $conn->prepare(
    "SELECT *
     FROM vaccines
     WHERE pet_id = ?
     ORDER BY date_given DESC"
);

$stmt->bind_param("i", $pet_id);
$stmt->execute();

$vaccine_query = $stmt->get_result();

/* Get medical history */
$stmt = $conn->prepare(
    "SELECT *
     FROM medical_history
     WHERE pet_id = ?
     ORDER BY `date` DESC"
);

$stmt->bind_param("i", $pet_id);
$stmt->execute();

$medical_query = $stmt->get_result();

/* Get appointments */
$stmt = $conn->prepare(
    "SELECT *
     FROM appointments
     WHERE pet_id = ?
     ORDER BY `date` DESC, `time` DESC"
);

$stmt->bind_param("i", $pet_id);
$stmt->execute();

$appointment_query = $stmt->get_result();

$today = date('Y-m-d');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Pet Details | Pet-Care</title>

    <!-- EXISTING CSS -->
    <link rel="stylesheet" href="./users.css?v=3">

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h1>🐾 Pet-Care</h1>

    <a href="user_dashboard.php">Dashboard</a>
    <a href="add_pet.php">Add Pet</a>
    <a href="my_pet.php">My Pets</a>
    <a href="../Account/logout.php">Logout</a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <div class="page-title">

        <h2>🐾 Pet Details</h2>

        <p>Manage your pet's information and health records.</p>

    </div>

    <!-- PET INFORMATION -->
    <div class="box1">

        <h2><?= h($pet['pet_name']) ?></h2>

        <p>
            <strong>Pet Type:</strong>
            <?= h($pet['type']) ?>
        </p>

    </div>

    <br>

    <!-- ADD ACTION BUTTONS -->
    <div class="action-buttons">

        <a class="button"
           href="add_vaccine.php?pet_id=<?= (int)$pet_id ?>">
            💉 Add Vaccine
        </a>

        <a class="button"
           href="add_medical.php?pet_id=<?= (int)$pet_id ?>">
            🩺 Add Medical Record
        </a>

        <a class="button"
           href="add_appointment.php?pet_id=<?= (int)$pet_id ?>">
            📅 Add Appointment
        </a>
        

    </div>

    <hr>

    <!-- VACCINE HISTORY -->
    <h3>💉 Vaccine History</h3>

    <div class="table-wrap">

    <table>

        <tr>
            <th>Vaccine</th>
            <th>Date Given</th>
            <th>Next Schedule</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($vaccine_query->num_rows === 0): ?>

            <tr>
                <td colspan="5">No vaccine records found.</td>
            </tr>

        <?php endif; ?>

        <?php while ($row = $vaccine_query->fetch_assoc()): ?>

            <tr>

                <td><?= h($row['vaccine_name']) ?></td>

                <td><?= h($row['date_given']) ?></td>

                <td>
                    <?= h($row['next_schedule'] ?: 'Not scheduled') ?>
                </td>

                <td>

                    <?php

                    if (empty($row['next_schedule'])) {

                        echo "Not scheduled";

                    } elseif ($row['next_schedule'] < $today) {

                        echo "<span style='color:red'>OVERDUE</span>";

                    } else {

                        echo "<span style='color:green'>UPCOMING</span>";

                    }

                    ?>

                </td>

                <td>
                     <div class="action-buttons">
                    

                    <a class ="button" href="edit_vaccine.php?id=<?= (int)$row['id'] ?>">
                        Edit
                    </a>

                    <a class = "button" href="delete_vaccine.php?id=<?= (int)$row['id'] ?>&pet_id=<?= (int)$pet_id ?>"
                       onclick="return confirm('Delete this vaccine record?')">
                        Delete
                    </a>
                    </div>

                </td>

            </tr>

        <?php endwhile; ?>

    </table>

    </div>

    <hr>

    <!-- MEDICAL HISTORY -->
    <h3>🩺 Medical History</h3>

    <div class="table-wrap">

    <table>

        <tr>
            <th>Date</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
            <th>Notes</th>
            <th>Action</th>
        </tr>

        <?php if ($medical_query->num_rows === 0): ?>

            <tr>
                <td colspan="5">No medical records found.</td>
            </tr>

        <?php endif; ?>

        <?php while ($row = $medical_query->fetch_assoc()): ?>

            <tr>

                <td><?= h($row['date']) ?></td>

                <td><?= h($row['diagnosis']) ?></td>

                <td><?= h($row['treatment']) ?></td>

                <td><?= h($row['notes']) ?></td>

                <td>

                    <div class="action-buttons">
                    

                    <a class="button" href="edit_medical.php?id=<?= (int)$row['id'] ?>">
    Edit
</a>

<a class="button"
   href="delete_medical.php?id=<?= (int)$row['id'] ?>&pet_id=<?= (int)$pet_id ?>"
   onclick="return confirm('Delete this medical record?')">
    Delete
</a>
                    </div>
                </td>

            </tr>

        <?php endwhile; ?>

    </table>

    </div>

    <hr>

    <!-- APPOINTMENTS -->
    <h3>📅 Appointments</h3>

    <div class="table-wrap">

    <table>

        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($appointment_query->num_rows === 0): ?>

            <tr>
                <td colspan="5">No appointments found.</td>
            </tr>

        <?php endif; ?>

        <?php while ($row = $appointment_query->fetch_assoc()): ?>

            <tr>

                <td><?= h($row['date']) ?></td>

                <td><?= h($row['time']) ?></td>

                <td><?= h($row['reason']) ?></td>

                <td>

                    <?php

                    if ($row['date'] < $today) {

                        echo "<span style='color:red'>PAST</span>";

                    } else {

                        echo "<span style='color:green'>UPCOMING</span>";

                    }

                    ?>

                </td>

                <td>

                    <div class="action-buttons">
                    

                   <div class="action-buttons">

    <a class="button"
       href="edit_appointment.php?id=<?= (int)$row['id'] ?>">
        Edit
    </a>

    <a class="button"
       href="delete_appointment.php?id=<?= (int)$row['id'] ?>&pet_id=<?= (int)$pet_id ?>"
       onclick="return confirm('Delete this appointment?')">
        Delete
    </a>

</div>
                    </div>

                </td>

            </tr>

        <?php endwhile; ?>

    </table>

    </div>

    <br>
  
 <div class="action-buttons">
     <a class="button"
    href="my_pet.php">Back to My Pets</a>


 </div>
 



</div>

</body>
</html>
