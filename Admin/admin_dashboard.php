
<?php
require_once '../db/db.php';

/* ADMIN ACCESS CHECK */
$admin_id = require_admin($conn);

/* COUNT HELPER */
function getCount($conn, $sql) {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

/* =========================
   DASHBOARD STATISTICS
========================= */

$totalUsers = getCount($conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"
);

$totalPets = getCount($conn,
    "SELECT COUNT(*) AS total FROM pets"
);

$totalVaccines = getCount($conn,
    "SELECT COUNT(*) AS total FROM vaccines"
);

$totalAppointments = getCount($conn,
    "SELECT COUNT(*) AS total FROM appointments"
);

$pendingUsers = getCount($conn,
    "SELECT COUNT(*) AS total FROM users
     WHERE role = 'user' AND status = 'pending'"
);

$overdueVaccines = getCount($conn,
    "SELECT COUNT(*) AS total FROM vaccines
     WHERE next_schedule IS NOT NULL
     AND next_schedule < CURDATE()"
);

$upcomingVaccines = getCount($conn,
    "SELECT COUNT(*) AS total FROM vaccines
     WHERE next_schedule BETWEEN CURDATE()
     AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
);

$todayAppointments = getCount($conn,
    "SELECT COUNT(*) AS total FROM appointments
     WHERE date = CURDATE()"
);

$upcomingAppointments = getCount($conn,
    "SELECT COUNT(*) AS total FROM appointments
     WHERE date >= CURDATE()"
);

/* =========================
   RECENT USERS
========================= */

$recentuser = mysqli_query($conn,
    "SELECT name, username, status
     FROM users
     WHERE role = 'user'
     ORDER BY id DESC
     LIMIT 5"
);

/* =========================
   RECENT PETS
========================= */

$recentpets = mysqli_query($conn,
    "SELECT p.pet_name, p.type, u.username
     FROM pets p
     JOIN users u ON p.user_id = u.id
     ORDER BY p.id DESC
     LIMIT 5"
);

/* =========================
   UPCOMING APPOINTMENTS
========================= */

$appointments = mysqli_query($conn,
    "SELECT a.date, a.time, a.reason,
            p.pet_name, u.username
     FROM appointments a
     JOIN pets p ON a.pet_id = p.id
     JOIN users u ON p.user_id = u.id
     WHERE a.date >= CURDATE()
     ORDER BY a.date ASC, a.time ASC
     LIMIT 5"
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=3">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h2>🐾 Admin Panel</h2>

    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="manage_users.php">Manage Users</a>
    <a href="manage_pets.php">Manage Pets</a>
    <a href="admin_vaccine.php">Vaccines</a>
    <a href="admin_medical.php">Medical</a>
    <a href="admin_appointment.php">Appointments</a>
    <a href="reports.php">Reports</a>
    <a href="../Account/logout.php">Logout</a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <div class="page-title">
        <h2>Admin Dashboard 📊</h2>
        <p>Welcome to the Pet-Care Management System.</p>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="dashboard-cards">

        <div class="dashboard-card">
            <div class="card-icon">👤</div>
            <div>
                <h3>Total Users</h3>
                <p><?php echo $totalUsers; ?></p>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">🐾</div>
            <div>
                <h3>Total Pets</h3>
                <p><?php echo $totalPets; ?></p>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">💉</div>
            <div>
                <h3>Total Vaccines</h3>
                <p><?php echo $totalVaccines; ?></p>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">📅</div>
            <div>
                <h3>Appointments</h3>
                <p><?php echo $totalAppointments; ?></p>
            </div>
        </div>

        <div class="dashboard-card warning-card">
            <div class="card-icon">⚠️</div>
            <div>
                <h3>Overdue Vaccines</h3>
                <p><?php echo $overdueVaccines; ?></p>
            </div>
        </div>

        <div class="dashboard-card pending-card">
            <div class="card-icon">⏳</div>
            <div>
                <h3>Pending Accounts</h3>
                <p><?php echo $pendingUsers; ?></p>
            </div>
        </div>

    </div>

    <!-- APPOINTMENT AND VACCINE OVERVIEW -->
    <h3 class="section-title">System Overview</h3>

    <div class="overview-grid">

        <div class="overview-card">
            <h3>📅 Appointments Today</h3>
            <p class="overview-number"><?php echo $todayAppointments; ?></p>
            <span>Appointments scheduled for today</span>
        </div>

        <div class="overview-card">
            <h3>🗓️ Upcoming Appointments</h3>
            <p class="overview-number"><?php echo $upcomingAppointments; ?></p>
            <span>Appointments from today onward</span>
        </div>

        <div class="overview-card">
            <h3>💉 Upcoming Vaccinations</h3>
            <p class="overview-number"><?php echo $upcomingVaccines; ?></p>
            <span>Due within the next 30 days</span>
        </div>

        <div class="overview-card">
            <h3>⚠️ Overdue Vaccinations</h3>
            <p class="overview-number"><?php echo $overdueVaccines; ?></p>
            <span>Past their scheduled date</span>
        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <h3 class="section-title">Quick Actions</h3>

    <div class="quick-actions">

        <a href="manage_users.php">👤 Manage Users</a>

        <a href="manage_pets.php">🐾 Manage Pets</a>

        <a href="admin_appointment.php">📅 Manage Appointments</a>

        <a href="reports.php">📊 View Reports</a>

    </div>

    <!-- UPCOMING APPOINTMENTS -->
    <div class="dashboard-section">

        <h3>📅 Upcoming Appointments</h3>

        <div class="table-wrap">

            <table>
                <tr>
                    <th>Owner</th>
                    <th>Pet Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                </tr>

                <?php if ($appointments && mysqli_num_rows($appointments) > 0) { ?>

                    <?php while ($row = mysqli_fetch_assoc($appointments)) { ?>

                        <tr>
                            <td><?php echo h($row['username']); ?></td>
                            <td><?php echo h($row['pet_name']); ?></td>
                            <td><?php echo h($row['date']); ?></td>
                            <td><?php echo h($row['time']); ?></td>
                            <td><?php echo h($row['reason']); ?></td>
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

    <!-- RECENT USERS -->
    <div class="dashboard-section">

        <h3>👤 Recent Users</h3>

        <div class="table-wrap">

            <table>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Status</th>
                </tr>

                <?php if ($recentuser && mysqli_num_rows($recentuser) > 0) { ?>

                    <?php while ($row = mysqli_fetch_assoc($recentuser)) { ?>

                        <tr>
                            <td><?php echo h($row['name']); ?></td>
                            <td><?php echo h($row['username']); ?></td>

                            <td>
                                <?php
                                $status = strtolower($row['status'] ?? '');

                                if ($status === 'active') {
                                    echo '<span class="status-active">Active</span>';
                                } elseif ($status === 'pending') {
                                    echo '<span class="status-pending">Pending</span>';
                                } else {
                                    echo '<span class="status-inactive">Inactive</span>';
                                }
                                ?>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="3" class="empty-message">
                            No registered users found.
                        </td>
                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

    <!-- RECENT PETS -->
    <div class="dashboard-section">

        <h3>🐾 Recent Pets</h3>

        <div class="table-wrap">

            <table>
                <tr>
                    <th>Owner</th>
                    <th>Pet Name</th>
                    <th>Type</th>
                </tr>

                <?php if ($recentpets && mysqli_num_rows($recentpets) > 0) { ?>

                    <?php while ($row = mysqli_fetch_assoc($recentpets)) { ?>

                        <tr>
                            <td><?php echo h($row['username']); ?></td>
                            <td><?php echo h($row['pet_name']); ?></td>
                            <td><?php echo h($row['type']); ?></td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="3" class="empty-message">
                            No pets registered yet.
                        </td>
                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

</div>

</body>
</html>
