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

    $date = trim($_POST['date'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $date;

    if (!$validDate) {
        $error = 'Please enter a valid medical record date.';
    } elseif ($diagnosis === '' || $treatment === '') {
        $error = 'Diagnosis and treatment are required.';
    } elseif (
        mb_strlen($diagnosis) > 255 ||
        mb_strlen($treatment) > 255
    ) {
        $error = 'Diagnosis or treatment is too long.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO medical_history
             (pet_id, date, diagnosis, treatment, notes)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issss",
            $pet_id,
            $date,
            $diagnosis,
            $treatment,
            $notes
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
    <title>Add Medical History | Pet-Care</title>
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
        <h2>Add Medical History</h2>
        <p>Pet: <?= h($pet['pet_name']) ?></p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="error-message"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= h(csrf_token()) ?>">

        <label for="date">Date</label>
        <input type="date" id="date" name="date"
               value="<?= h(date('Y-m-d')) ?>" required>

        <label for="diagnosis">Diagnosis</label>
        <input type="text" id="diagnosis" name="diagnosis"
               maxlength="255" required>

        <label for="treatment">Treatment</label>
        <input type="text" id="treatment" name="treatment"
               maxlength="255" required>

        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"></textarea>

         <div class="action-buttons">
                    <button type="submit">Save Medical Record</button>
        <a class="button" href="view.php?pet_id=<?= (int)$pet_id ?>">Back to Pet</a>
        </div>

      
    </form>
</div>

</body>
</html>