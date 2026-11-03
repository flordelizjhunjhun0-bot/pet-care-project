
<?php
require_once '../db/db.php';

$admin_id = require_admin($conn);

/* GET ADMIN NAME */
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();
$admin_name = $admin['name'] ?? 'Administrator';
$stmt->close();

/* SEARCH */
$search = trim($_GET['search'] ?? '');

$sql = "SELECT vaccines.*, pets.pet_name, users.name AS owner
        FROM vaccines
        JOIN pets ON vaccines.pet_id = pets.id
        JOIN users ON pets.user_id = users.id";

if ($search !== '') {
    $searchTerm = "%{$search}%";

    $sql .= " WHERE vaccines.vaccine_name LIKE ?
              OR pets.pet_name LIKE ?
              OR users.name LIKE ?";
}

$sql .= " ORDER BY vaccines.id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();

$today = date('Y-m-d');

/* VACCINE STATISTICS */
function getVaccineCount($conn, $sql, $today = null) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    if ($today !== null) {
        $stmt->bind_param("s", $today);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $count = $row['total'] ?? 0;
    $stmt->close();

    return $count;
}

$total_vaccines = getVaccineCount(
    $conn,
    "SELECT COUNT(*) AS total FROM vaccines"
);

$overdue_vaccines = getVaccineCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM vaccines
     WHERE next_schedule IS NOT NULL
     AND next_schedule <> ''
     AND next_schedule < ?",
    $today
);

$due_today = getVaccineCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM vaccines
     WHERE next_schedule = ?",
    $today
);

$upcoming_vaccines = getVaccineCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM vaccines
     WHERE next_schedule > ?",
    $today
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccine Management | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=5">

    <style>
        /* SEARCH FORM */
        .vaccine-search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .vaccine-search-form input {
            flex: 1;
            min-width: 200px;
            padding: 12px 15px;
            border: 1px solid #dce2e8;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            background: #fff;
        }

        .vaccine-search-form input:focus {
            border-color: #245c75;
            box-shadow: 0 0 0 3px rgba(36, 92, 117, 0.10);
        }

        .vaccine-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 9px 15px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            min-height: 38px;
            transition: 0.2s ease;
        }

        .btn-search {
            background: #173b4d;
            color: #fff;
        }

        .btn-search:hover {
            background: #245c75;
        }

        .btn-clear {
            background: #e8edf1;
            color: #34495e;
        }

        .btn-clear:hover {
            background: #d8e0e6;
        }

        /* DELETE BUTTON */
        .vaccine-action-buttons {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .btn-delete-vaccine {
            background: #dc4c4c;
            color: #fff;
            padding: 8px 13px;
        }

        .btn-delete-vaccine:hover {
            background: #bd3535;
        }

        /* STATUS BADGES */
        .vaccine-status {
            display: inline-block;
            padding: 6px 11px;
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

        .vaccine-owner {
            font-weight: 600;
            color: #263445;
        }

        .vaccine-pet {
            font-weight: 600;
            color: #173b4d;
        }

        .vaccine-result-count {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .empty-message {
            text-align: center;
            padding: 30px 15px;
            color: #6b7280;
        }

        @media (max-width: 600px) {
            .vaccine-search-form {
                flex-direction: column;
            }

            .vaccine-search-form input,
            .vaccine-search-form button,
            .vaccine-search-form a {
                width: 100%;
            }

            .vaccine-action-buttons {
                flex-direction: column;
                align-items: stretch;
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

        <a href="admin_vaccine.php" class="active">
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

<!-- MAIN CONTENT -->
<main class="main professional-main">

    <!-- TOPBAR -->
    <header class="admin-dashboard-topbar">
        <div>
            <h2>Vaccine Management</h2>
            <p>Monitor vaccine records and upcoming vaccination schedules.</p>
        </div>

        <div class="date-card">
            📅 <?= date('F d, Y') ?>
        </div>
    </header>

    <!-- STATISTICS -->
    <section class="admin-stats-grid">

        <div class="admin-stat-card">
            <div class="admin-stat-icon">💉</div>
            <div>
                <p>Total Vaccine Records</p>
                <h3><?= number_format($total_vaccines) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">⚠️</div>
            <div>
                <p>Overdue</p>
                <h3><?= number_format($overdue_vaccines) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">📅</div>
            <div>
                <p>Due Today</p>
                <h3><?= number_format($due_today) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">✓</div>
            <div>
                <p>Upcoming</p>
                <h3><?= number_format($upcoming_vaccines) ?></h3>
            </div>
        </div>

    </section>

    <!-- VACCINE TABLE -->
    <section class="admin-table-card">

        <h3>Vaccine Records</h3>

        <!-- SEARCH -->
        <form method="GET" class="vaccine-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search vaccine, pet, or owner..."
                value="<?= h($search) ?>"
            >

            <button type="submit" class="vaccine-btn btn-search">
                🔍 Search
            </button>

            <?php if ($search !== ''): ?>
                <a href="admin_vaccine.php" class="vaccine-btn btn-clear">
                    Clear
                </a>
            <?php endif; ?>

        </form>

        <p class="vaccine-result-count">
            Showing <?= $result->num_rows ?> vaccine record(s)
        </p>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Pet</th>
                        <th>Vaccine</th>
                        <th>Date Given</th>
                        <th>Next Schedule</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <?php
                        $next_schedule = $row['next_schedule'] ?? '';

                        if (empty($next_schedule)) {
                            $status_text = 'NO SCHEDULE';
                            $status_class = 'status-none';
                        } elseif ($next_schedule < $today) {
                            $status_text = 'OVERDUE';
                            $status_class = 'status-overdue';
                        } elseif ($next_schedule === $today) {
                            $status_text = 'DUE TODAY';
                            $status_class = 'status-today';
                        } else {
                            $status_text = 'UPCOMING';
                            $status_class = 'status-upcoming';
                        }
                        ?>

                        <tr>

                            <td class="vaccine-owner">
                                <?= h($row['owner']) ?>
                            </td>

                            <td class="vaccine-pet">
                                <?= h($row['pet_name']) ?>
                            </td>

                            <td>
                                <?= h($row['vaccine_name']) ?>
                            </td>

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
                                <span class="vaccine-status <?= $status_class ?>">
                                    <?= $status_text ?>
                                </span>
                            </td>

                            <td>
                                <div class="vaccine-action-buttons">

                                    <a
                                        href="delete_vaccine_admin.php?id=<?= (int)$row['id'] ?>"
                                        class="vaccine-btn btn-delete-vaccine"
                                        onclick="return confirm('Are you sure you want to delete this vaccine record?');"
                                    >
                                        🗑 Delete
                                    </a>

                                </div>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty-message">
                            <?php if ($search !== ''): ?>
                                No vaccine records found matching
                                "<?= h($search) ?>".
                            <?php else: ?>
                                No vaccine records available.
                            <?php endif; ?>
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
