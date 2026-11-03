
<?php
require_once '../db/db.php';

/* CHECK ADMIN ACCESS */
$admin_id = require_admin($conn);

$message = '';
$error = '';

/* =========================
   HANDLE APPOINTMENT ACTIONS
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';
    $appointment_id = valid_id($_POST['appointment_id'] ?? 0);

    if (!$appointment_id) {
        $error = "Invalid appointment ID.";
    }

    /* UPDATE APPOINTMENT STATUS */
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

    /* DELETE APPOINTMENT */
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

/* =========================
   SEARCH AND STATUS FILTER
========================= */

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

/* =========================
   STATUS COUNTS
========================= */

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

    <link rel="stylesheet" href="admin.css?v=3">
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
    <a href="admin_appointment.php" class="active">Appointments</a>
    <a href="reports.php">Reports</a>
    <a href="../Account/logout.php">Logout</a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <div class="page-title">
        <h2>📅 Appointment Management</h2>
        <p>Manage appointment requests and booking status.</p>
    </div>

    <!-- SUCCESS / ERROR MESSAGE -->
    <?php if ($message !== '') { ?>
        <div class="appointment-message success-message">
            <?php echo h($message); ?>
        </div>
    <?php } ?>

    <?php if ($error !== '') { ?>
        <div class="appointment-message error-message">
            <?php echo h($error); ?>
        </div>
    <?php } ?>

    <!-- STATUS SUMMARY -->
    <div class="appointment-summary">

        <div class="appointment-summary-card pending-summary">
            <h3>Pending</h3>
            <p><?php echo $totalPending; ?></p>
        </div>

        <div class="appointment-summary-card approved-summary">
            <h3>Approved</h3>
            <p><?php echo $totalApproved; ?></p>
        </div>

        <div class="appointment-summary-card completed-summary">
            <h3>Completed</h3>
            <p><?php echo $totalCompleted; ?></p>
        </div>

        <div class="appointment-summary-card cancelled-summary">
            <h3>Cancelled</h3>
            <p><?php echo $totalCancelled; ?></p>
        </div>

    </div>

    <!-- SEARCH AND FILTER -->
    <form method="GET" class="appointment-filter">

        <input
            type="text"
            name="search"
            placeholder="Search pet, owner, or reason..."
            value="<?php echo h($search); ?>"
        >

        <select name="status">

            <option value="">All Statuses</option>

            <option value="Pending"
                <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>
                Pending
            </option>

            <option value="Approved"
                <?php echo $filter_status === 'Approved' ? 'selected' : ''; ?>>
                Approved
            </option>

            <option value="Completed"
                <?php echo $filter_status === 'Completed' ? 'selected' : ''; ?>>
                Completed
            </option>

            <option value="Cancelled"
                <?php echo $filter_status === 'Cancelled' ? 'selected' : ''; ?>>
                Cancelled
            </option>

        </select>

        <button type="submit">Search / Filter</button>

        <a href="admin_appointment.php" class="reset-filter">
            Reset
        </a>

    </form>

    <!-- APPOINTMENTS TABLE -->
    <div class="appointment-table-section">

        <h3>Appointment Records</h3>

        <div class="table-wrap">

            <table>

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

                <?php if ($result->num_rows > 0) { ?>

                    <?php while ($row = $result->fetch_assoc()) { ?>

                        <tr>

                            <td><?php echo h($row['owner']); ?></td>

                            <td><?php echo h($row['pet_name']); ?></td>

                            <td><?php echo h($row['date']); ?></td>

                            <td><?php echo h($row['time']); ?></td>

                            <td><?php echo h($row['reason']); ?></td>

                            <td>
                                <?php
                                $statusClass = strtolower($row['status']);

                                if ($statusClass === 'pending') {
                                    $statusClass = 'status-pending';
                                } elseif ($statusClass === 'approved') {
                                    $statusClass = 'status-approved';
                                } elseif ($statusClass === 'completed') {
                                    $statusClass = 'status-completed';
                                } else {
                                    $statusClass = 'status-cancelled';
                                }
                                ?>

                                <span class="<?php echo $statusClass; ?>">
                                    <?php echo h($row['status']); ?>
                                </span>
                            </td>

                            <!-- UPDATE STATUS -->
                            <td>

                                <form method="POST" class="status-update-form">

                                    <input type="hidden" name="csrf_token"
                                           value="<?php echo h(csrf_token()); ?>">

                                    <input type="hidden" name="appointment_id"
                                           value="<?php echo (int)$row['id']; ?>">

                                    <input type="hidden" name="action"
                                           value="update_status">

                                    <select name="status" required>

                                        <option value="Pending"
                                            <?php echo $row['status'] === 'Pending' ? 'selected' : ''; ?>>
                                            Pending
                                        </option>

                                        <option value="Approved"
                                            <?php echo $row['status'] === 'Approved' ? 'selected' : ''; ?>>
                                            Approved
                                        </option>

                                        <option value="Completed"
                                            <?php echo $row['status'] === 'Completed' ? 'selected' : ''; ?>>
                                            Completed
                                        </option>

                                        <option value="Cancelled"
                                            <?php echo $row['status'] === 'Cancelled' ? 'selected' : ''; ?>>
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

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this appointment?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?php echo h(csrf_token()); ?>">

                                    <input type="hidden" name="appointment_id"
                                           value="<?php echo (int)$row['id']; ?>">

                                    <input type="hidden" name="action"
                                           value="delete">

                                    <button type="submit" class="delete-button">
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="8" class="empty-message">
                            No appointments found.
                        </td>
                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

</div>

</body>
</html>
