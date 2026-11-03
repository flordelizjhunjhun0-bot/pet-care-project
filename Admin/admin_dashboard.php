
<?php
require_once '../db/db.php';

$admin_id = require_admin($conn);

/* =====================================
   DASHBOARD COUNT FUNCTION
===================================== */
function getCount($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int)($row['total'] ?? 0);
}

/* =====================================
   MAIN STATISTICS
===================================== */

$total_users = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"
);

$total_pets = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM pets"
);

$total_vaccines = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM vaccines"
);

$total_appointments = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM appointments"
);

$total_medical = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM medical_history"
);

/* =====================================
   USER ACCOUNT OVERVIEW
===================================== */

$pending_users = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user' AND status = 'pending'"
);

$active_users = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'user' AND status = 'active'"
);

/* =====================================
   VACCINE OVERVIEW
===================================== */

$overdue_vaccines = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM vaccines
     WHERE next_schedule IS NOT NULL
     AND next_schedule < CURDATE()"
);

$upcoming_vaccines = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM vaccines
     WHERE next_schedule IS NOT NULL
     AND next_schedule >= CURDATE()
     AND next_schedule <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
);

/* =====================================
   APPOINTMENT OVERVIEW
===================================== */

$today_appointments = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM appointments
     WHERE `date` = CURDATE()
     AND status NOT IN ('Cancelled', 'Completed')"
);

$upcoming_appointments = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM appointments
     WHERE `date` >= CURDATE()
     AND status NOT IN ('Cancelled', 'Completed')"
);

/* =====================================
   RECENT USERS
===================================== */

$recent_users = $conn->query(
    "SELECT name, username, email, status
     FROM users
     WHERE role = 'user'
     ORDER BY id DESC
     LIMIT 5"
);

/* =====================================
   RECENT PETS
===================================== */

$recent_pets = $conn->query(
    "SELECT pets.pet_name, pets.type, users.name AS owner_name
     FROM pets
     INNER JOIN users ON pets.user_id = users.id
     ORDER BY pets.id DESC
     LIMIT 5"
);

/* =====================================
   UPCOMING APPOINTMENTS
===================================== */

$appointments = $conn->query(
    "SELECT
        appointments.id,
        appointments.date,
        appointments.time,
        appointments.reason,
        appointments.status,
        pets.pet_name,
        users.name AS owner_name
     FROM appointments
     INNER JOIN pets ON appointments.pet_id = pets.id
     INNER JOIN users ON pets.user_id = users.id
     WHERE appointments.date >= CURDATE()
     AND appointments.status NOT IN ('Cancelled', 'Completed')
     ORDER BY appointments.date ASC, appointments.time ASC
     LIMIT 5"
);

/* =====================================
   ADMIN INFORMATION
===================================== */

$admin_name = 'Administrator';

$stmt = $conn->prepare(
    "SELECT name FROM users WHERE id = ? LIMIT 1"
);

if ($stmt) {
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    $admin_result = $stmt->get_result();

    if ($admin_row = $admin_result->fetch_assoc()) {
        $admin_name = $admin_row['name'];
    }

    $stmt->close();
}

/* =====================================
   STATUS CLASS FUNCTION
===================================== */

