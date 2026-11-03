
<?php
require_once '../db/db.php';

/* CHECK ADMIN ACCESS */
$admin_id = require_admin($conn);

/* GET ADMIN NAME */
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();
$admin_name = $admin['name'] ?? 'Administrator';
$stmt->close();

/* TODAY */
$today = date('Y-m-d');

/* VACCINE REPORT */
$stmt = $conn->prepare(
    "SELECT vaccines.*, pets.pet_name, users.name AS owner
     FROM vaccines
     INNER JOIN pets ON vaccines.pet_id = pets.id
     INNER JOIN users ON users.id = pets.user_id
     ORDER BY vaccines.date_given DESC"
);

$stmt->execute();
$result = $stmt->get_result();

/* MEDICAL REPORT */
$stmt_med = $conn->prepare(
    "SELECT medical_history.*, pets.pet_name, users.name AS owner
     FROM medical_history
     INNER JOIN pets ON medical_history.pet_id = pets.id
     INNER JOIN users ON users.id = pets.user_id
     ORDER BY medical_history.date DESC"
);

$stmt_med->execute();
$result_med = $stmt_med->get_result();

/* APPOINTMENT REPORT */
$stmt_app = $conn->prepare(
    "SELECT appointments.*, pets.pet_name, users.name AS owner
     FROM appointments
     INNER JOIN pets ON appointments.pet_id = pets.id
     INNER JOIN users ON users.id = pets.user_id
     ORDER BY appointments.date DESC, appointments.time DESC"
);

$stmt_app->execute();
$result_app = $stmt_app->get_result();

