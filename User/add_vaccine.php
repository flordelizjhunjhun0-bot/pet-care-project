
<?php

require_once '../db/db.php';

$user_id = require_user($conn);
$pet_id = valid_id($_GET['pet_id'] ?? 0);

$username = $_SESSION['username'] ?? 'Pet Owner';
$error = '';

$vaccine_name = '';
$date_given = date('Y-m-d');
$next_schedule_input = '';

if (!$pet_id) {
    header('Location: my_pet.php');
    exit;
}

/* VERIFY PET OWNERSHIP */
$stmt = $conn->prepare(
    "SELECT id, pet_name
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

/* SAVE VACCINE RECORD */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    $date_given = trim($_POST['date_given'] ?? '');
    $next_schedule_input = trim($_POST['next_schedule'] ?? '');

    /* VALIDATE DATE GIVEN */
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date_given);

    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date_given;

    $next_schedule = null;

    /* VALIDATE NEXT SCHEDULE */
    if ($next_schedule_input !== '') {

        $nextObject = DateTime::createFromFormat(
            '!Y-m-d',
            $next_schedule_input
        );

        if (
            !$nextObject ||
            $nextObject->format('Y-m-d') !== $next_schedule_input
        ) {

            $error = 'Please enter a valid next schedule date.';

        } elseif (
            $validDate &&
            $next_schedule_input < $date_given
        ) {

            $error = 'Next schedule cannot be earlier than the date given.';

        } else {

            $next_schedule = $next_schedule_input;

        }
    }

    /* VALIDATE INPUTS */
    if ($error === '' && $vaccine_name === '') {

        $error = 'Vaccine name is required.';

    } elseif (
        $error === '' &&
        mb_strlen($vaccine_name) > 255
    ) {

        $error = 'Vaccine name must not exceed 255 characters.';

    } elseif ($error === '' && !$validDate) {

        $error = 'Please enter a valid date given.';

    } elseif (
        $error === '' &&
        $date_given > date('Y-m-d')
    ) {

        $error = 'Date given cannot be in the future.';

    }

    /* INSERT VACCINE */
    if ($error === '') {

        $stmt = $conn->prepare(
            "INSERT INTO vaccines
                (pet_id, vaccine_name, date_given, next_schedule)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {

            $error = 'Unable to prepare vaccine record. Please try again.';

        } else {

            $stmt->bind_param(
                "isss",
                $pet_id,
                $vaccine_name,
                $date_given,
                $next_schedule
            );

            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: view.php?pet_id=" . $pet_id
                );

                exit;

            } else {

                $error = 'Unable to save vaccine record. Please try again.';
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

    <title>Add Vaccine | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">

    <style>

        .vaccine-form-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 15px;
            padding: 28px;
            max-width: 750px;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
        }

        .vaccine-form-description {
            color: #6b7785;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .vaccine-form-group {
            margin-bottom: 20px;
        }

        .vaccine-form-group label {
            display: block;
            color: #354454;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .vaccine-form-group input {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid #dce3e8;
            border-radius: 8px;
            background: #ffffff;
            color: #263445;
            font-size: 14px;
            font-family: Arial, Helvetica, sans-serif;
            box-sizing: border-box;
            outline: none;
        }

        .vaccine-form-group input:focus {
            border-color: #285d6b;
            box-shadow: 0 0 0 3px rgba(40,93,107,.10);
        }

        .vaccine-form-help {
            color: #7b8792;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 8px;
        }

        .vaccine-form-error {
            padding: 13px 15px;
            background: #fdeaea;
            color: #b83232;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .vaccine-pet-info {
            display: flex;
            align-items: center;
            gap: 13px;
            background: #f4f7f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 23px;
        }

        .vaccine-pet-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: #e4f0e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .vaccine-pet-info span {
            display: block;
            color: #7b8792;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .vaccine-pet-info strong {
            color: #263445;
            font-size: 15px;
        }

        .vaccine-schedule-note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            background: #fff8e8;
            border: 1px solid #f2e3bd;
            border-radius: 9px;
            padding: 13px 15px;
            margin-top: 5px;
            margin-bottom: 22px;
            color: #805d19;
            font-size: 13px;
            line-height: 1.6;
        }

        .vaccine-form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .vaccine-submit-button,
        .vaccine-cancel-button {
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

        .vaccine-submit-button {
            background: #285d6b;
            color: #ffffff;
        }

        .vaccine-submit-button:hover {
            background: #173b4d;
        }

        .vaccine-cancel-button {
            background: #e9eef2;
            color: #203747;
        }

        .vaccine-cancel-button:hover {
            background: #dce5eb;
        }

        @media(max-width: 768px) {

            .vaccine-form-card {
                padding: 20px;
            }

            .vaccine-form-actions > * {
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
            <span class="topbar-label">PET HEALTH MANAGEMENT</span>
            <h2>Add Vaccine</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= date('F d, Y') ?></strong>
        </div>

    </header>

    <section class="dashboard-section">

        <div class="section-heading">

            <div>
                <span class="section-eyebrow">VACCINATION MANAGEMENT</span>
                <h3>Record Pet Vaccination</h3>
            </div>

        </div>

        <div class="vaccine-form-card">

            <p class="vaccine-form-description">
                Record the vaccine administered to your pet
                and provide the next vaccination schedule, if applicable.
            </p>

            <div class="vaccine-pet-info">

                <div class="vaccine-pet-icon">🐾</div>

                <div>
                    <span>Vaccination record for</span>
                    <strong><?= h($pet['pet_name']) ?></strong>
                </div>

            </div>

            <?php if ($error !== ''): ?>

                <div class="vaccine-form-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(csrf_token()) ?>"
                >

                <div class="vaccine-form-group">

                    <label for="vaccine_name">Vaccine Name</label>

                    <input
                        type="text"
                        id="vaccine_name"
                        name="vaccine_name"
                        maxlength="255"
                        value="<?= h($vaccine_name) ?>"
                        placeholder="e.g. Anti-Rabies Vaccine"
                        required
                    >

                </div>

                <div class="vaccine-form-group">

                    <label for="date_given">Date Given</label>

                    <input
                        type="date"
                        id="date_given"
                        name="date_given"
                        max="<?= date('Y-m-d') ?>"
                        value="<?= h($date_given) ?>"
                        required
                    >

                </div>

                <div class="vaccine-form-group">

                    <label for="next_schedule">
                        Next Schedule
                        <span style="font-weight:400;color:#89939d;">
                            (Optional)
                        </span>
                    </label>

                    <input
                        type="date"
                        id="next_schedule"
                        name="next_schedule"
                        min="<?= h($date_given) ?>"
                        value="<?= h($next_schedule_input) ?>"
                    >

                    <p class="vaccine-form-help">
                        Leave this field blank if no next vaccination
                        schedule has been provided.
                    </p>

                </div>

                <div class="vaccine-schedule-note">

                    <span>ⓘ</span>

                    <span>
                        Enter the next vaccination date based on the
                        veterinarian's recommendation.
                    </span>

                </div>

                <div class="vaccine-form-actions">

                    <button
                        type="submit"
                        class="vaccine-submit-button"
                    >
                        Save Vaccine
                    </button>

                    <a
                        href="view.php?pet_id=<?= (int)$pet_id ?>"
                        class="vaccine-cancel-button"
                    >
                        Back to Pet
                    </a>

                </div>

            </form>

        </div>

    </section>

</main>

</body>
</html>
