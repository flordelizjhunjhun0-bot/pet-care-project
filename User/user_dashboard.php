
<?php
require_once '../db/db.php';

/* CHECK USER LOGIN */
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

/* PET COUNTS */
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

/* VACCINE COUNTS */
$vac = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM vaccines v
     INNER JOIN pets p ON v.pet_id = p.id
     WHERE p.user_id = ?",
    $currentuser
);

$overdueVaccines = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM vaccines v
     INNER JOIN pets p ON v.pet_id = p.id
     WHERE p.user_id = ?
     AND v.next_schedule IS NOT NULL
     AND v.next_schedule < CURDATE()",
    $currentuser
);

$upcomingVaccines = getUserCount($conn,
    "SELECT COUNT(*) AS total
     FROM vaccines v
     INNER JOIN pets p ON v.pet_id = p.id
     WHERE p.user_id = ?
     AND v.next_schedule BETWEEN CURDATE()
     AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
    $currentuser
);

/* APPOINTMENT COUNTS */
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
     AND a.date >= CURDATE()
     AND a.status IN ('Pending', 'Approved')",
    $currentuser
);

/* UPCOMING APPOINTMENTS */
$stmt = $conn->prepare(
    "SELECT a.date, a.time, a.reason, a.status, p.pet_name
     FROM appointments a
     INNER JOIN pets p ON a.pet_id = p.id
     WHERE p.user_id = ?
     AND a.date >= CURDATE()
     AND a.status IN ('Pending', 'Approved')
     ORDER BY a.date ASC, a.time ASC
     LIMIT 5"
);

$stmt->bind_param("i", $currentuser);
$stmt->execute();
$appointments = $stmt->get_result();

/* VACCINATION REMINDERS */
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

/* RECENT MEDICAL RECORDS */
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

/* USERNAME */
$username = $_SESSION['username'] ?? 'Pet Owner';

/* DATE */
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Dashboard | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">
</head>

