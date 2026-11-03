
<?php
require_once '../db/db.php';

/* CHECK USER LOGIN AND ACCOUNT STATUS */
$currentuser = require_user($conn);

/* COUNT HELPER */
function getUserCount($conn, $sql, $user_id) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int)($row['total'] ?? 0);
}

/* =========================
   PET COUNTS
========================= */

$totalpets = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM pets
     WHERE user_id = ? AND LOWER(type) = 'dog'",
    $currentuser
);

$totalpets2 = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM pets
     WHERE user_id = ? AND LOWER(type) = 'cat'",
    $currentuser
);

$totalpets3 = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM pets
     WHERE user_id = ?
     AND (type IS NULL OR LOWER(type) NOT IN ('dog', 'cat'))",
    $currentuser
);

$totalAllPets = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM pets
     WHERE user_id = ?",
    $currentuser
);

/* =========================
   VACCINE COUNTS
========================= */

$vac = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM vaccines v
     INNER JOIN pets p ON v.pet_id = p.id
     WHERE p.user_id = ?",
    $currentuser
);

/* OVERDUE VACCINES */
$overdueVaccines = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM vaccines v
     INNER JOIN pets p ON v.pet_id = p.id
     WHERE p.user_id = ?
     AND v.next_schedule IS NOT NULL
     AND v.next_schedule < CURDATE()",
    $currentuser
);

/* UPCOMING VACCINES WITHIN 30 DAYS */
$upcomingVaccines = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM vaccines v
     INNER JOIN pets p ON v.pet_id = p.id
     WHERE p.user_id = ?
     AND v.next_schedule BETWEEN CURDATE()
     AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
    $currentuser
);

/* =========================
   APPOINTMENT COUNTS
========================= */

$totalAppointments = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM appointments a
     INNER JOIN pets p ON a.pet_id = p.id
     WHERE p.user_id = ?",
    $currentuser
);

$upcomingAppointments = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM appointments a
     INNER JOIN pets p ON a.pet_id = p.id
     WHERE p.user_id = ?
     AND a.date >= CURDATE()",
    $currentuser
);

/* =========================
   UPCOMING APPOINTMENTS
========================= */

$stmt = $conn->prepare(
    "SELECT a.date, a.time, a.reason, p.pet_name
     FROM appointments a
     INNER JOIN pets p ON a.pet_id = p.id
     WHERE p.user_id = ?
     AND a.date >= CURDATE()
     ORDER BY a.date ASC, a.time ASC
     LIMIT 5"
);

$stmt->bind_param("i", $currentuser);
$stmt->execute();
$appointments = $stmt->get_result();

/* =========================
   VACCINATION REMINDERS
========================= */

$stmt = $conn->prepare(
    "SELECT v.vaccine_name, v.next_schedule, p.pet_name
     FROM vaccines v
     INNER JOIN pets p ON v.pet_id = p.id
     WHERE p.user_id = ?
     AND v.next_schedule IS NOT NULL
     AND v.next_schedule <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY v.next_schedule ASC
     LIMIT 5"
);

$stmt->bind_param("i", $currentuser);
$stmt->execute();
$vaccinationReminders = $stmt->get_result();

/* =========================
   RECENT MEDICAL RECORDS
========================= */

$stmt = $conn->prepare(
    "SELECT m.date, m.diagnosis, m.treatment, p.pet_name
     FROM medical_history m
     INNER JOIN pets p ON m.pet_id = p.id
     WHERE p.user_id = ?
     ORDER BY m.date DESC
     LIMIT 5"
);

$stmt->bind_param("i", $currentuser);
$stmt->execute();
$medicalRecords = $stmt->get_result();

