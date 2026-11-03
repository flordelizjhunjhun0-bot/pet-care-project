
<?php

require_once '../db/db.php';

$user_id = require_user($conn);
$vaccine_id = valid_id($_GET['id'] ?? 0);

$error = '';

$username = $_SESSION['username'] ?? 'Pet Owner';

if (!$vaccine_id) {
    header('Location: my_pet.php');
    exit;
}

/* GET VACCINE AND VERIFY OWNERSHIP */
$stmt = $conn->prepare(
    "SELECT vaccines.*, pets.user_id, pets.id AS pet_id, pets.pet_name
     FROM vaccines
     INNER JOIN pets ON vaccines.pet_id = pets.id
     WHERE vaccines.id = ?
     AND pets.user_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $vaccine_id, $user_id);
$stmt->execute();

$vaccine = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vaccine) {
    http_response_code(404);
    exit('Vaccine record not found or access denied.');
}

$pet_id = (int)$vaccine['pet_id'];

$vaccine_name = $vaccine['vaccine_name'];
$date_given = $vaccine['date_given'];
$next_schedule = $vaccine['next_schedule'] ?? '';
$scheduling_mode = $vaccine['scheduling_mode'] ?? 'Manual';
$interval_days = $vaccine['interval_days'] ?? '';

/* UPDATE VACCINE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    $date_given = trim($_POST['date_given'] ?? '');
    $scheduling_mode = $_POST['scheduling_mode'] ?? 'Manual';
    $next_schedule = trim($_POST['next_schedule'] ?? '');
    $interval_days = trim($_POST['interval_days'] ?? '');

    /* VALIDATE VACCINE NAME */
    if ($vaccine_name === '' || mb_strlen($vaccine_name) > 100) {

        $error = 'Please enter a valid vaccine name (maximum 100 characters).';

    } else {

        /* VALIDATE DATE GIVEN */
        $dateObject = DateTime::createFromFormat('!Y-m-d', $date_given);

        $validDate = $dateObject &&
            $dateObject->format('Y-m-d') === $date_given;

        if (!$validDate || $date_given > date('Y-m-d')) {

            $error = 'Please enter a valid date given. Future dates are not allowed.';

        } elseif (!in_array($scheduling_mode, ['Manual', 'Automatic'], true)) {

            $error = 'Please select a valid scheduling method.';

        } else {

            $interval_value = null;

            /* AUTOMATIC SCHEDULING */
            if ($scheduling_mode === 'Automatic') {

                if (
                    $interval_days === '' ||
                    !ctype_digit($interval_days) ||
                    (int)$interval_days < 1 ||
                    (int)$interval_days > 3650
                ) {

                    $error = 'Please enter an interval between 1 and 3650 days.';

                } else {

                    $interval_value = (int)$interval_days;

                    $scheduleDate = new DateTime($date_given);

                    $scheduleDate->modify(
                        '+' . $interval_value . ' days'
                    );

                    $next_schedule = $scheduleDate->format('Y-m-d');
                }

            } else {

                /* MANUAL SCHEDULING */
                $interval_value = null;

                $scheduleObject = DateTime::createFromFormat(
                    '!Y-m-d',
                    $next_schedule
                );

                $validSchedule = $scheduleObject &&
                    $scheduleObject->format('Y-m-d') === $next_schedule;

                if (
                    !$validSchedule ||
                    $next_schedule < $date_given
                ) {

                    $error = 'Please enter a valid next schedule date.';

                }
            }

            /* SAVE CHANGES */
            if ($error === '') {

                $stmt = $conn->prepare(
                    "UPDATE vaccines
                     SET vaccine_name = ?,
                         date_given = ?,
                         next_schedule = ?,
                         scheduling_mode = ?,
                         interval_days = ?
                     WHERE id = ?
                     AND pet_id IN (
                         SELECT id
                         FROM pets
                         WHERE user_id = ?
                     )"
                );

                if (!$stmt) {

                    $error = 'Unable to prepare update. Please try again.';

                } else {

                    $stmt->bind_param(
                        "ssssiii",
                        $vaccine_name,
                        $date_given,
                        $next_schedule,
                        $scheduling_mode,
                        $interval_value,
                        $vaccine_id,
                        $user_id
                    );

                    if ($stmt->execute()) {

                        $stmt->close();

                        header(
                            "Location: view.php?pet_id=" . $pet_id
                        );

                        exit;

                    } else {

                        $error = 'Unable to update vaccine record. Please try again.';

                        $stmt->close();
                    }
                }
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

    <title>Edit Vaccine | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">

    <style>

        .edit-form-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 15px;
            padding: 28px;
            max-width: 750px;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
        }

        .edit-form-description {
            color: #6b7785;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .edit-form-group {
            margin-bottom: 20px;
        }

        .edit-form-group label {
            display: block;
            color: #354454;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .edit-form-group input,
        .edit-form-group select {
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

        .edit-form-group input:focus,
        .edit-form-group select:focus {
            border-color: #285d6b;
            box-shadow: 0 0 0 3px rgba(40,93,107,.10);
        }

        .edit-form-error {
            padding: 13px 15px;
            background: #fdeaea;
            color: #b83232;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .edit-form-help {
            color: #7b8792;
            font-size: 12px;
            margin-top: 7px;
        }

        .edit-form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .edit-submit-button,
        .edit-cancel-button {
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

        .edit-submit-button {
            background: #285d6b;
            color: white;
        }

        .edit-submit-button:hover {
            background: #173b4d;
        }

        .edit-cancel-button {
            background: #e9eef2;
            color: #203747;
        }

        .edit-cancel-button:hover {
            background: #dce5eb;
        }

        .schedule-information {
            background: #f4f7f8;
            padding: 14px;
            border-radius: 9px;
            color: #526271;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        @media(max-width: 768px) {

            .edit-form-card {
                padding: 20px;
            }

            .edit-form-actions > * {
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
            <span class="topbar-label">HEALTH RECORDS</span>
            <h2>Edit Vaccine</h2>
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
                <h3>Update Vaccine Information</h3>
            </div>

        </div>

        <div class="edit-form-card">

            <p class="edit-form-description">
                Update the vaccination details for
                <strong><?= h($vaccine['pet_name']) ?></strong>.
            </p>

            <?php if ($error !== ''): ?>

                <div class="edit-form-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(csrf_token()) ?>"
                >

                <div class="edit-form-group">

                    <label for="vaccine_name">Vaccine Name</label>

                    <input
                        type="text"
                        id="vaccine_name"
                        name="vaccine_name"
                        maxlength="100"
                        value="<?= h($vaccine_name) ?>"
                        placeholder="Enter vaccine name"
                        required
                    >

                </div>

                <div class="edit-form-group">

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

                <div class="edit-form-group">

                    <label for="scheduling_mode">Scheduling Method</label>

                    <select
                        name="scheduling_mode"
                        id="scheduling_mode"
                        required
                    >

                        <option value="Manual"
                            <?= $scheduling_mode === 'Manual' ? 'selected' : '' ?>>
                            Manual Scheduling
                        </option>

                        <option value="Automatic"
                            <?= $scheduling_mode === 'Automatic' ? 'selected' : '' ?>>
                            Automatic Scheduling
                        </option>

                    </select>

                </div>

                <div class="schedule-information">

                    <strong>Manual:</strong>
                    Enter the next vaccination date based on the
                    veterinarian's recommendation.

                    <br>

                    <strong>Automatic:</strong>
                    Enter the interval in days. The system calculates
                    the next schedule from the Date Given.

                </div>

                <div class="edit-form-group" id="manualSchedule">

                    <label for="next_schedule">Next Schedule</label>

                    <input
                        type="date"
                        id="next_schedule"
                        name="next_schedule"
                        min="<?= h($date_given) ?>"
                        value="<?= h($next_schedule) ?>"
                    >

                </div>

                <div class="edit-form-group" id="automaticSchedule">

                    <label for="interval_days">Interval (Days)</label>

                    <input
                        type="number"
                        id="interval_days"
                        name="interval_days"
                        min="1"
                        max="3650"
                        value="<?= h((string)$interval_days) ?>"
                        placeholder="Enter interval in days"
                    >

                    <p class="edit-form-help">
                        Use the interval recommended by the veterinarian.
                    </p>

                </div>

                <div class="edit-form-actions">

                    <button type="submit" class="edit-submit-button">
                        Save Changes
                    </button>

                    <a
                        href="view.php?pet_id=<?= (int)$pet_id ?>"
                        class="edit-cancel-button">
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </section>

</main>

<script>

const schedulingMode = document.getElementById('scheduling_mode');
const manualSchedule = document.getElementById('manualSchedule');
const automaticSchedule = document.getElementById('automaticSchedule');

const nextSchedule = document.getElementById('next_schedule');
const intervalDays = document.getElementById('interval_days');

function toggleScheduleFields() {

    if (schedulingMode.value === 'Automatic') {

        manualSchedule.style.display = 'none';
        automaticSchedule.style.display = 'block';

        nextSchedule.required = false;
        intervalDays.required = true;

    } else {

        manualSchedule.style.display = 'block';
        automaticSchedule.style.display = 'none';

        nextSchedule.required = true;
        intervalDays.required = false;

    }

}

schedulingMode.addEventListener('change', toggleScheduleFields);

toggleScheduleFields();

</script>

</body>
</html>
