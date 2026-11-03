
<?php
require_once '../db/db.php';

/* CHECK ADMIN ACCESS */
$admin_id = require_admin($conn);

$message = '';
$error = '';

/* GET ADMIN NAME */
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();
$admin_name = $admin['name'] ?? 'Administrator';
$stmt->close();

/* HANDLE APPOINTMENT ACTIONS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';
    $appointment_id = valid_id($_POST['appointment_id'] ?? 0);

    if (!$appointment_id) {
        $error = "Invalid appointment ID.";
    }

    elseif ($action === 'update_status') {

        $new_status = $_POST['status'] ?? '';

        $allowed_statuses = [
            'Pending',
            'Approved',
            'Completed',
            'Cancelled'
        ];

        if (!in_array($new_status, $allowed_statuses, true)) {
            $error = "Invalid appointment status.";
        } else {

            $stmt = $conn->prepare(
                "UPDATE appointments
                 SET status = ?
                 WHERE id = ?"
            );

            $stmt->bind_param("si", $new_status, $appointment_id);

            if ($stmt->execute()) {
                $message = "Appointment status updated successfully.";
            } else {
                $error = "Unable to update appointment status.";
            }

            $stmt->close();
        }
    }

    elseif ($action === 'delete') {

        $stmt = $conn->prepare(
            "DELETE FROM appointments WHERE id = ?"
        );

        $stmt->bind_param("i", $appointment_id);

        if ($stmt->execute()) {
            $message = "Appointment deleted successfully.";
        } else {
            $error = "Unable to delete appointment.";
        }

        $stmt->close();
    }

    else {
        $error = "Invalid action.";
    }
}

/* SEARCH AND STATUS FILTER */
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$allowed_filters = [
    '',
    'Pending',
    'Approved',
    'Completed',
    'Cancelled'
];

if (!in_array($filter_status, $allowed_filters, true)) {
    $filter_status = '';
}

$searchValue = '%' . $search . '%';

$stmt = $conn->prepare(
    "SELECT
        appointments.id,
        appointments.date,
        appointments.time,
        appointments.reason,
        appointments.status,
        pets.pet_name,
        users.name AS owner
     FROM appointments
     INNER JOIN pets ON pets.id = appointments.pet_id
     INNER JOIN users ON users.id = pets.user_id
     WHERE (
        ? = ''
        OR pets.pet_name LIKE ?
        OR users.name LIKE ?
        OR appointments.reason LIKE ?
     )
     AND (
        ? = ''
        OR appointments.status = ?
     )
     ORDER BY appointments.date DESC, appointments.time DESC"
);

$stmt->bind_param(
    "ssssss",
    $search,
    $searchValue,
    $searchValue,
    $searchValue,
    $filter_status,
    $filter_status
);

$stmt->execute();
$result = $stmt->get_result();

/* STATUS COUNTS */
function getAppointmentCount($conn, $status) {

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE status = ?"
    );

    $stmt->bind_param("s", $status);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return (int)($row['total'] ?? 0);
}

