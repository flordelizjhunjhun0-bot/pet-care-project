
<?php
require_once '../db/db.php';

$user_id = require_user($conn);
$appointment_id = valid_id($_GET['id'] ?? 0);

$error = '';

if (!$appointment_id) {
    http_response_code(400);
    exit('Invalid appointment ID.');
}

// Get appointment and verify ownership through its pet.
$stmt = $conn->prepare(
    "SELECT appointments.*
     FROM appointments
     INNER JOIN pets ON pets.id = appointments.pet_id
     WHERE appointments.id = ?
     AND pets.user_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();

$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    http_response_code(404);
    exit('Appointment not found or access denied.');
}

$date = $appointment['date'];
$time = $appointment['time'];
$reason = $appointment['reason'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date;

    if (!$validDate || $date < date('Y-m-d')) {
        $error = 'Please select a valid appointment date.';

    } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
        $error = 'Please select a valid appointment time.';

    } elseif ($reason === '' || mb_strlen($reason) > 255) {
        $error = 'Please enter a reason (maximum 255 characters).';

    } else {
        // Return the appointment to Pending for admin review.
        $newStatus = 'Pending';

        $stmt = $conn->prepare(
            "UPDATE appointments
             SET date = ?, time = ?, reason = ?, status = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "ssssi",
            $date,
            $time,
            $reason,
            $newStatus,
            $appointment_id
        );

        if ($stmt->execute()) {
            header(
                "Location: view.php?pet_id=" .
                (int)$appointment['pet_id']
            );
            exit;
        } else {
            $error = 'Unable to update appointment. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment | Pet-Care</title>
    <link rel="stylesheet" href="users.css?v=4">
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
        <h2>✏️ Edit Appointment</h2>
        <p>Update your appointment information.</p>
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

            <button type="submit">
                Update Appointment
            </button>

            <a class="button"
               href="view.php?pet_id=<?= (int)$appointment['pet_id'] ?>">
                Cancel
            </a>

        </div>

    </form>

</div>

</body>
</html>
