
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

if ($search !== '') {
    $searchTerm = "%{$search}%";

    $stmt = $conn->prepare(
        "SELECT pets.*, users.name AS owner
         FROM pets
         JOIN users ON pets.user_id = users.id
         WHERE pets.pet_name LIKE ?
            OR pets.type LIKE ?
            OR users.name LIKE ?
         ORDER BY pets.id DESC"
    );

    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare(
        "SELECT pets.*, users.name AS owner
         FROM pets
         JOIN users ON pets.user_id = users.id
         ORDER BY pets.id DESC"
    );
}

$stmt->execute();
$result = $stmt->get_result();

/* PET STATISTICS */
function getPetCount($conn, $sql) {
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

$total_pets = getPetCount($conn, "SELECT COUNT(*) AS total FROM pets");

$total_owners = getPetCount(
    $conn,
    "SELECT COUNT(DISTINCT user_id) AS total FROM pets"
);

$total_dogs = getPetCount(
    $conn,
    "SELECT COUNT(*) AS total FROM pets WHERE type = 'Dog'"
);

$total_cats = getPetCount(
    $conn,
    "SELECT COUNT(*) AS total FROM pets WHERE type = 'Cat'"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pets | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=5">

    <style>
        /* SEARCH */
        .pet-search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .pet-search-form input {
            flex: 1;
            min-width: 200px;
            padding: 12px 15px;
            border: 1px solid #dce2e8;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            background: #fff;
        }

        .pet-search-form input:focus {
            border-color: #245c75;
            box-shadow: 0 0 0 3px rgba(36, 92, 117, 0.10);
        }

        .pet-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 17px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            min-height: 38px;
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
        .pet-action-buttons {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .btn-delete-pet {
            background: #dc4c4c;
            color: #fff;
            padding: 8px 13px;
        }

        .btn-delete-pet:hover {
            background: #bd3535;
        }

        /* PET TYPE */
        .pet-type {
            display: inline-block;
            padding: 5px 11px;
            background: #e8f5e9;
            color: #245c3a;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .pet-owner {
            font-weight: 600;
            color: #263445;
        }

        .pet-name {
            font-weight: 600;
            color: #173b4d;
        }

        .pet-result-count {
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
            .pet-search-form {
                flex-direction: column;
            }

            .pet-search-form input,
            .pet-search-form button,
            .pet-search-form a {
                width: 100%;
            }

            .pet-action-buttons {
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

        <a href="manage_pets.php" class="active">
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

<!-- MAIN CONTENT -->
<main class="main professional-main">

    <!-- TOPBAR -->
    <header class="admin-dashboard-topbar">
        <div>
            <h2>Manage Pets</h2>
            <p>View and manage all registered pets in the clinic.</p>
        </div>

        <div class="date-card">
            📅 <?= date('F d, Y') ?>
        </div>
    </header>

    <!-- STATISTICS -->
    <section class="admin-stats-grid">

        <div class="admin-stat-card">
            <div class="admin-stat-icon">🐾</div>
            <div>
                <p>Total Pets</p>
                <h3><?= number_format($total_pets) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">👥</div>
            <div>
                <p>Pet Owners</p>
                <h3><?= number_format($total_owners) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">🐶</div>
            <div>
                <p>Dogs</p>
                <h3><?= number_format($total_dogs) ?></h3>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">🐱</div>
            <div>
                <p>Cats</p>
                <h3><?= number_format($total_cats) ?></h3>
            </div>
        </div>

    </section>

    <!-- PET TABLE -->
    <section class="admin-table-card">

        <h3>Registered Pets</h3>

        <!-- SEARCH -->
        <form method="GET" class="pet-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search pet name, type, or owner..."
                value="<?= h($search) ?>"
            >

            <button type="submit" class="pet-btn btn-search">
                🔍 Search
            </button>

            <?php if ($search !== ''): ?>
                <a href="manage_pets.php" class="pet-btn btn-clear">
                    Clear
                </a>
            <?php endif; ?>

        </form>

        <p class="pet-result-count">
            Showing <?= $result->num_rows ?> pet record(s)
        </p>

        <div class="admin-table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Pet Name</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td class="pet-owner">
                                <?= h($row['owner']) ?>
                            </td>

                            <td class="pet-name">
                                <?= h($row['pet_name']) ?>
                            </td>

                            <td>
                                <span class="pet-type">
                                    <?= h($row['type']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="pet-action-buttons">
                                    <a
                                        href="delete_pet_admin.php?id=<?= (int)$row['id'] ?>"
                                        class="pet-btn btn-delete-pet"
                                        onclick="return confirm('Are you sure you want to delete this pet? This action cannot be undone.');"
                                    >
                                        🗑 Delete
                                    </a>
                                </div>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="empty-message">
                            <?php if ($search !== ''): ?>
                                No pets found matching "<?= h($search) ?>".
                            <?php else: ?>
                                No registered pets found.
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
