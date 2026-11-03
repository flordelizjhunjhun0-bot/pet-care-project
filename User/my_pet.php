
<?php
require_once '../db/db.php';

$user_id = require_user($conn);

$username = $_SESSION['username'] ?? 'Pet Owner';
$error = '';
$pets = [];

/* GET PETS OWNED BY THE LOGGED-IN USER */
$stmt = $conn->prepare(
    "SELECT id, pet_name, type
     FROM pets
     WHERE user_id = ?
     ORDER BY id DESC"
);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }

    $stmt->close();
} else {
    $error = 'Unable to load your pets. Please try again.';
}

$petCount = count($pets);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Pets | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">

    <style>
        .pets-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .pets-count-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 14px;
            padding: 18px 22px;
            min-width: 190px;
            box-shadow: 0 4px 14px rgba(20, 40, 55, .04);
        }

        .pets-count-card span {
            display: block;
            color: #6b7785;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .pets-count-card strong {
            display: block;
            color: #173b4d;
            font-size: 27px;
        }

        .pets-add-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #285d6b;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .pets-add-button:hover {
            background: #173b4d;
        }

        .pets-table-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 14px rgba(20, 40, 55, .04);
            overflow-x: auto;
        }

        .pets-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .pets-table th {
            background: #f4f7f8;
            color: #526271;
            text-align: left;
            padding: 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .pets-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #edf0f2;
            color: #354454;
            font-size: 14px;
        }

        .pets-table tr:last-child td {
            border-bottom: none;
        }

        .pet-name-cell {
            font-weight: 600;
            color: #173b4d !important;
        }

        .pet-type-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            background: #e9f3f0;
            color: #285d6b;
            font-size: 12px;
            font-weight: 600;
        }

        .pet-actions {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .pet-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .pet-view {
            background: #e8f1f5;
            color: #285d6b;
        }

        .pet-edit {
            background: #fff3dc;
            color: #98651a;
        }

        .pet-delete {
            background: #fdeaea;
            color: #c03939;
        }

        .pet-action:hover {
            opacity: .8;
        }

        .pets-empty {
            text-align: center;
            padding: 55px 20px;
        }

        .pets-empty-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .pets-empty h3 {
            color: #173b4d;
            margin-bottom: 8px;
        }

        .pets-empty p {
            color: #6b7785;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .pets-error {
            padding: 13px 16px;
            background: #fdeaea;
            color: #b83232;
            border-radius: 9px;
            margin-bottom: 18px;
        }

        @media (max-width: 768px) {
            .pets-summary {
                align-items: stretch;
            }

            .pets-count-card {
                width: 100%;
            }

            .pets-add-button {
                width: 100%;
            }

            .pets-table-card {
                padding: 12px;
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
            <h2>My Pets</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= date('F d, Y') ?></strong>
        </div>
    </header>

    <section class="dashboard-section">

        <div class="section-heading">
            <div>
                <span class="section-eyebrow">YOUR REGISTERED PETS</span>
                <h3>Manage Your Pets</h3>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="pets-error">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <div class="pets-summary">

            <div class="pets-count-card">
                <span>Total Registered Pets</span>
                <strong><?= $petCount ?></strong>
            </div>

            <a href="add_pet.php" class="pets-add-button">
                <span>＋</span>
                Add New Pet
            </a>

        </div>

        <div class="pets-table-card">

            <?php if ($petCount > 0): ?>

                <table class="pets-table">

                    <thead>
                        <tr>
                            <th>Pet Name</th>
                            <th>Pet Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($pets as $pet): ?>

                            <tr>

                                <td class="pet-name-cell">
                                    🐾 <?= h($pet['pet_name']) ?>
                                </td>

                                <td>
                                    <span class="pet-type-badge">
                                        <?= h($pet['type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="pet-actions">

                                        <a
                                            href="view.php?pet_id=<?= (int)$pet['id'] ?>"
                                            class="pet-action pet-view">
                                            View
                                        </a>

                                        <a
                                            href="edit_pet.php?id=<?= (int)$pet['id'] ?>"
                                            class="pet-action pet-edit">
                                            Edit
                                        </a>

                                        <a
                                            href="delete_pet.php?id=<?= (int)$pet['id'] ?>"
                                            class="pet-action pet-delete"
                                            onclick="return confirm('Are you sure you want to delete this pet?');">
                                            Delete
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="pets-empty">

                    <div class="pets-empty-icon">🐾</div>

                    <h3>No Pets Registered Yet</h3>

                    <p>
                        You have not added any pets to your account.
                        Start by registering your first pet.
                    </p>

                    <a href="add_pet.php" class="pets-add-button">
                        ＋ Add Your First Pet
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>