/* REPORT COUNTS */
$total_vaccines = $result->num_rows;
$total_medical = $result_med->num_rows;
$total_appointments = $result_app->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=5">

    <style>
        /* PRINT BUTTON */
        .report-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 19px;
            background: #173b4d;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .print-btn:hover {
            background: #245c75;
        }

        /* REPORT SECTION */
        .report-section {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
        }

        .report-section h3 {
            font-size: 18px;
            color: #263445;
            margin-bottom: 18px;
        }

        .report-section .admin-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .report-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-overdue {
            background: #fde8e8;
            color: #c62828;
        }

        .status-today {
            background: #fff2d8;
            color: #a66a00;
        }

        .status-upcoming {
            background: #e4f5e9;
            color: #24733d;
        }

        .status-none {
            background: #edf0f2;
            color: #687580;
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

        .report-empty {
            text-align: center;
            padding: 25px;
            color: #6b7280;
        }

        .report-owner {
            font-weight: 600;
            color: #263445;
        }

        .report-pet {
            font-weight: 600;
            color: #173b4d;
        }

        .report-count {
            font-size: 13px;
            color: #6b7280;
            margin-top: -10px;
            margin-bottom: 15px;
        }

        .print-header {
            display: none;
        }

        @media (max-width: 700px) {
            .report-section {
                padding: 15px;
            }

            .report-actions {
                justify-content: stretch;
            }

            .print-btn {
                width: 100%;
            }
        }

        /* PRINT LAYOUT */
        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            body,
            body.professional-dashboard {
                background: #fff !important;
                color: #000 !important;
            }

            .sidebar,
            .report-actions,
            .admin-dashboard-topbar {
                display: none !important;
            }

            .main,
            .professional-main {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 25px;
            }

            .print-header h1 {
                font-size: 22px;
                margin-bottom: 5px;
            }

            .print-header p {
                font-size: 12px;
                margin: 3px 0;
            }

            .admin-stats-grid {
                display: none !important;
            }

            .report-section {
                box-shadow: none !important;
                border: 1px solid #ccc;
                border-radius: 0;
                padding: 12px;
                margin-bottom: 20px;
                page-break-inside: auto;
            }

            .report-section h3 {
                color: #000 !important;
                font-size: 15px;
            }

            .admin-table-wrap {
                overflow: visible !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10px !important;
            }

            table th,
            table td {
                border: 1px solid #999 !important;
                padding: 7px !important;
                color: #000 !important;
                background: #fff !important;
            }

            table th {
                font-weight: bold;
                background: #eee !important;
            }

            .report-status {
                border: none !important;
                padding: 0 !important;
                background: transparent !important;
                color: #000 !important;
            }

            tr {
                page-break-inside: avoid;
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

        <a href="admin_appointment.php">
            <span class="admin-nav-icon">📅</span>
            Appointments
        </a>

        <a href="reports.php" class="active">
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
            <h2>Reports</h2>
            <p>View clinic records and generate printable reports.</p>
        </div>

        <div class="date-card">
            📅 <?= date('F d, Y') ?>
        </div>
    </header>

    <!-- PRINT HEADER -->
    <div class="print-header">
        <h1>🐾 Pet-Care Veterinary Clinic</h1>
        <p>Clinic Records and Reports</p>
        <p>Generated on: <?= date('F d, Y') ?></p>
        <p>Prepared by: <?= h($admin_name) ?></p>
    </div>

    <!-- SUMMARY CARDS -->
    <section class="admin-stats-grid">

        <div class="admin-stat-card">
            <div class="admin-stat-icon">💉</div>
            <div>
                <p>Vaccine Records</p>
                <h3><?= number_format($total_vaccines) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">🩺</div>
            <div>
                <p>Medical Records</p>
                <h3><?= number_format($total_medical) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">📅</div>
            <div>
                <p>Appointments</p>
                <h3><?= number_format($total_appointments) ?></h3>
            </div>
        </div>

    </section>

    <!-- PRINT BUTTON -->
    <div class="report-actions">
        <button onclick="window.print()" class="print-btn">
            🖨 Print Reports
        </button>
    </div>

    <!-- VACCINE REPORT -->
    <section class="report-section">

        <h3>💉 Vaccine Report</h3>

        <p class="report-count">
            Total records: <?= number_format($total_vaccines) ?>
        </p>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Pet Name</th>
                        <th>Vaccine</th>
                        <th>Date Given</th>
                        <th>Next Schedule</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <?php
                        $next_schedule = $row['next_schedule'] ?? '';

                        if (empty($next_schedule)) {
                            $vaccine_status = 'NO SCHEDULE';
                            $vaccine_class = 'status-none';
                        } elseif ($next_schedule < $today) {
                            $vaccine_status = 'OVERDUE';
                            $vaccine_class = 'status-overdue';
                        } elseif ($next_schedule === $today) {
                            $vaccine_status = 'DUE TODAY';
                            $vaccine_class = 'status-today';
                        } else {
                            $vaccine_status = 'UPCOMING';
                            $vaccine_class = 'status-upcoming';
                        }
                        ?>

                        <tr>
                            <td class="report-owner">
                                <?= h($row['owner']) ?>
                            </td>

                            <td class="report-pet">
                                <?= h($row['pet_name']) ?>
                            </td>

                            <td><?= h($row['vaccine_name']) ?></td>

                            <td>
                                <?= !empty($row['date_given'])
                                    ? h($row['date_given'])
                                    : '—' ?>
                            </td>

                            <td>
                                <?= !empty($next_schedule)
                                    ? h($next_schedule)
                                    : '—' ?>
                            </td>

                            <td>
                                <span class="report-status <?= $vaccine_class ?>">
                                    <?= $vaccine_status ?>
                                </span>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="report-empty">
                            No vaccine records available.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- MEDICAL HISTORY REPORT -->
    <section class="report-section">

        <h3>🩺 Medical History Report</h3>

        <p class="report-count">
            Total records: <?= number_format($total_medical) ?>
        </p>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Pet Name</th>
                        <th>Date</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                        <th>Notes</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result_med->num_rows > 0): ?>

                    <?php while ($row = $result_med->fetch_assoc()): ?>

                        <tr>
                            <td class="report-owner">
                                <?= h($row['owner']) ?>
                            </td>

                            <td class="report-pet">
                                <?= h($row['pet_name']) ?>
                            </td>

                            <td><?= h($row['date']) ?></td>

                            <td><?= h($row['diagnosis']) ?></td>

                            <td><?= h($row['treatment']) ?></td>

                            <td><?= !empty($row['notes']) ? h($row['notes']) : '—' ?></td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="report-empty">
                            No medical records available.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <!-- APPOINTMENT REPORT -->
    <section class="report-section">

        <h3>📅 Appointment Report</h3>

        <p class="report-count">
            Total records: <?= number_format($total_appointments) ?>
        </p>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Pet Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result_app->num_rows > 0): ?>

                    <?php while ($row = $result_app->fetch_assoc()): ?>

                        <?php
                        $appointment_status = $row['status'] ?? 'Pending';

                        $appointment_class = match ($appointment_status) {
                            'Pending' => 'status-pending',
                            'Approved' => 'status-approved',
                            'Completed' => 'status-completed',
                            'Cancelled' => 'status-cancelled',
                            default => 'status-pending'
                        };
                        ?>

                        <tr>
                            <td class="report-owner">
                                <?= h($row['owner']) ?>
                            </td>

                            <td class="report-pet">
                                <?= h($row['pet_name']) ?>
                            </td>

                            <td><?= h($row['date']) ?></td>

                            <td><?= h($row['time']) ?></td>

                            <td><?= h($row['reason']) ?></td>

                            <td>
                                <span class="report-status <?= $appointment_class ?>">
                                    <?= h($appointment_status) ?>
                                </span>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="report-empty">
                            No appointment records available.
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
$stmt_med->close();
$stmt_app->close();
?>