<body class="professional-dashboard">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🐾</div>
        <div>
            <h1>Pet-Care</h1>
            <span>Veterinary Portal</span>
        </div>
    </div>

    <p class="sidebar-label">MAIN MENU</p>

    <nav class="sidebar-nav">
        <a href="user_dashboard.php" class="active">
            <span class="nav-icon">▦</span>
            Dashboard
        </a>

        <a href="add_pet.php">
            <span class="nav-icon">＋</span>
            Add Pet
        </a>

        <a href="my_pet.php">
            <span class="nav-icon">🐾</span>
            My Pets
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <div class="user-avatar">👤</div>
            <div>
                <strong><?= h($username) ?></strong>
                <span>Pet Owner</span>
            </div>
        </div>

        <a href="../Account/logout.php" class="logout-link">
            <span class="nav-icon">↪</span>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main professional-main">

    <!-- TOP HEADER -->
    <header class="dashboard-topbar">
        <div>
            <span class="topbar-label">PET OWNER PORTAL</span>
            <h2>Dashboard</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= h(date('F d, Y')) ?></strong>
        </div>
    </header>

    <!-- WELCOME -->
    <section class="welcome-panel">
        <div>
            <span class="welcome-eyebrow">WELCOME BACK</span>
            <h1>Hello, <?= h($username) ?>!</h1>
            <p>Keep track of your pets' health, appointments, and vaccination schedules.</p>

            <a href="my_pet.php" class="welcome-button">
                View My Pets <span>→</span>
            </a>
        </div>

        <div class="welcome-illustration">🐶</div>
    </section>

    <!-- PET OVERVIEW -->
    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <span class="section-eyebrow">OVERVIEW</span>
                <h3>My Pets</h3>
            </div>
            <a href="my_pet.php" class="section-link">View all pets →</a>
        </div>

        <div class="professional-stat-grid">

            <div class="professional-stat-card">
                <div class="stat-top">
                    <span class="stat-icon dog-icon">🐶</span>
                    <span class="stat-caption">DOGS</span>
                </div>
                <strong><?= $totalpets ?></strong>
                <p>Registered dogs</p>
            </div>

            <div class="professional-stat-card">
                <div class="stat-top">
                    <span class="stat-icon cat-icon">🐱</span>
                    <span class="stat-caption">CATS</span>
                </div>
                <strong><?= $totalpets2 ?></strong>
                <p>Registered cats</p>
            </div>

            <div class="professional-stat-card">
                <div class="stat-top">
                    <span class="stat-icon other-icon">🐾</span>
                    <span class="stat-caption">OTHERS</span>
                </div>
                <strong><?= $totalpets3 ?></strong>
                <p>Other pet types</p>
            </div>

            <div class="professional-stat-card highlight-stat">
                <div class="stat-top">
                    <span class="stat-icon total-icon">❤️</span>
                    <span class="stat-caption">TOTAL PETS</span>
                </div>
                <strong><?= $totalAllPets ?></strong>
                <p>All registered pets</p>
            </div>

        </div>
    </section>

    <!-- HEALTH RECORD OVERVIEW -->
    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <span class="section-eyebrow">HEALTH MONITORING</span>
                <h3>Pet Care Records</h3>
            </div>
        </div>

        <div class="professional-stat-grid">

            <div class="professional-stat-card">
                <div class="stat-top">
                    <span class="stat-icon vaccine-icon">💉</span>
                    <span class="stat-caption">VACCINES</span>
                </div>
                <strong><?= $vac ?></strong>
                <p>Total vaccine records</p>
            </div>

            <div class="professional-stat-card">
                <div class="stat-top">
                    <span class="stat-icon appointment-icon">📅</span>
                    <span class="stat-caption">APPOINTMENTS</span>
                </div>
                <strong><?= $totalAppointments ?></strong>
                <p>Total appointment records</p>
            </div>

            <div class="professional-stat-card warning-stat">
                <div class="stat-top">
                    <span class="stat-icon overdue-icon">⚠️</span>
                    <span class="stat-caption">OVERDUE</span>
                </div>
                <strong><?= $overdueVaccines ?></strong>
                <p>Overdue vaccinations</p>
            </div>

            <div class="professional-stat-card">
                <div class="stat-top">
                    <span class="stat-icon upcoming-icon">🔔</span>
                    <span class="stat-caption">UPCOMING</span>
                </div>
                <strong><?= $upcomingVaccines ?></strong>
                <p>Vaccines due within 30 days</p>
            </div>

        </div>
    </section>

    <!-- QUICK ACTIONS -->
    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <span class="section-eyebrow">SHORTCUTS</span>
                <h3>Quick Actions</h3>
            </div>
        </div>

        <div class="professional-quick-actions">
            <a href="add_pet.php">
                <span>＋</span>
                <div>
                    <strong>Add New Pet</strong>
                    <small>Register a pet</small>
                </div>
                <b>→</b>
            </a>

            <a href="my_pet.php">
                <span>🐾</span>
                <div>
                    <strong>Manage My Pets</strong>
                    <small>View pet information</small>
                </div>
                <b>→</b>
            </a>
        </div>
    </section>

    <!-- APPOINTMENTS -->
    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <span class="section-eyebrow">SCHEDULE</span>
                <h3>Upcoming Appointments</h3>
            </div>
            <span class="section-count"><?= $upcomingAppointments ?> upcoming</span>
        </div>

        <div class="professional-table-wrap">
            <table class="professional-table">
                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($appointments->num_rows > 0): ?>
                    <?php while ($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= h($row['pet_name']) ?></strong></td>
                            <td><?= h(date('M d, Y', strtotime($row['date']))) ?></td>
                            <td><?= h(date('h:i A', strtotime($row['time']))) ?></td>
                            <td><?= h($row['reason']) ?></td>
                            <td>
                                <?php
                                $appointmentStatus = $row['status'] ?? 'Pending';
                                $appointmentClass = strtolower($appointmentStatus);
                                ?>
                                <span class="status-badge status-<?= h($appointmentClass) ?>">
                                    <?= h($appointmentStatus) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="professional-empty">
                            <span>📅</span>
                            <strong>No upcoming appointments</strong>
                            <small>Your upcoming appointments will appear here.</small>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- VACCINATION REMINDERS -->
    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <span class="section-eyebrow">HEALTH REMINDERS</span>
                <h3>Vaccination Reminders</h3>
            </div>
            <span class="section-count">Next 30 days + overdue</span>
        </div>

        <div class="professional-table-wrap">
            <table class="professional-table">
                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Vaccine</th>
                        <th>Next Schedule</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($vaccinationReminders->num_rows > 0): ?>
                    <?php while ($row = $vaccinationReminders->fetch_assoc()): ?>
                        <?php
                        $schedule = $row['next_schedule'];

                        if ($schedule < $today) {
                            $vaccineStatus = 'Overdue';
                            $vaccineClass = 'overdue';
                        } elseif ($schedule === $today) {
                            $vaccineStatus = 'Due Today';
                            $vaccineClass = 'today';
                        } else {
                            $vaccineStatus = 'Upcoming';
                            $vaccineClass = 'upcoming';
                        }
                        ?>
                        <tr>
                            <td><strong><?= h($row['pet_name']) ?></strong></td>
                            <td><?= h($row['vaccine_name']) ?></td>
                            <td><?= h(date('M d, Y', strtotime($schedule))) ?></td>
                            <td>
                                <span class="status-badge status-<?= h($vaccineClass) ?>">
                                    <?= h($vaccineStatus) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="professional-empty">
                            <span>💉</span>
                            <strong>No vaccination reminders</strong>
                            <small>No overdue or upcoming schedules within the next 30 days.</small>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- MEDICAL RECORDS -->
    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <span class="section-eyebrow">HEALTH HISTORY</span>
                <h3>Recent Medical Records</h3>
            </div>
        </div>

        <div class="professional-table-wrap">
            <table class="professional-table">
                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Date</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($medicalRecords->num_rows > 0): ?>
                    <?php while ($row = $medicalRecords->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= h($row['pet_name']) ?></strong></td>
                            <td><?= h(date('M d, Y', strtotime($row['date']))) ?></td>
                            <td><?= h($row['diagnosis']) ?></td>
                            <td><?= h($row['treatment']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="professional-empty">
                            <span>🩺</span>
                            <strong>No medical records yet</strong>
                            <small>Medical records for your pets will appear here.</small>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- PET CARE STATUS -->
    <section class="dashboard-section">
        <div class="section-heading">
            <div>
                <span class="section-eyebrow">IMPORTANT INFORMATION</span>
                <h3>Pet Care Status</h3>
            </div>
        </div>

        <?php if ($overdueVaccines > 0): ?>
            <div class="dashboard-notice notice-warning">
                <span>⚠️</span>
                <div>
                    <strong>Vaccination attention required</strong>
                    <p>
                        You have <?= $overdueVaccines ?> overdue vaccination
                        schedule(s). Please contact your veterinary clinic.
                    </p>
                </div>
            </div>

        <?php elseif ($upcomingVaccines > 0): ?>
            <div class="dashboard-notice notice-success">
                <span>✓</span>
                <div>
                    <strong>Upcoming vaccination schedules</strong>
                    <p>
                        You have upcoming vaccination schedules.
                        Please review your reminders above.
                    </p>
                </div>
            </div>

        <?php else: ?>
            <div class="dashboard-notice notice-success">
                <span>✓</span>
                <div>
                    <strong>No vaccination alerts</strong>
                    <p>
                        There are no overdue or upcoming vaccination
                        reminders within the next 30 days.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <footer class="dashboard-footer">
        Pet-Care Veterinary Clinic Record System
        <span>© <?= date('Y') ?></span>
    </footer>

</main>

</body>
</html>
