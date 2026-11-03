
<?php

require_once '../db/db.php';

$user_id = require_user($conn);

$pet_id = valid_id($_GET['pet_id'] ?? 0);

if (!$pet_id) {
    http_response_code(400);
    exit("Invalid pet ID.");
}

/* GET PET INFORMATION */
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
$stmt->close();

if (!$pet) {
    http_response_code(404);
    exit("Pet not found or access denied.");
}

/* GET VACCINE RECORDS */
$stmt = $conn->prepare(
    "SELECT *
     FROM vaccines
     WHERE pet_id = ?
     ORDER BY date_given DESC"
);

$stmt->bind_param("i", $pet_id);
$stmt->execute();

$vaccine_query = $stmt->get_result();
$stmt->close();

/* GET MEDICAL HISTORY */
$stmt = $conn->prepare(
    "SELECT *
     FROM medical_history
     WHERE pet_id = ?
     ORDER BY `date` DESC"
);

$stmt->bind_param("i", $pet_id);
$stmt->execute();

$medical_query = $stmt->get_result();
$stmt->close();

/* GET APPOINTMENTS */
$stmt = $conn->prepare(
    "SELECT *
     FROM appointments
     WHERE pet_id = ?
     ORDER BY `date` DESC, `time` DESC"
);

$stmt->bind_param("i", $pet_id);
$stmt->execute();

$appointment_query = $stmt->get_result();
$stmt->close();

$username = $_SESSION['username'] ?? 'Pet Owner';
$today = date('Y-m-d');