function appointmentStatusClass($status) {
    $status = strtolower(trim($status));

    switch ($status) {
        case 'approved':
            return 'approved';

        case 'completed':
            return 'completed';

        case 'cancelled':
            return 'cancelled';

        default:
            return 'pending';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=5">
</head>

<body class="professional-dashboard">

<!-- =====================================
     SIDEBAR
===================================== -->

<aside class="sidebar">

    <div class="admin-sidebar-brand">

        <div class="admin-brand-icon">🐾</div>

        <div>
            <h1>Pet-Care</h1>
            <span>Veterinary Portal</span>
        </div>

    </div>

    <p class="admin-sidebar-label">MAIN MENU</p>

    <nav class="sidebar-nav">

        <a href="admin_dashboard.php" class="active">
            <span class="admin-nav-icon">▦</span>
            Dashboard
        </a>

        <a href="manage_users.php">
            <span class="admin-nav-icon">👥</span>
            Manage Users
        </a>

        <a href="manage_pets.php">
            <span class="admin-nav-icon">🐾</span>
            Manage Pets
        </a>

        <a href="admin_vaccine.php">
            <span class="admin-nav-icon">💉</span>
            Vaccines
        </a>

        <a href="admin_medical.php">
            <span class="admin-nav-icon">🩺</span>
            Medical Records
        </a>

        <a href="admin_appointment.php">
            <span class="admin-nav-icon">📅</span>
            Appointments
        </a>

        <a href="reports.php">
            <span class="admin-nav-icon">📊</span>
            Reports
        </a>

    </nav>

    <div class="admin-sidebar-bottom">

        <div class="admin-sidebar-user">

            <div class="admin-user-avatar">👤</div>

            <div>
                <strong><?= h($admin_name) ?></strong>
                <span>Administrator</span>
            </div>

        </div>

        <a href="../Account/logout.php" class="admin-logout-link">
            <span class="admin-nav-icon">↪</span>
            Logout
        </a>

    </div>

</aside>


<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="main professional-main">

    <!-- TOPBAR -->

    <header class="admin-dashboard-topbar">

        <div>
            <h2>Admin Dashboard</h2>
            <p>Welcome back! Here's your clinic overview.</p>
        </div>

        <div class="date-card">
            📅 <?= date('F d, Y') ?>
        </div>

    </header>


    <!-- =====================================
         MAIN STATISTICS
    ====================================== -->

    <section class="admin-stats-grid">

        <div class="admin-stat-card">

            <div class="admin-stat-icon">👥</div>

            <div>
                <h3>Total Users</h3>
                <p><?= $total_users ?></p>
            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-icon">🐾</div>

            <div>
                <h3>Total Pets</h3>
                <p><?= $total_pets ?></p>
            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-icon">💉</div>

            <div>
                <h3>Total Vaccines</h3>
                <p><?= $total_vaccines ?></p>
            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-icon">📅</div>

            <div>
                <h3>Appointments</h3>
                <p><?= $total_appointments ?></p>
            </div>

        </div>


        <div class="admin-stat-card">

            <div class="admin-stat-icon">🩺</div>

            <div>
                <h3>Medical Records</h3>
                <p><?= $total_medical ?></p>
            </div>

        </div>

    </section>


    <!-- =====================================
         OVERVIEW
    ====================================== -->

    <h3 class="section-title">Clinic Overview</h3>

    <section class="admin-overview-grid">

        <div class="admin-overview-card">

            <h3>Pending User Accounts</h3>

            <p><?= $pending_users ?></p>

            <span>Accounts waiting for approval</span>

        </div>


        <div class="admin-overview-card">

            <h3>Active Users</h3>

            <p><?= $active_users ?></p>

            <span>Currently active pet owners</span>

        </div>


        <div class="admin-overview-card">

            <h3>Overdue Vaccines</h3>

            <p><?= $overdue_vaccines ?></p>

            <span>Vaccines past their schedule</span>

        </div>


        <div class="admin-overview-card">

            <h3>Upcoming Vaccines</h3>

            <p><?= $upcoming_vaccines ?></p>

            <span>Scheduled within the next 7 days</span>

        </div>


        <div class="admin-overview-card">

            <h3>Today's Appointments</h3>

            <p><?= $today_appointments ?></p>

            <span>Appointments scheduled today</span>

        </div>


        <div class="admin-overview-card">

            <h3>Upcoming Appointments</h3>

            <p><?= $upcoming_appointments ?></p>

            <span>Pending or approved future appointments</span>

        </div>

    </section>


    <!-- =====================================
         QUICK ACTIONS
    ====================================== -->

    <h3 class="section-title">Quick Actions</h3>

    <section class="admin-quick-actions">

        <a href="manage_users.php">
            👥 Manage Users
        </a>

        <a href="manage_pets.php">
            🐾 Manage Pets
        </a>

        <a href="admin_vaccine.php">
            💉 Manage Vaccines
        </a>

        <a href="admin_appointment.php">
            📅 Manage Appointments
        </a>

        <a href="reports.php">
            📊 View Reports
        </a>

    </section>


    <!-- =====================================
         RECENT USERS
    ====================================== -->

    <section class="admin-table-card">

        <h3>Recently Registered Users</h3>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($recent_users && $recent_users->num_rows > 0): ?>

                    <?php while ($user = $recent_users->fetch_assoc()): ?>

                        <?php
                            $user_status = strtolower(trim($user['status'] ?? 'pending'));

                            $user_status_class = in_array(
                                $user_status,
                                ['active', 'pending', 'inactive'],
                                true
                            ) ? $user_status : 'pending';
                        ?>

                        <tr>

                            <td><?= h($user['name']) ?></td>

                            <td><?= h($user['username']) ?></td>

                            <td><?= h($user['email'] ?? '') ?></td>

                            <td>
                                <span class="admin-status <?= h($user_status_class) ?>">
                                    <?= h(ucfirst($user_status)) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="empty-message">
                            No registered users found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- =====================================
         RECENT PETS
    ====================================== -->

    <section class="admin-table-card">

        <h3>Recently Added Pets</h3>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Pet Name</th>
                        <th>Pet Type</th>
                        <th>Owner</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($recent_pets && $recent_pets->num_rows > 0): ?>

                    <?php while ($pet = $recent_pets->fetch_assoc()): ?>

                        <tr>

                            <td><?= h($pet['pet_name']) ?></td>

                            <td><?= h($pet['type']) ?></td>

                            <td><?= h($pet['owner_name']) ?></td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="3" class="empty-message">
                            No pets have been added yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- =====================================
         UPCOMING APPOINTMENTS
    ====================================== -->

    <section class="admin-table-card">

        <h3>Upcoming Appointments</h3>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Pet Name</th>
                        <th>Owner</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($appointments && $appointments->num_rows > 0): ?>

                    <?php while ($appointment = $appointments->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= h($appointment['pet_name']) ?>
                            </td>

                            <td>
                                <?= h($appointment['owner_name']) ?>
                            </td>

                            <td>
                                <?= h(date(
                                    'M d, Y',
                                    strtotime($appointment['date'])
                                )) ?>
                            </td>

                            <td>
                                <?= h($appointment['time']) ?>
                            </td>

                            <td>
                                <?= h($appointment['reason']) ?>
                            </td>

                            <td>

                                <?php
                                    $status = $appointment['status'] ?? 'Pending';
                                    $status_class = appointmentStatusClass($status);
                                ?>

                                <span class="admin-status <?= h($status_class) ?>">
                                    <?= h($status) ?>
                                </span>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty-message">
                            No upcoming appointments.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
