
<?php

require_once '../db/db.php';

$user_id = require_user($conn);
$pet_id = valid_id($_GET['pet_id'] ?? 0);

$username = $_SESSION['username'] ?? 'Pet Owner';
$error = '';

$date = '';
$time = '';
$reason = '';

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

/* ADD APPOINTMENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    /* VALIDATE DATE */
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date;

    if (!$validDate || $date < date('Y-m-d')) {

        $error = 'Please select a valid appointment date.';

    } elseif (
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)
    ) {

        $error = 'Please select a valid appointment time.';

    } elseif ($reason === '') {

        $error = 'Please enter the reason for your appointment.';

    } elseif (mb_strlen($reason) > 255) {

        $error = 'Reason must not exceed 255 characters.';

    } else {

        /* NEW APPOINTMENTS START AS PENDING */
        $status = 'Pending';

        $stmt = $conn->prepare(
            "INSERT INTO appointments
                (pet_id, date, time, reason, status)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {

            $error = 'Unable to prepare appointment. Please try again.';

        } else {

            $stmt->bind_param(
                "issss",
                $pet_id,
                $date,
                $time,
                $reason,
                $status
            );

            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: view.php?pet_id=" . $pet_id
                );

                exit;

            } else {

                $error = 'Unable to submit your appointment. Please try again.';
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

    <title>Add Appointment | Pet-Care</title>

    <link rel="stylesheet" href="users.css?v=5">

    <style>

        .appointment-form-card {
            background: #ffffff;
            border: 1px solid #e5eaf0;
            border-radius: 15px;
            padding: 28px;
            max-width: 750px;
            box-shadow: 0 4px 14px rgba(20,40,55,.04);
        }

        .appointment-form-description {
            color: #6b7785;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .appointment-form-group {
            margin-bottom: 20px;
        }

        .appointment-form-group label {
            display: block;
            color: #354454;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .appointment-form-group input,
        .appointment-form-group textarea {
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

        .appointment-form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .appointment-form-group input:focus,
        .appointment-form-group textarea:focus {
            border-color: #285d6b;
            box-shadow: 0 0 0 3px rgba(40,93,107,.10);
        }

        .appointment-form-error {
            padding: 13px 15px;
            background: #fdeaea;
            color: #b83232;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .appointment-form-help {
            color: #7b8792;
            font-size: 12px;
            margin-top: 7px;
        }

        .appointment-pet-info {
            display: flex;
            align-items: center;
            gap: 13px;
            background: #f4f7f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 23px;
        }

        .appointment-pet-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: #e4f0e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .appointment-pet-info span {
            display: block;
            color: #7b8792;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .appointment-pet-info strong {
            color: #263445;
            font-size: 15px;
        }

        .appointment-status-notice {
            padding: 14px 16px;
            background: #fff8e8;
            border: 1px solid #f4dfb2;
            border-radius: 9px;
            color: #85621d;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 22px;
        }

        .appointment-form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .appointment-submit-button,
        .appointment-cancel-button {
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

        .appointment-submit-button {
            background: #285d6b;
            color: white;
        }

        .appointment-submit-button:hover {
            background: #173b4d;
        }

        .appointment-cancel-button {
            background: #e9eef2;
            color: #203747;
        }

        .appointment-cancel-button:hover {
            background: #dce5eb;
        }

        @media(max-width: 768px) {

            .appointment-form-card {
                padding: 20px;
            }

            .appointment-form-actions > * {
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
            <span class="topbar-label">APPOINTMENT MANAGEMENT</span>
            <h2>Add Appointment</h2>
        </div>

        <div class="topbar-date">
            <span>Today</span>
            <strong><?= date('F d, Y') ?></strong>
        </div>

    </header>

    <section class="dashboard-section">

        <div class="section-heading">

            <div>
                <span class="section-eyebrow">APPOINTMENT BOOKING</span>
                <h3>Schedule a Veterinary Visit</h3>
            </div>

        </div>

        <div class="appointment-form-card">

            <p class="appointment-form-description">
                Fill in the appointment details below to request
                a veterinary visit.
            </p>

            <div class="appointment-pet-info">

                <div class="appointment-pet-icon">🐾</div>

                <div>
                    <span>Appointment for</span>
                    <strong><?= h($pet['pet_name']) ?></strong>
                </div>

            </div>

            <div class="appointment-status-notice">
                <strong>Reminder:</strong>
                Your appointment will be submitted with Pending
                status. The administrator must review your request.
            </div>

            <?php if ($error !== ''): ?>

                <div class="appointment-form-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(csrf_token()) ?>"
                >

                <div class="appointment-form-group">

                    <label for="date">Appointment Date</label>

                    <input
                        type="date"
                        id="date"
                        name="date"
                        min="<?= date('Y-m-d') ?>"
                        value="<?= h($date) ?>"
                        required
                    >

                </div>

                <div class="appointment-form-group">

                    <label for="time">Appointment Time</label>

                    <input
                        type="time"
                        id="time"
                        name="time"
                        value="<?= h($time) ?>"
                        required
                    >

                </div>

                <div class="appointment-form-group">

                    <label for="reason">Reason for Appointment</label>

                    <textarea
                        id="reason"
                        name="reason"
                        maxlength="255"
                        placeholder="Describe the reason for the veterinary visit..."
                        required
                    ><?= h($reason) ?></textarea>

                    <p class="appointment-form-help">
                        Maximum of 255 characters.
                    </p>

                </div>

                <div class="appointment-form-actions">

                    <button
                        type="submit"
                        class="appointment-submit-button"
                    >
                        Submit Appointment
                    </button>

                    <a
                        href="view.php?pet_id=<?= (int)$pet_id ?>"
                        class="appointment-cancel-button"
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
