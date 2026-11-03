<?php
require_once '../db/db.php';

$user_id = require_user($conn);
$pet_id = valid_id($_GET['pet_id'] ?? null);
$error = '';

if (!$pet_id) {
    header('Location: my_pet.php');
    exit;
}

// Verify that this pet belongs to the logged-in user.
$stmt = $conn->prepare(
    "SELECT id, pet_name FROM pets
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

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date;

    if (!$validDate || $date < date('Y-m-d')) {
        $error = 'Please select a valid future appointment date.';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        $error = 'Please select a valid appointment time.';
    } elseif ($reason === '' || mb_strlen($reason) > 255) {
        $error = 'Please enter a reason (maximum 255 characters).';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO appointments (pet_id, date, time, reason)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param("isss", $pet_id, $date, $time, $reason);
        $stmt->execute();

        header("Location: view.php?pet_id=" . $pet_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appointment | Pet-Care</title>
     <link rel="stylesheet" href="./users.css?v=3">
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
        <input type="hidden" name="csrf_token"
               value="<?= h(csrf_token()) ?>">

        <label for="date">Appointment Date</label>
        <input
            type="date"
            id="date"
            name="date"
            min="<?= h(date('Y-m-d')) ?>"
            required
        >

        <label for="time">Appointment Time</label>
        <input type="time" id="time" name="time" required>

        <label for="reason">Reason</label>
        <textarea
            id="reason"
            name="reason"
            maxlength="255"
            required
        ></textarea>
        <div class="action-buttons">
            <button type="submit">Add Appointment</button>
        <a class = "button" href="view.php?pet_id=<?= (int)$pet_id ?>">Back to Pet</a>
        </div>

        
    </form>
</div>

</body>
</html>