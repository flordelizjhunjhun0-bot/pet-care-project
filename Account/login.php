<?php
require_once '../db/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $message = "Please enter your username and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT *
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {

            $message = "Invalid username or password.";

        } else {

            $password_ok = password_verify(
                $password,
                $user['password']
            );

            /*
             * Compatibility for the old plain-text admin password.
             * After a successful login, upgrade it to a password hash.
             */
            if (
                !$password_ok &&
                hash_equals((string)$user['password'], $password)
            ) {

                $password_ok = true;

                $new_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $update = $conn->prepare(
                    "UPDATE users
                     SET password = ?
                     WHERE id = ?"
                );

                $update->bind_param(
                    "si",
                    $new_hash,
                    $user['id']
                );

                $update->execute();
            }

            if (!$password_ok) {

                $attempts = (int)$user['attempts'] + 1;

                $status = ($attempts >= 3)
                    ? 'deactivate'
                    : $user['status'];

                $update = $conn->prepare(
                    "UPDATE users
                     SET attempts = ?, status = ?
                     WHERE id = ?"
                );

                $update->bind_param(
                    "isi",
                    $attempts,
                    $status,
                    $user['id']
                );

                $update->execute();

                if ($attempts >= 3) {
                    $message = "Account locked. Please contact the administrator.";
                } else {
                    $remaining = 3 - $attempts;
                    $message = "Invalid password. $remaining attempt(s) remaining.";
                }

            } elseif ($user['status'] === 'deactivate') {

                $message = "Your account is deactivated. Contact the administrator.";

            } elseif ($user['status'] !== 'active') {

                $message = "Your account is waiting for admin approval.";

            } else {

                /* Reset failed attempts */
                $reset = $conn->prepare(
                    "UPDATE users
                     SET attempts = 0
                     WHERE id = ?"
                );

                $reset->bind_param("i", $user['id']);
                $reset->execute();

                /* Prevent session fixation */
                session_regenerate_id(true);

                if ($user['role'] === 'admin') {

                    $_SESSION['admin'] = $user['username'];
                    $_SESSION['admin_id'] = (int)$user['id'];

                    unset(
                        $_SESSION['user_id'],
                        $_SESSION['username']
                    );

                    header("Location: ../Admin/admin_dashboard.php");
                    exit();

                } else {

                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];

                    unset(
                        $_SESSION['admin'],
                        $_SESSION['admin_id']
                    );

                    header("Location: ../User/user_dashboard.php");
                    exit();
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | Pet-Care</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>🐾 Pet-Care Login</h2>

    <p>Welcome back! Sign in to continue.</p>

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            autocomplete="username"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <?php if ($message !== ''): ?>

            <p style="color:red; text-align:center;">
                <?= h($message) ?>
            </p>

        <?php endif; ?>

        <button type="submit">
            Login
        </button>

    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</div>

</body>
</html>