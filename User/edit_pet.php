
<?php

require_once '../db/db.php';

$user_id = require_user($conn);
$pet_id = valid_id($_GET['id'] ?? 0);

$username = $_SESSION['username'] ?? 'Pet Owner';
$error = '';

if (!$pet_id) {
    header('Location: my_pet.php');
    exit;
}

/* GET PET INFORMATION AND VERIFY OWNERSHIP */
$stmt = $conn->prepare(
    "SELECT id, pet_name, type
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
    exit('Pet not found or access denied.');
}

$pet_name = $pet['pet_name'];
$type = $pet['type'];

/* UPDATE PET INFORMATION */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $pet_name = trim($_POST['pet_name'] ?? '');
    $type = trim($_POST['type'] ?? '');

    $allowed_types = [
        'Dog',
        'Cat',
        'Bird',
        'Rabbit',
        'Other'
    ];

    if ($pet_name === '' || mb_strlen($pet_name) > 100) {

        $error = 'Please enter a valid pet name (maximum 100 characters).';

    } elseif (!in_array($type, $allowed_types, true)) {

        $error = 'Please select a valid pet type.';

    } else {

        $stmt = $conn->prepare(
            "UPDATE pets
             SET pet_name = ?, type = ?
             WHERE id = ?
             AND user_id = ?"
        );

        if (!$stmt) {

            $error = 'Unable to prepare update. Please try again.';

        } else {

            $stmt->bind_param(
                "ssii",
                $pet_name,
                $type,
                $pet_id,
                $user_id
            );

            if ($stmt->execute()) {

                $stmt->close();

                header('Location: my_pet.php?success=pet_updated');
                exit;

            } else {

                $error = 'Unable to update pet information. Please try again.';
                $stmt->close();

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Pet | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">

    <style>

        .pet-form-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 15px;
            padding: 28px;
            max-width: 750px;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
        }

        .pet-form-description {
            color: #6b7785;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .pet-form-group {
            margin-bottom: 20px;
        }

        .pet-form-group label {
            display: block;
            color: #354454;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .pet-form-group input,
        .pet-form-group select {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid #dce3e8;
            border-radius: 8px;
            background: #ffffff;
            color: #263445;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
        }

        .pet-form-group input:focus,
        .pet-form-group select:focus {
            border-color: #285d6b;
            box-shadow: 0 0 0 3px rgba(40,93,107,.10);
        }

        .pet-form-error {
            padding: 13px 15px;
            background: #fdeaea;
            color: #b83232;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .pet-form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .pet-submit-button,
        .pet-cancel-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 45px;
            padding: 11px 19px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .pet-submit-button {
            background: #285d6b;
            color: white;
        }

        .pet-submit-button:hover {
            background: #173b4d;
        }

        .pet-cancel-button {
            background: #e9eef2;
            color: #203747;
        }

        .pet-cancel-button:hover {
            background: #dce5eb;
        }

        @media(max-width: 768px) {

            .pet-form-card {
                padding: 20px;
            }

            .pet-form-actions > * {
                width: 100%;
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
            <h2>Edit Pet</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= date('F d, Y') ?></strong>
        </div>

    </header>

    <section class="dashboard-section">

        <div class="section-heading">

            <div>
                <span class="section-eyebrow">PET INFORMATION</span>
                <h3>Update Pet Details</h3>
            </div>

        </div>

        <div class="pet-form-card">

            <p class="pet-form-description">
                Update the information of
                <strong><?= h($pet_name) ?></strong>.
                Make sure the details are correct before saving.
            </p>

            <?php if ($error !== ''): ?>

                <div class="pet-form-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

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
                        value="<?= h($pet_name) ?>"
                        placeholder="Enter pet name"
                        required
                    >

                </div>

                <div class="pet-form-group">

                    <label for="type">Pet Type</label>

                    <select
                        id="type"
                        name="type"
                        required
                    >

                        <option value="">Select Pet Type</option>

                        <?php foreach (['Dog', 'Cat', 'Bird', 'Rabbit', 'Other'] as $petType): ?>

                            <option
                                value="<?= h($petType) ?>"
                                <?= $type === $petType ? 'selected' : '' ?>
                            >
                                <?= h($petType) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="pet-form-actions">

                    <button
                        type="submit"
                        name="update"
                        class="pet-submit-button"
                    >
                        Save Changes
                    </button>

                    <a
                        href="my_pet.php"
                        class="pet-cancel-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </section>

</main>

</body>
</html>