$vaccine_count = $vaccine_query->num_rows;
$medical_count = $medical_query->num_rows;
$appointment_count = $appointment_query->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pet Details | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">

    <style>

        .pet-profile-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
            margin-bottom: 25px;
        }

        .pet-profile-icon {
            width: 75px;
            height: 75px;
            background: #e9f3f0;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            flex-shrink: 0;
        }

        .pet-profile-info h3 {
            color: #173b4d;
            font-size: 23px;
            margin-bottom: 7px;
        }

        .pet-profile-info p {
            color: #6b7785;
            font-size: 14px;
        }

        .pet-record-counts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 28px;
        }

        .pet-count-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 13px;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
        }

        .pet-count-card span {
            display: block;
            color: #6b7785;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .pet-count-card strong {
            color: #173b4d;
            font-size: 27px;
        }

        .pet-record-section {
            margin-bottom: 30px;
        }

        .pet-record-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .pet-record-heading h3 {
            color: #173b4d;
            font-size: 19px;
        }

        .pet-add-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border-radius: 8px;
            background: #285d6b;
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .pet-add-button:hover {
            background: #173b4d;
        }

        .pet-table-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 13px;
            padding: 18px;
            overflow-x: auto;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
        }

        .pet-record-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .pet-record-table th {
            background: #f4f7f8;
            color: #526271;
            padding: 14px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .pet-record-table td {
            padding: 14px;
            border-bottom: 1px solid #edf0f2;
            color: #354454;
            font-size: 13px;
            vertical-align: middle;
        }

        .pet-record-table tr:last-child td {
            border-bottom: none;
        }

        .record-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .record-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .record-edit {
            background: #fff3dc;
            color: #98651a;
        }

        .record-delete {
            background: #fdeaea;
            color: #c03939;
        }

        .record-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-overdue,
        .status-past,
        .status-cancelled {
            background: #fdeaea;
            color: #c03939;
        }

        .status-upcoming,
        .status-approved {
            background: #e5f3eb;
            color: #287548;
        }

        .status-today,
        .status-pending {
            background: #fff3dc;
            color: #98651a;
        }

        .status-completed {
            background: #e8f1f5;
            color: #285d6b;
        }

        .record-empty {
            text-align: center;
            padding: 30px 15px !important;
            color: #8a96a3 !important;
        }

        .pet-back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 8px;
            background: #e9eef2;
            color: #203747;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .pet-back-button:hover {
            background: #dce5eb;
        }

        @media(max-width: 768px) {

            .pet-record-counts {
                grid-template-columns: 1fr;
            }

            .pet-profile-card {
                padding: 18px;
            }

            .pet-profile-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }

            .pet-table-card {
                padding: 10px;
            }

        }

    </style>

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

        <a href="user_dashboard.php">
            <span class="nav-icon">▦</span>
            Dashboard
        </a>

        <a href="add_pet.php">
            <span class="nav-icon">＋</span>
            Add Pet
        </a>

        <a href="my_pet.php" class="active">
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

    <header class="dashboard-topbar">

        <div>
            <span class="topbar-label">PET MANAGEMENT</span>
            <h2>Pet Details</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= date('F d, Y') ?></strong>
        </div>

    </header>

    <!-- PET PROFILE -->
    <section class="dashboard-section">

        <div class="section-heading">
            <div>
                <span class="section-eyebrow">PET INFORMATION</span>
                <h3>Pet Profile</h3>
            </div>
        </div>

        <div class="pet-profile-card">

            <div class="pet-profile-icon">🐾</div>

            <div class="pet-profile-info">

                <h3><?= h($pet['pet_name']) ?></h3>

                <p>
                    Pet Type:
                    <strong><?= h($pet['type']) ?></strong>
                </p>

            </div>

        </div>

        <!-- RECORD COUNTS -->
        <div class="pet-record-counts">

            <div class="pet-count-card">
                <span>Vaccination Records</span>
                <strong><?= $vaccine_count ?></strong>
            </div>

            <div class="pet-count-card">
                <span>Medical Records</span>
                <strong><?= $medical_count ?></strong>
            </div>

            <div class="pet-count-card">
                <span>Appointments</span>
                <strong><?= $appointment_count ?></strong>
            </div>

        </div>

    </section>

    <!-- VACCINATION RECORDS -->
    <section class="pet-record-section">

        <div class="pet-record-heading">

            <div>
                <span class="section-eyebrow">HEALTH RECORDS</span>
                <h3>💉 Vaccination Records</h3>
            </div>

            <a href="add_vaccine.php?pet_id=<?= (int)$pet_id ?>"
               class="pet-add-button">
                ＋ Add Vaccine
            </a>

        </div>

        <div class="pet-table-card">

            <table class="pet-record-table">

                <thead>
                    <tr>
                        <th>Vaccine</th>
                        <th>Date Given</th>
                        <th>Next Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($vaccine_count === 0): ?>

                    <tr>
                        <td colspan="5" class="record-empty">
                            No vaccination records found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php while ($row = $vaccine_query->fetch_assoc()): ?>

                        <?php
                        $next_schedule = $row['next_schedule'] ?? '';

                        if ($next_schedule === '') {
                            $vaccine_status = 'Not Scheduled';
                            $vaccine_class = 'status-today';
                        } elseif ($next_schedule < $today) {
                            $vaccine_status = 'Overdue';
                            $vaccine_class = 'status-overdue';
                        } elseif ($next_schedule === $today) {
                            $vaccine_status = 'Due Today';
                            $vaccine_class = 'status-today';
                        } else {
                            $vaccine_status = 'Upcoming';
                            $vaccine_class = 'status-upcoming';
                        }
                        ?>

                        <tr>

                            <td><?= h($row['vaccine_name']) ?></td>

                            <td><?= h($row['date_given']) ?></td>

                            <td>
                                <?= h($next_schedule ?: 'Not scheduled') ?>
                            </td>

                            <td>
                                <span class="record-status <?= $vaccine_class ?>">
                                    <?= h($vaccine_status) ?>
                                </span>
                            </td>

                            <td>
                                <div class="record-actions">

                                    <a class="record-action record-edit"
                                       href="edit_vaccine.php?id=<?= (int)$row['id'] ?>">
                                        Edit
                                    </a>

                                    <a class="record-action record-delete"
                                       href="delete_vaccine.php?id=<?= (int)$row['id'] ?>&pet_id=<?= (int)$pet_id ?>"
                                       onclick="return confirm('Delete this vaccine record?')">
                                        Delete
                                    </a>

                                </div>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- MEDICAL HISTORY -->
    <section class="pet-record-section">

        <div class="pet-record-heading">

            <div>
                <span class="section-eyebrow">HEALTH RECORDS</span>
                <h3>🩺 Medical History</h3>
            </div>

            <a href="add_medical.php?pet_id=<?= (int)$pet_id ?>"
               class="pet-add-button">
                ＋ Add Medical Record
            </a>

        </div>

        <div class="pet-table-card">

            <table class="pet-record-table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($medical_count === 0): ?>

                    <tr>
                        <td colspan="5" class="record-empty">
                            No medical records found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php while ($row = $medical_query->fetch_assoc()): ?>

                        <tr>

                            <td><?= h($row['date']) ?></td>

                            <td><?= h($row['diagnosis']) ?></td>

                            <td><?= h($row['treatment']) ?></td>

                            <td><?= h($row['notes']) ?></td>

                            <td>

                                <div class="record-actions">

                                    <a class="record-action record-edit"
                                       href="edit_medical.php?id=<?= (int)$row['id'] ?>">
                                        Edit
                                    </a>

                                    <a class="record-action record-delete"
                                       href="delete_medical.php?id=<?= (int)$row['id'] ?>&pet_id=<?= (int)$pet_id ?>"
                                       onclick="return confirm('Delete this medical record?')">
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- APPOINTMENTS -->
    <section class="pet-record-section">

        <div class="pet-record-heading">

            <div>
                <span class="section-eyebrow">SCHEDULE MANAGEMENT</span>
                <h3>📅 Appointments</h3>
            </div>

            <a href="add_appointment.php?pet_id=<?= (int)$pet_id ?>"
               class="pet-add-button">
                ＋ Add Appointment
            </a>

        </div>

        <div class="pet-table-card">

            <table class="pet-record-table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($appointment_count === 0): ?>

                    <tr>
                        <td colspan="5" class="record-empty">
                            No appointments found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php while ($row = $appointment_query->fetch_assoc()): ?>

                        <?php
                        $appointment_status = $row['status'] ?? '';

                        if ($appointment_status === '') {
                            if ($row['date'] < $today) {
                                $appointment_status = 'Past';
                            } elseif ($row['date'] === $today) {
                                $appointment_status = 'Today';
                            } else {
                                $appointment_status = 'Upcoming';
                            }
                        }

                        $status_class = 'status-upcoming';

                        if ($appointment_status === 'Pending') {
                            $status_class = 'status-pending';
                        } elseif ($appointment_status === 'Approved') {
                            $status_class = 'status-approved';
                        } elseif ($appointment_status === 'Completed') {
                            $status_class = 'status-completed';
                        } elseif ($appointment_status === 'Cancelled' ||
                                  $appointment_status === 'Past') {
                            $status_class = 'status-cancelled';
                        } elseif ($appointment_status === 'Today') {
                            $status_class = 'status-today';
                        }
                        ?>

                        <tr>

                            <td><?= h($row['date']) ?></td>

                            <td><?= h($row['time']) ?></td>

                            <td><?= h($row['reason']) ?></td>

                            <td>
                                <span class="record-status <?= $status_class ?>">
                                    <?= h($appointment_status) ?>
                                </span>
                            </td>

                            <td>

                                <div class="record-actions">

                                    <a class="record-action record-edit"
                                       href="edit_appointment.php?id=<?= (int)$row['id'] ?>">
                                        Edit
                                    </a>

                                    <a class="record-action record-delete"
                                       href="delete_appointment.php?id=<?= (int)$row['id'] ?>&pet_id=<?= (int)$pet_id ?>"
                                       onclick="return confirm('Delete this appointment?')">
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- BACK BUTTON -->
    <div style="margin-top: 25px; margin-bottom: 30px;">

        <a href="my_pet.php" class="pet-back-button">
            ← Back to My Pets
        </a>

    </div>

</main>

</body>
</html>