$username = $_SESSION['username'] ?? 'Pet Owner';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Dashboard | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=4">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h1>🐾 Pet Care</h1>

    <a href="user_dashboard.php" class="active">Dashboard</a>
    <a href="add_pet.php">Add Pet</a>
    <a href="my_pet.php">My Pets</a>
    <a href="../Account/logout.php">Logout</a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <!-- WELCOME -->
    <div class="text dashboard-welcome">
        <h1>Welcome, <?php echo h($username); ?> 👋</h1>
        <p>Your Pet Care Overview</p>
    </div>

    <!-- PET SUMMARY -->
    <h3 class="dashboard-heading">🐾 My Pets</h3>

    <div class="user-dashboard-cards">

        <div class="user-dashboard-card">
            <div class="dashboard-icon">🐶</div>
            <div>
                <h3>Dogs</h3>
                <p><?php echo $totalpets; ?></p>
            </div>
        </div>

        <div class="user-dashboard-card">
            <div class="dashboard-icon">🐱</div>
            <div>
                <h3>Cats</h3>
                <p><?php echo $totalpets2; ?></p>
            </div>
        </div>

        <div class="user-dashboard-card">
            <div class="dashboard-icon">🐾</div>
            <div>
                <h3>Others</h3>
                <p><?php echo $totalpets3; ?></p>
            </div>
        </div>

        <div class="user-dashboard-card">
            <div class="dashboard-icon">❤️</div>
            <div>
                <h3>Total Pets</h3>
                <p><?php echo $totalAllPets; ?></p>
            </div>
        </div>

    </div>

    <!-- RECORD SUMMARY -->
    <h3 class="dashboard-heading">📋 Pet Care Records</h3>

    <div class="user-dashboard-cards">

        <div class="user-dashboard-card">
            <div class="dashboard-icon">💉</div>
            <div>
                <h3>Total Vaccines</h3>
                <p><?php echo $vac; ?></p>
            </div>
        </div>

        <div class="user-dashboard-card">
            <div class="dashboard-icon">📅</div>
            <div>
                <h3>Total Appointments</h3>
                <p><?php echo $totalAppointments; ?></p>
            </div>
        </div>

        <div class="user-dashboard-card warning-card">
            <div class="dashboard-icon">⚠️</div>
            <div>
                <h3>Overdue Vaccines</h3>
                <p><?php echo $overdueVaccines; ?></p>
            </div>
        </div>

        <div class="user-dashboard-card">
            <div class="dashboard-icon">🔔</div>
            <div>
                <h3>Upcoming Vaccines</h3>
                <p><?php echo $upcomingVaccines; ?></p>
            </div>
        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <h3 class="dashboard-heading">Quick Actions</h3>

    <div class="user-quick-actions">

        <a href="add_pet.php">➕ Add Pet</a>

        <a href="my_pet.php">🐾 View My Pets</a>

    </div>

    <!-- UPCOMING APPOINTMENTS -->
    <div class="user-dashboard-section">

        <h3>📅 Upcoming Appointments</h3>

        <div class="table-wrap">

            <table>
                <tr>
                    <th>Pet</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>

                <?php if ($appointments->num_rows > 0) { ?>

                    <?php while ($row = $appointments->fetch_assoc()) { ?>

                        <tr>
                            <td><?php echo h($row['pet_name']); ?></td>
                            <td><?php echo h($row['date']); ?></td>
                            <td><?php echo h($row['time']); ?></td>
                            <td><?php echo h($row['reason']); ?></td>

                            <td>
                                <?php if ($row['date'] == date('Y-m-d')) { ?>
                                    <span class="appointment-today">Today</span>
                                <?php } else { ?>
                                    <span class="appointment-upcoming">Upcoming</span>
                                <?php } ?>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="5" class="empty-message">
                            No upcoming appointments.
                        </td>
                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

    <!-- VACCINATION REMINDERS -->
    <div class="user-dashboard-section">

        <h3>💉 Vaccination Reminders</h3>

        <div class="table-wrap">

            <table>
                <tr>
                    <th>Pet</th>
                    <th>Vaccine</th>
                    <th>Next Schedule</th>
                    <th>Status</th>
                </tr>

                <?php if ($vaccinationReminders->num_rows > 0) { ?>

                    <?php while ($row = $vaccinationReminders->fetch_assoc()) { ?>

                        <tr>
                            <td><?php echo h($row['pet_name']); ?></td>
                            <td><?php echo h($row['vaccine_name']); ?></td>
                            <td><?php echo h($row['next_schedule']); ?></td>

                            <td>
                                <?php if ($row['next_schedule'] < date('Y-m-d')) { ?>
                                    <span class="vaccine-overdue">Overdue</span>
                                <?php } elseif ($row['next_schedule'] == date('Y-m-d')) { ?>
                                    <span class="appointment-today">Due Today</span>
                                <?php } else { ?>
                                    <span class="appointment-upcoming">Upcoming</span>
                                <?php } ?>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="4" class="empty-message">
                            No vaccination reminders within the next 30 days.
                        </td>
                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

    <!-- RECENT MEDICAL RECORDS -->
    <div class="user-dashboard-section">

        <h3>🩺 Recent Medical Records</h3>

        <div class="table-wrap">

            <table>
                <tr>
                    <th>Pet</th>
                    <th>Date</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                </tr>

                <?php if ($medicalRecords->num_rows > 0) { ?>

                    <?php while ($row = $medicalRecords->fetch_assoc()) { ?>

                        <tr>
                            <td><?php echo h($row['pet_name']); ?></td>
                            <td><?php echo h($row['date']); ?></td>
                            <td><?php echo h($row['diagnosis']); ?></td>
                            <td><?php echo h($row['treatment']); ?></td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="4" class="empty-message">
                            No medical records available.
                        </td>
                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

    <!-- STATUS / ALERTS -->
    <div class="user-dashboard-section dashboard-status">

        <h3>🔔 Pet Care Status</h3>

        <?php if ($overdueVaccines > 0) { ?>

            <p class="alert-warning">
                ⚠️ You have <?php echo $overdueVaccines; ?>
                overdue vaccination schedule(s). Please contact your veterinary clinic.
            </p>

        <?php } elseif ($upcomingVaccines > 0) { ?>

            <p class="alert-success">
                ✅ You have upcoming vaccination schedule(s). Check your reminders above.
            </p>

        <?php } else { ?>

            <p class="alert-success">
                ✅ No overdue or upcoming vaccination reminders at this time.
            </p>

        <?php } ?>

    </div>

</div>

</body>
</html>
