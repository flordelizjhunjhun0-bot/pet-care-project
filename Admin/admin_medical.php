
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

$sql = "SELECT medical_history.id,
               users.name AS owner,
               pets.pet_name,
               medical_history.date,
               medical_history.diagnosis,
               medical_history.treatment,
               medical_history.notes
        FROM medical_history
        JOIN pets ON pets.id = medical_history.pet_id
        JOIN users ON users.id = pets.user_id";

if ($search !== '') {
    $searchTerm = "%{$search}%";

    $sql .= " WHERE users.name LIKE ?
              OR pets.pet_name LIKE ?
              OR medical_history.diagnosis LIKE ?
              OR medical_history.treatment LIKE ?";
}

$sql .= " ORDER BY medical_history.date DESC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $stmt->bind_param(
        "ssss",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );
}

$stmt->execute();
$result = $stmt->get_result();

/* MEDICAL STATISTICS */
function getMedicalCount($conn, $sql) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $count = $row['total'] ?? 0;
    $stmt->close();

    return $count;
}

$total_records = getMedicalCount(
    $conn,
    "SELECT COUNT(*) AS total FROM medical_history"
);

$total_pets = getMedicalCount(
    $conn,
    "SELECT COUNT(DISTINCT pet_id) AS total FROM medical_history"
);

$total_diagnoses = getMedicalCount(
    $conn,
    "SELECT COUNT(DISTINCT diagnosis) AS total
     FROM medical_history
     WHERE diagnosis IS NOT NULL AND diagnosis <> ''"
);

$total_treatments = getMedicalCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM medical_history
     WHERE treatment IS NOT NULL AND treatment <> ''"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Medical Records | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=5">

    <style>
        /* SEARCH FORM */
        .medical-search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .medical-search-form input {
            flex: 1;
            min-width: 200px;
            padding: 12px 15px;
            border: 1px solid #dce2e8;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            background: #fff;
        }

        .medical-search-form input:focus {
            border-color: #245c75;
            box-shadow: 0 0 0 3px rgba(36, 92, 117, 0.10);
        }

        .medical-btn {
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
        .medical-action-buttons {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .btn-delete-medical {
            background: #dc4c4c;
            color: #fff;
            padding: 8px 13px;
        }

        .btn-delete-medical:hover {
            background: #bd3535;
        }

        /* TABLE TEXT */
        .medical-owner {
            font-weight: 600;
            color: #263445;
        }

        .medical-pet {
            font-weight: 600;
            color: #173b4d;
        }

        .medical-diagnosis {
            font-weight: 600;
            color: #34495e;
        }

        .medical-notes {
            color: #6b7280;
            max-width: 220px;
            overflow-wrap: anywhere;
        }

        .medical-result-count {
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
            .medical-search-form {
                flex-direction: column;
            }

            .medical-search-form input,
            .medical-search-form button,
            .medical-search-form a {
                width: 100%;
            }

            .medical-action-buttons {
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

        <a href="admin_vaccine.php">
            <span class="admin-nav-icon">💉</span>
            Vaccines
        </a>

        <a href="admin_medical.php" class="active">
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
            <h2>Medical Records</h2>
            <p>View and manage medical history of all registered pets.</p>
        </div>

        <div class="date-card">
            📅 <?= date('F d, Y') ?>
        </div>
    </header>

    <!-- STATISTICS -->
    <section class="admin-stats-grid">

        <div class="admin-stat-card">
            <div class="admin-stat-icon">🩺</div>
            <div>
                <p>Total Medical Records</p>
                <h3><?= number_format($total_records) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">🐾</div>
            <div>
                <p>Pets with Records</p>
                <h3><?= number_format($total_pets) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">📋</div>
            <div>
                <p>Different Diagnoses</p>
                <h3><?= number_format($total_diagnoses) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">💊</div>
            <div>
                <p>Records with Treatment</p>
                <h3><?= number_format($total_treatments) ?></h3>
            </div>
        </div>

    </section>

    <!-- MEDICAL RECORDS TABLE -->
    <section class="admin-table-card">

        <h3>Medical History - All Pets</h3>

        <!-- SEARCH -->
        <form method="GET" class="medical-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search owner, pet, diagnosis, or treatment..."
                value="<?= h($search) ?>"
            >

            <button type="submit" class="medical-btn btn-search">
                🔍 Search
            </button>

            <?php if ($search !== ''): ?>
                <a href="admin_medical.php" class="medical-btn btn-clear">
                    Clear
                </a>
            <?php endif; ?>

        </form>

        <p class="medical-result-count">
            Showing <?= $result->num_rows ?> medical record(s)
        </p>

        <div class="admin-table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Pet</th>
                        <th>Date</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td class="medical-owner">
                                <?= h($row['owner']) ?>
                            </td>

                            <td class="medical-pet">
                                <?= h($row['pet_name']) ?>
                            </td>

                            <td>
                                <?= !empty($row['date'])
                                    ? h($row['date'])
                                    : '—' ?>
                            </td>

                            <td class="medical-diagnosis">
                                <?= !empty($row['diagnosis'])
                                    ? h($row['diagnosis'])
                                    : '—' ?>
                            </td>

                            <td>
                                <?= !empty($row['treatment'])
                                    ? h($row['treatment'])
                                    : '—' ?>
                            </td>

                            <td class="medical-notes">
                                <?= !empty($row['notes'])
                                    ? h($row['notes'])
                                    : '—' ?>
                            </td>

                            <td>
                                <div class="medical-action-buttons">

                                    <a
                                        href="delete_medical_admin.php?id=<?= (int)$row['id'] ?>"
                                        class="medical-btn btn-delete-medical"
                                        onclick="return confirm('Are you sure you want to delete this medical record?');"
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
                                No medical records found matching
                                "<?= h($search) ?>".
                            <?php else: ?>
                                No medical records available.
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