$totalPending = getAppointmentCount($conn, 'Pending');
$totalApproved = getAppointmentCount($conn, 'Approved');
$totalCompleted = getAppointmentCount($conn, 'Completed');
$totalCancelled = getAppointmentCount($conn, 'Cancelled');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Appointment Management | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=5">

    <style>
        /* MESSAGES */
        .appointment-message {
            padding: 13px 17px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .success-message {
            background: #e4f5e9;
            color: #24733d;
            border: 1px solid #b9e3c5;
        }

        .error-message {
            background: #fde8e8;
            color: #c62828;
            border: 1px solid #f5c2c2;
        }

        /* FILTER FORM */
        .appointment-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .appointment-filter input,
        .appointment-filter select {
            min-height: 42px;
            padding: 10px 13px;
            border: 1px solid #dce2e8;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            background: #fff;
            color: #263445;
        }

        .appointment-filter input {
            flex: 1;
            min-width: 200px;
        }

        .appointment-filter select {
            min-width: 155px;
        }

        .appointment-filter input:focus,
        .appointment-filter select:focus {
            border-color: #245c75;
            box-shadow: 0 0 0 3px rgba(36, 92, 117, 0.10);
        }

        .appointment-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 40px;
            padding: 9px 15px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-filter {
            background: #173b4d;
            color: #fff;
        }

        .btn-filter:hover {
            background: #245c75;
        }

        .btn-reset {
            background: #e8edf1;
            color: #34495e;
        }

        .btn-reset:hover {
            background: #d8e0e6;
        }

        /* STATUS BADGES */
        .appointment-status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff2d8;
            color: #a66a00;
        }

        .status-approved {
            background: #e4f5e9;
            color: #24733d;
        }

        .status-completed {
            background: #e4efff;
            color: #245c9c;
        }

        .status-cancelled {
            background: #fde8e8;
            color: #c62828;
        }

        /* UPDATE STATUS FORM */
        .status-update-form {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 115px;
        }

        .status-update-form select {
            width: 100%;
            min-height: 36px;
            padding: 7px 9px;
            border: 1px solid #dce2e8;
            border-radius: 6px;
            outline: none;
            font-size: 12px;
            background: #fff;
        }

        /* ACTION BUTTONS */
        .update-button,
        .delete-button {
            width: 100%;
            min-height: 35px;
            padding: 7px 11px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .update-button {
            background: #245c75;
            color: #fff;
        }

        .update-button:hover {
            background: #173b4d;
        }

        .delete-button {
            background: #dc4c4c;
            color: #fff;
        }

        .delete-button:hover {
            background: #bd3535;
        }

        .appointment-owner {
            font-weight: 600;
            color: #263445;
        }

        .appointment-pet {
            font-weight: 600;
            color: #173b4d;
        }

        .appointment-result-count {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .empty-message {
            text-align: center;
            padding: 30px 15px;
            color: #6b7280;
        }

        @media (max-width: 700px) {
            .appointment-filter {
                flex-direction: column;
            }

            .appointment-filter input,
            .appointment-filter select,
            .appointment-filter button,
            .appointment-filter a {
                width: 100%;
            }
        }
    </style>
</head>

<body class="professional-dashboard">

<!-- SIDEBAR -->
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

        <a href="admin_dashboard.php">
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

        <a href="admin_appointment.php" class="active">
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

<!-- MAIN CONTENT -->
<main class="main professional-main">

    <!-- TOPBAR -->
    <header class="admin-dashboard-topbar">
        <div>
            <h2>Appointment Management</h2>
            <p>Manage appointment requests and booking status.</p>
        </div>

        <div class="date-card">
            📅 <?= date('F d, Y') ?>
        </div>
    </header>

    <!-- SUCCESS / ERROR MESSAGE -->
    <?php if ($message !== ''): ?>
        <div class="appointment-message success-message">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="appointment-message error-message">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <!-- STATUS SUMMARY -->
    <section class="admin-stats-grid">

        <div class="admin-stat-card">
            <div class="admin-stat-icon">⏳</div>
            <div>
                <p>Pending</p>
                <h3><?= number_format($totalPending) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">✓</div>
            <div>
                <p>Approved</p>
                <h3><?= number_format($totalApproved) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">📋</div>
            <div>
                <p>Completed</p>
                <h3><?= number_format($totalCompleted) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">✕</div>
            <div>
                <p>Cancelled</p>
                <h3><?= number_format($totalCancelled) ?></h3>
            </div>
        </div>

    </section>

    <!-- APPOINTMENTS TABLE -->
    <section class="admin-table-card">

        <h3>Appointment Records</h3>

        <!-- SEARCH AND FILTER -->
        <form method="GET" class="appointment-filter">

            <input
                type="text"
                name="search"
                placeholder="Search pet, owner, or reason..."
                value="<?= h($search) ?>"
            >

            <select name="status">
                <option value="">All Statuses</option>

                <option value="Pending"
                    <?= $filter_status === 'Pending' ? 'selected' : '' ?>>
                    Pending
                </option>

                <option value="Approved"
                    <?= $filter_status === 'Approved' ? 'selected' : '' ?>>
                    Approved
                </option>

                <option value="Completed"
                    <?= $filter_status === 'Completed' ? 'selected' : '' ?>>
                    Completed
                </option>

                <option value="Cancelled"
                    <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>
                    Cancelled
                </option>
            </select>

            <button type="submit" class="appointment-btn btn-filter">
                🔍 Search / Filter
            </button>

            <a href="admin_appointment.php" class="appointment-btn btn-reset">
                Reset
            </a>

        </form>

        <p class="appointment-result-count">
            Showing <?= $result->num_rows ?> appointment record(s)
        </p>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Pet</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Update Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <?php
                        $statusClass = match ($row['status']) {
                            'Pending' => 'status-pending',
                            'Approved' => 'status-approved',
                            'Completed' => 'status-completed',
                            'Cancelled' => 'status-cancelled',
                            default => 'status-pending'
                        };
                        ?>

                        <tr>

                            <td class="appointment-owner">
                                <?= h($row['owner']) ?>
                            </td>

                            <td class="appointment-pet">
                                <?= h($row['pet_name']) ?>
                            </td>

                            <td><?= h($row['date']) ?></td>

                            <td><?= h($row['time']) ?></td>

                            <td><?= h($row['reason']) ?></td>

                            <td>
                                <span class="appointment-status <?= $statusClass ?>">
                                    <?= h($row['status']) ?>
                                </span>
                            </td>

                            <!-- UPDATE STATUS -->
                            <td>

                                <form method="POST" class="status-update-form">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int)$row['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update_status"
                                    >

                                    <select name="status" required>
                                        <option value="Pending"
                                            <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>
                                            Pending
                                        </option>

                                        <option value="Approved"
                                            <?= $row['status'] === 'Approved' ? 'selected' : '' ?>>
                                            Approved
                                        </option>

                                        <option value="Completed"
                                            <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>
                                            Completed
                                        </option>

                                        <option value="Cancelled"
                                            <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>
                                            Cancelled
                                        </option>
                                    </select>

                                    <button type="submit" class="update-button">
                                        Update
                                    </button>

                                </form>

                            </td>

                            <!-- DELETE -->
                            <td>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this appointment?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int)$row['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <button type="submit" class="delete-button">
                                        🗑 Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty-message">
                            No appointments found.
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

<?php
$stmt->close();
?>
