
<?php
require_once '../db/db.php';

/* CHECK USER LOGIN */
$user_id = require_user($conn);

$error = '';
$pet_name = '';
$type = '';

/* HANDLE FORM SUBMISSION */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $pet_name = trim($_POST['pet_name'] ?? '');
    $type = trim($_POST['type'] ?? '');

    $allowedTypes = ['Dog', 'Cat', 'Bird', 'Rabbit', 'Other'];

    if ($pet_name === '' || $type === '') {
        $error = 'Please complete all fields.';
    } elseif (mb_strlen($pet_name) > 100) {
        $error = 'Pet name must not exceed 100 characters.';
    } elseif (!in_array($type, $allowedTypes, true)) {
        $error = 'Please select a valid pet type.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO pets (user_id, pet_name, type)
             VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            $error = 'Unable to process your request. Please try again.';
        } else {
            $stmt->bind_param("iss", $user_id, $pet_name, $type);

            if ($stmt->execute()) {
                $stmt->close();

                header('Location: my_pet.php?success=pet_added');
                exit;
            } else {
                $error = 'Unable to add your pet. Please try again.';
            }

            $stmt->close();
        }
    }
}

/* USER INFORMATION */
$username = $_SESSION['username'] ?? 'Pet Owner';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Pet | Pet-Care</title>

    <!-- SAME DASHBOARD CSS -->
    <link rel="stylesheet" href="users.css?v=5">

    <!-- ADD PET FORM STYLES USING DASHBOARD COLORS -->
    <style>
        .pet-form-card {
            background: #ffffff;
            border: 1px solid #e6ebef;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(25, 48, 65, 0.035);
            max-width: 750px;
        }

        .pet-form-description {
            color: #81909b;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 6px;
            margin-bottom: 24px;
        }

        .pet-form-group {
            margin-bottom: 22px;
        }

        .pet-form-group label {
            display: block;
            color: #526575;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .pet-form-group input,
        .pet-form-group select {
            display: block;
            width: 100%;
            min-height: 46px;
            padding: 11px 14px;
            border: 1px solid #dce4e9;
            border-radius: 8px;
            background: #ffffff;
            color: #263b4b;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
        }

        .pet-form-group input:focus,
        .pet-form-group select:focus {
            border-color: #327c70;
            box-shadow: 0 0 0 3px rgba(50, 124, 112, 0.10);
        }

        .pet-form-group input::placeholder {
            color: #a0abb3;
        }

        .pet-form-hint {
            display: block;
            color: #8997a1;
            font-size: 11px;
            margin-top: 7px;
        }

        .pet-form-error {
            background: #fce8e8;
            border: 1px solid #f2cccc;
            color: #b43d3d;
            border-radius: 9px;
            padding: 13px 15px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .pet-form-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #edf0f2;
        }

        .pet-form-actions a,
        .pet-form-actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            margin: 0;
        }

        .pet-cancel-button {
            background: #e9eef2;
            color: #526575;
            border: 1px solid #e0e6eb;
        }

        .pet-cancel-button:hover {
            background: #dfe7ec;
        }

        .pet-submit-button {
            background: #173b4d;
            color: #ffffff;
            border: none;
        }

        .pet-submit-button:hover {
            background: #285d6b;
        }

        @media (max-width: 520px) {
            .pet-form-card {
                padding: 18px;
            }

            .pet-form-actions {
                flex-direction: column-reverse;
            }

            .pet-form-actions a,
            .pet-form-actions button {
                width: 100%;
            }
        }
    </style>
</head>

<body class="professional-dashboard">

<!-- SAME SIDEBAR AS DASHBOARD -->
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

        <a href="add_pet.php" class="active">
            <span class="nav-icon">＋</span>
            Add Pet
        </a>

        <a href="my_pet.php">
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

<!-- SAME MAIN LAYOUT AS DASHBOARD -->
<main class="main professional-main">

    <!-- TOP HEADER -->
    <header class="dashboard-topbar">

        <div>
            <span class="topbar-label">PET OWNER PORTAL</span>
            <h2>Add New Pet</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= h(date('F d, Y')) ?></strong>
        </div>

    </header>

    <!-- PAGE CONTENT -->
    <section class="dashboard-section">

        <div class="section-heading">
            <div>
                <span class="section-eyebrow">PET MANAGEMENT</span>
                <h3>Pet Registration</h3>
            </div>
        </div>

        <div class="pet-form-card">

            <h3 style="color:#203747; font-size:18px;">
                🐾 Pet Information
            </h3>

            <p class="pet-form-description">
                Enter your pet's basic information to register it
                in your Pet-Care account.
            </p>

            <?php if ($error !== ''): ?>
                <div class="pet-form-error">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(csrf_token()) ?>"
                >

                <div class="pet-form-group">

                    <label for="pet_name">Pet Name</label>

                    <input
                        type="text"
                        id="pet_name"
                        name="pet_name"
                        maxlength="100"
                        placeholder="Enter your pet's name"
                        value="<?= h($pet_name) ?>"
                        required
                    >

                    <span class="pet-form-hint">
                        Enter the name of your pet.
                    </span>

                </div>

                <div class="pet-form-group">

                    <label for="type">Pet Type</label>

                    <select id="type" name="type" required>

                        <option value="">Select pet type</option>

                        <?php
                        $petTypes = [
                            'Dog',
                            'Cat',
                            'Bird',
                            'Rabbit',
                            'Other'
                        ];
                        ?>

                        <?php foreach ($petTypes as $petType): ?>
                            <option
                                value="<?= h($petType) ?>"
                                <?= $type === $petType ? 'selected' : '' ?>
                            >
                                <?= h($petType) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <span class="pet-form-hint">
                        Choose the appropriate type of your pet.
                    </span>

                </div>

                <div class="pet-form-actions">

                    <a
                        href="my_pet.php"
                        class="pet-cancel-button"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="pet-submit-button"
                    >
                        ＋ Add Pet
                    </button>

                </div>

            </form>

        </div>

    </section>

    <!-- FOOTER -->
    <footer class="dashboard-footer">
        Pet-Care Veterinary Clinic Record System
        <span>© <?= date('Y') ?></span>
    </footer>

</main>

</body>
</html>
