<?php
require_once '../db/db.php';

$user_id = require_user($conn);
$pet_id = valid_id($_GET['pet_id'] ?? null);
$error = '';

if (!$pet_id) {
    header('Location: my_pet.php');
    exit;
}

// Verify pet ownership.
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

    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    $date_given = trim($_POST['date_given'] ?? '');
    $next_schedule_input = trim($_POST['next_schedule'] ?? '');

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date_given);
    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date_given;

    $next_schedule = null;

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
        } elseif ($validDate && $next_schedule_input < $date_given) {
            $error = 'Next schedule cannot be earlier than the date given.';
        } else {
            $next_schedule = $next_schedule_input;
        }
    }

    if ($error === '' && $vaccine_name === '') {
        $error = 'Vaccine name is required.';
    } elseif ($error === '' && mb_strlen($vaccine_name) > 255) {
        $error = 'Vaccine name is too long.';
    } elseif ($error === '' && !$validDate) {
        $error = 'Please enter a valid date given.';
    }

    if ($error === '') {
        $stmt = $conn->prepare(
            "INSERT INTO vaccines
             (pet_id, vaccine_name, date_given, next_schedule)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "isss",
            $pet_id,
            $vaccine_name,
            $date_given,
            $next_schedule
        );

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
    <title>Add Vaccine | Pet-Care</title>
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
        <h2>Add Vaccine</h2>
        <p>Pet: <?= h($pet['pet_name']) ?></p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="error-message"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= h(csrf_token()) ?>">

        <label for="vaccine_name">Vaccine Name</label>
        <input
            type="text"
            id="vaccine_name"
            name="vaccine_name"
            maxlength="255"
            required
        >

        <label for="date_given">Date Given</label>
        <input
            type="date"
            id="date_given"
            name="date_given"
            required
        >

        <label for="next_schedule">Next Schedule (Optional)</label>
        <input
            type="date"
            id="next_schedule"
            name="next_schedule"
        >

        <small>
            Enter the next schedule based on the veterinarian's advice.
        </small>

         <div class="action-buttons">
           
        <button type="submit">Save Vaccine</button>
        <a class="button" href="view.php?pet_id=<?= (int)$pet_id ?>">Back to Pet</a>
        </div>

    </form>
</div>

</body>
</html>