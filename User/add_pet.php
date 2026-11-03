<?php
require_once '../db/db.php';

$user_id = require_user($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $pet_name = trim($_POST['pet_name'] ?? '');
    $type = trim($_POST['type'] ?? '');

    if ($pet_name === '' || $type === '') {
        $error = 'Please complete all fields.';
    } elseif (mb_strlen($pet_name) > 100 || mb_strlen($type) > 50) {
        $error = 'Pet name or pet type is too long.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO pets (user_id, pet_name, type)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("iss", $user_id, $pet_name, $type);
        $stmt->execute();

        header('Location: my_pet.php?success=pet_added');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Pet | Pet-Care</title>
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
        <h2>🐾 Add New Pet</h2>
    </div>

    <?php if ($error !== ''): ?>
        <p class="error-message"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= h(csrf_token()) ?>">

        <div class="form-group">
            <label for="pet_name">Pet Name</label>
            <input
                type="text"
                id="pet_name"
                name="pet_name"
                maxlength="100"
                placeholder="Enter pet name"
                required
            >
        </div>

        <div class="form-group">
            <label for="type">Pet Type</label>
            <select id="type" name="type" required>
                <option value="">Select pet type</option>
                <option value="Dog">Dog</option>
                <option value="Cat">Cat</option>
                <option value="Bird">Bird</option>
                <option value="Rabbit">Rabbit</option>
                <option value="Other">Other</option>
            </select>
        </div>

         <div class="action-buttons">
            <button type="submit">➕ Add Pet</button>
        <a class="button" href="my_pet.php">Cancel</a>
        </div>

    </form>
</div>

</body>
</html>