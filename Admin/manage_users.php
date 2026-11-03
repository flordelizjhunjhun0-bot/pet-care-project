
<?php
require_once '../db/db.php';

$admin_id = require_admin($conn);

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
   USER COUNT FUNCTION
===================================== */

function getUserCount($conn, $status = null) {

    if ($status === null) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM users
             WHERE role = 'user'"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM users
             WHERE role = 'user' AND status = ?"
        );

        $stmt->bind_param("s", $status);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0);
}

/* =====================================
   USER STATISTICS
===================================== */

$total_users = getUserCount($conn);
$active_users = getUserCount($conn, 'active');
$pending_users = getUserCount($conn, 'pending');
$inactive_users = getUserCount($conn, 'inactive');

/* =====================================
   SEARCH USERS
===================================== */

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $searchTerm = "%{$search}%";

    $stmt = $conn->prepare(
        "SELECT id, name, username, email, status
         FROM users
         WHERE role = 'user'
         AND (
            name LIKE ?
            OR username LIKE ?
            OR email LIKE ?
         )
         ORDER BY id DESC"
    );

    $stmt->bind_param(
        "sss",
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

} else {

    $stmt = $conn->prepare(
        "SELECT id, name, username, email, status
         FROM users
         WHERE role = 'user'
         ORDER BY id DESC"
    );
}

$stmt->execute();
$users = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Users | Pet-Care</title>

    <link rel="stylesheet" href="admin.css?v=5">

    <style>

        /* =====================================
           PAGE
        ===================================== */

        body.professional-dashboard {
            background: #f4f6f9;
            color: #263445;
        }

        /* =====================================
           SEARCH FORM
        ===================================== */

        .user-search-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .user-search-form input {
            flex: 1;
            min-width: 220px;
            padding: 12px 15px;
            border: 1px solid #dce2e6;
            border-radius: 8px;
            outline: none;
            background: #ffffff;
            font-size: 13px;
            color: #263445;
            transition: 0.2s ease;
        }

        .user-search-form input:focus {
            border-color: #245c3a;
            box-shadow: 0 0 0 3px rgba(36,92,58,0.10);
        }

        /* =====================================
           BUTTON GENERAL
        ===================================== */

        .user-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 13px;
            border: none;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .user-btn:hover {
            transform: translateY(-2px);
            filter: brightness(0.96);
        }

        .user-btn:active {
            transform: translateY(0);
        }

        /* SEARCH */

        .btn-search {
            background: #245c3a;
            color: #ffffff;
        }

        .btn-clear {
            background: #e9ecef;
            color: #475569;
        }

        /* =====================================
           ACTION BUTTONS
        ===================================== */

        .user-action-buttons {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
        }

        .btn-approve,
        .btn-activate {
            background: #e8f5e9;
            color: #245c3a;
        }

        .btn-deactivate {
            background: #fff3cd;
            color: #856404;
        }

        .btn-delete {
            background: #f8d7da;
            color: #b4232f;
        }

        /* =====================================
           TABLE
        ===================================== */

        .admin-table-wrap table {
            min-width: 750px;
        }

        .admin-table-wrap td {
            vertical-align: middle;
        }

        .admin-table-wrap th:last-child,
        .admin-table-wrap td:last-child {
            min-width: 210px;
        }

        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 768px) {

            .user-search-form {
                align-items: stretch;
            }

            .user-search-form input {
                width: 100%;
                min-width: 100%;
            }

            .user-search-form .user-btn {
                flex: 1;
            }

            .user-action-buttons {
                min-width: 190px;
            }

        }

        @media (max-width: 480px) {

            .user-action-buttons {
                gap: 5px;
            }

            .user-btn {
                padding: 8px 10px;
                font-size: 11px;
            }

        }

    </style>

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

        <a href="admin_dashboard.php">
            <span class="admin-nav-icon">▦</span>
            Dashboard
        </a>

        <a href="manage_users.php" class="active">
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
            <h2>Manage Users</h2>
            <p>Manage and monitor registered pet owner accounts.</p>
        </div>

        <div class="date-card">
            📅 <?= date('F d, Y') ?>
        </div>

    </header>


    <!-- =====================================
         USER STATISTICS
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

            <div class="admin-stat-icon">✅</div>

            <div>
                <h3>Active Users</h3>
                <p><?= $active_users ?></p>
            </div>

        </div>

        <div class="admin-stat-card">

            <div class="admin-stat-icon">⏳</div>

            <div>
                <h3>Pending Users</h3>
                <p><?= $pending_users ?></p>
            </div>

        </div>

        <div class="admin-stat-card">

            <div class="admin-stat-icon">🚫</div>

            <div>
                <h3>Deactivated Users</h3>
                <p><?= $inactive_users ?></p>
            </div>

        </div>

    </section>


    <!-- =====================================
         REGISTERED USERS
    ====================================== -->

    <section class="admin-table-card">

        <h3>Registered Users</h3>

        <!-- SEARCH FORM -->

        <form method="GET" class="user-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search name, username, or email..."
                value="<?= h($search) ?>"
            >

            <button type="submit" class="user-btn btn-search">
                🔍 Search
            </button>

            <?php if ($search !== ''): ?>

                <a href="manage_users.php" class="user-btn btn-clear">
                    ✕ Clear
                </a>

            <?php endif; ?>

        </form>


        <!-- USERS TABLE -->

        <div class="admin-table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($users->num_rows > 0): ?>

                    <?php while ($row = $users->fetch_assoc()): ?>

                        <?php

                        $status = strtolower(trim($row['status'] ?? ''));

                        if ($status === 'active') {
                            $statusClass = 'active';
                            $statusLabel = 'Active';
                        } elseif ($status === 'pending') {
                            $statusClass = 'pending';
                            $statusLabel = 'Pending';
                        } else {
                            $statusClass = 'inactive';
                            $statusLabel = 'Deactivated';
                        }

                        ?>

                        <tr>

                            <td><?= h($row['name']) ?></td>

                            <td><?= h($row['username']) ?></td>

                            <td><?= h($row['email'] ?? '') ?></td>

                            <td>

                                <span class="admin-status <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>

                            </td>

                            <td>

                                <div class="user-action-buttons">

                                    <?php if ($status === 'pending'): ?>

                                        <a
                                            href="user_approve.php?id=<?= (int)$row['id'] ?>"
                                            class="user-btn btn-approve"
                                        >
                                            ✓ Approve
                                        </a>

                                    <?php endif; ?>


                                    <?php if ($status === 'active'): ?>

                                        <a
                                            href="user_deactivate.php?id=<?= (int)$row['id'] ?>"
                                            class="user-btn btn-deactivate"
                                        >
                                            ⏸ Deactivate
                                        </a>

                                    <?php else: ?>

                                        <a
                                            href="user_active.php?id=<?= (int)$row['id'] ?>"
                                            class="user-btn btn-activate"
                                        >
                                            ↻ Activate
                                        </a>

                                    <?php endif; ?>


                                    <a
                                        href="user_delete.php?id=<?= (int)$row['id'] ?>"
                                        class="user-btn btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this user?')"
                                    >
                                        🗑 Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="5" class="empty-message">
                            No users found.
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
