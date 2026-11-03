<?php
require_once '../db/db.php';

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        $username === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $password === ''
    ) {

        $message = "Please complete all required fields correctly.";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";

    } elseif (
        strlen($name) > 100 ||
        strlen($username) > 100 ||
        strlen($email) > 100 ||
        strlen($contact) > 30
    ) {

        $message = "One or more fields exceed the allowed length.";

    } else {

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        $check->bind_param("ss", $username, $email);
        $check->execute();

        if ($check->get_result()->fetch_assoc()) {

            $message = "Username or email is already registered.";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insert = $conn->prepare(
                "INSERT INTO users
                (name, username, password, email, contact, status, role)
                VALUES (?, ?, ?, ?, ?, 'pending', 'user')"
            );

            $insert->bind_param(
                "sssss",
                $name,
                $username,
                $password_hash,
                $email,
                $contact
            );

            $insert->execute();

            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Register | Pet-Care</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>🐾 Create Pet-Care Account</h2>

    <p>Register as a pet owner.</p>

    <form method="POST">

        <label>Full Name</label>

        <input
            type="text"
            name="name"
            maxlength="100"
            required
        >

        <label>Username</label>

        <input
            type="text"
            name="username"
            maxlength="100"
            required
        >

        <label>Email</label>

        <input
            type="email"
            name="email"
            maxlength="100"
            required
        >

        <label>Contact Number</label>

        <input
            type="tel"
            name="contact"
            maxlength="30"
            placeholder="09XXXXXXXXX"
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <?php if ($message !== ''): ?>

            <p style="color:red; text-align:center;">
                <?= h($message) ?>
            </p>

        <?php endif; ?>

        <?php if ($success): ?>

            <p style="color:green; text-align:center;">
                Registration successful!
                Please wait for admin approval.
            </p>

        <?php endif; ?>

        <button type="submit">
            Register
        </button>

    </form>

    <p>
        Already have an account?
        <a href="login.php">Login here</a>
    </p>

</div>

</body>
</html>