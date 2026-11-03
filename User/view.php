
<?php
require_once '../db/db.php';

$user_id = require_user($conn);
$pet_id = valid_id($_GET['pet_id'] ?? 0);

if (!$pet_id) {
    http_response_code(400);
    exit("Invalid pet ID.");
}

// Get pet information owned by the logged-in user
$stmt = $conn->prepare(
    "SELECT * FROM pets
     WHERE id = ? AND user_id = ?
     LIMIT 1"
);
$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();

$pet = $stmt->get_result()->fetch_assoc();

if (!$pet) {
    http_response_code(404);
    exit("Pet not found or access denied.");
}

// Get vaccine records
$stmt = $conn->prepare(
    "SELECT * FROM vaccines
     WHERE pet_id = ?
     ORDER BY date_given DESC"
);
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$vaccine_query = $stmt->get_result();

// Get medical history
$stmt = $conn->prepare(
    "SELECT * FROM medical_history
     WHERE pet_id = ?
     ORDER BY `date` DESC"
);
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$medical_query = $stmt->get_result();

// Get appointments
$stmt = $conn->prepare(
    "SELECT * FROM appointments
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pet Details | Pet-Care</title>

    <!-- CSS CONNECTION -->
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

    <!-- PET ACTION BUTTONS -->
    <div class="pet-action-buttons">

        <a href="add_vaccine.php?pet_id=<?= (int)$pet_id ?>"
           class="button btn-vaccine">
            💉 Add Vaccine
        </a>

        <a href="add_medical.php?pet_id=<?= (int)$pet_id ?>"
           class="button btn-medical">
            🩺 Add Medical Record
        </a>

        <a href="add_appointment.php?pet_id=<?= (int)$pet_id ?>"
           class="button btn-appointment">
            📅 Add Appointment
        </a>

     

        <a href="my_pet.php"
           class="button btn-back">
            ⬅ Back to My Pets
        </a>

    </div>

    <hr>

    <!-- VACCINE HISTORY -->
    <h3>💉 Vaccine History</h3>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Vaccine</th>
                    <th>Date Given</th>
                    <th>Next Schedule</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

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
                        <?php if (empty($row['next_schedule'])): ?>

                            <span class="status-not-scheduled">
                                NOT SCHEDULED
                            </span>

                        <?php elseif ($row['next_schedule'] < $today): ?>

                            <span class="status-overdue">
                                OVERDUE
                            </span>

                        <?php else: ?>

                            <span class="status-upcoming">
                                UPCOMING
                            </span>

                        <?php endif; ?>
                    </td>

                    <td class="table-actions">

                        <a class="edit-link"
                           href="edit_vaccine.php?id=<?= (int)$row['id'] ?>">
                            Edit
                        </a>

                        <form method="POST"
                              action="delete_vaccine.php"
                              class="inline-form"
                              onsubmit="return confirm('Delete this vaccine record?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?= h(csrf_token()) ?>">

                            <input type="hidden"
                                   name="id"
                                   value="<?= (int)$row['id'] ?>">

                            <input type="hidden"
                                   name="pet_id"
                                   value="<?= (int)$pet_id ?>">

                            <button type="submit" class="delete-button">
                                Delete
                            </button>

                        </form>

                    </td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>

    </div>

    <hr>

    <!-- MEDICAL HISTORY -->
    <h3>🩺 Medical History</h3>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

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

                    <td class="table-actions">

                        <a class="edit-link"
                           href="edit_medical.php?id=<?= (int)$row['id'] ?>">
                            Edit
                        </a>

                        <form method="POST"
                              action="delete_medical.php"
                              class="inline-form"
                              onsubmit="return confirm('Delete this medical record?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?= h(csrf_token()) ?>">

                            <input type="hidden"
                                   name="id"
                                   value="<?= (int)$row['id'] ?>">

                            <input type="hidden"
                                   name="pet_id"
                                   value="<?= (int)$pet_id ?>">

                            <button type="submit" class="delete-button">
                                Delete
                            </button>

                        </form>

                    </td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>

    </div>

    <hr>

    <!-- APPOINTMENTS -->
    <h3>📅 Appointments</h3>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

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
                        <?php if ($row['date'] < $today): ?>

                            <span class="status-overdue">
                                PAST
                            </span>

                        <?php else: ?>

                            <span class="status-upcoming">
                                UPCOMING
                            </span>

                        <?php endif; ?>
                    </td>

                    <td class="table-actions">

                        <a class="edit-link"
                           href="edit_appointment.php?id=<?= (int)$row['id'] ?>">
                            Edit
                        </a>

                        <form method="POST"
                              action="delete_appointment.php"
                              class="inline-form"
                              onsubmit="return confirm('Delete this appointment?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?= h(csrf_token()) ?>">

                            <input type="hidden"
                                   name="id"
                                   value="<?= (int)$row['id'] ?>">

                            <input type="hidden"
                                   name="pet_id"
                                   value="<?= (int)$pet_id ?>">

                            <button type="submit" class="delete-button">
                                Delete
                            </button>

                        </form>

                    </td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>

    </div>

  

</div>

</body>
</html>
