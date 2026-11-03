
<?php
require_once '../db/db.php';

$user_id = require_user($conn);
$pet_id = valid_id($_GET['pet_id'] ?? null);

$error = '';

$date = '';
$time = '';
$reason = '';

if (!$pet_id) {
    header('Location: my_pet.php');
    exit;
}

// Verify that this pet belongs to the logged-in user.
$stmt = $conn->prepare(
    "SELECT id, pet_name
     FROM pets
     WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();

$pet = $stmt->get_result()->fetch_assoc();

if (!$pet) {
    http_response_code(403);
    exit('You are not allowed to access this pet.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // Validate appointment date.
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date;

    if (!$validDate || $date < date('Y-m-d')) {
        $error = 'Please select a valid appointment date.';

    // Validate appointment time.
    } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
        $error = 'Please select a valid appointment time.';

    // Validate reason.
    } elseif ($reason === '' || mb_strlen($reason) > 255) {
        $error = 'Please enter a reason (maximum 255 characters).';

    } else {
        // Every new appointment starts with Pending status.
        $status = 'Pending';

        $stmt = $conn->prepare(
            "INSERT INTO appointments
                (pet_id, date, time, reason, status)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issss",
            $pet_id,
            $date,
            $time,
            $reason,
            $status
        );

        if ($stmt->execute()) {
            header("Location: view.php?pet_id=" . $pet_id);
            exit;
        } else {
            $error = 'Unable to submit your appointment. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appointment | Pet-Care</title>
    <link rel="stylesheet" href="./users.css?v=4">
</head>
<body>

<div class="sidebar">
    <h1>🐾 Pet Care</h1>
    <a href="user_dashboard.php">Dashboard</a>
    <a href="add_pet.php">Add Pet</a>
    <a href="my_pet.php">My Pets</a>
    <a href="../Account/logout.php">Logout</a>
</div>

<div class="main">

    <div class="page-title">
        <h2>Add Appointment</h2>
        <p>Pet: <?= h($pet['pet_name']) ?></p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="error-message"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= h(csrf_token()) ?>"
        >

        <label for="date">Appointment Date</label>
        <input
            type="date"
            id="date"
            name="date"
            min="<?= h(date('Y-m-d')) ?>"
            value="<?= h($date) ?>"
            required
        >

        <label for="time">Appointment Time</label>
        <input
            type="time"
            id="time"
            name="time"
            value="<?= h($time) ?>"
            required
        >

        <label for="reason">Reason</label>
        <textarea
            id="reason"
            name="reason"
            maxlength="255"
            required
        ><?= h($reason) ?></textarea>

        <div class="action-buttons">
            <button type="submit">Submit Appointment</button>

            <a class="button"
               href="view.php?pet_id=<?= (int)$pet_id ?>">
                Back to Pet
            </a>
        </div>

    </form>

</div>

</body>
</html>
