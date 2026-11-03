
<?php
require_once '../db/db.php';

/* HTML ESCAPE FUNCTION */
if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Registration successful! Please wait for admin approval before logging in.';
}

/* LOGIN PROCESS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, username, password, attempts, status, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'Unable to process your request. Please try again.';
        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            if (!$user) {

                $error = 'Invalid username or password.';

            } elseif ($user['status'] === 'pending') {

                $error = 'Your account is still waiting for admin approval.';

            } elseif ($user['status'] !== 'active') {

                $error = 'Your account is inactive. Please contact the administrator.';

            } else {

                $storedPassword = $user['password'];

                $passwordValid = password_verify($password, $storedPassword);

                /* SUPPORT LEGACY PLAIN TEXT PASSWORD */
                if (
                    !$passwordValid &&
                    hash_equals((string)$storedPassword, (string)$password)
                ) {

                    $passwordValid = true;

                    $newHash = password_hash($password, PASSWORD_DEFAULT);

                    $update = $conn->prepare(
                        "UPDATE users SET password = ? WHERE id = ?"
                    );

                    if ($update) {
                        $update->bind_param("si", $newHash, $user['id']);
                        $update->execute();
                        $update->close();
                    }
                }

                if ($passwordValid) {

                    /* RESET LOGIN ATTEMPTS */
                    $reset = $conn->prepare(
                        "UPDATE users SET attempts = 0 WHERE id = ?"
                    );

                    if ($reset) {
                        $reset->bind_param("i", $user['id']);
                        $reset->execute();
                        $reset->close();
                    }

                    session_regenerate_id(true);

                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] === 'admin') {

                        $_SESSION['admin_id'] = $user['id'];

                        header("Location: ../Admin/admin_dashboard.php");
                        exit;

                    } else {

                        $_SESSION['user_id'] = $user['id'];

                        header("Location: ../User/user_dashboard.php");
                        exit;
                    }

                } else {

                    /* FAILED LOGIN ATTEMPTS */
                    $attempts = (int)$user['attempts'] + 1;

                    $newStatus = ($attempts >= 3)
                        ? 'inactive'
                        : $user['status'];

                    $update = $conn->prepare(
                        "UPDATE users
                         SET attempts = ?, status = ?
                         WHERE id = ?"
                    );

                    if ($update) {
                        $update->bind_param(
                            "isi",
                            $attempts,
                            $newStatus,
                            $user['id']
                        );

                        $update->execute();
                        $update->close();
                    }

                    if ($attempts >= 3) {

                        $error = 'Your account has been deactivated after 3 failed login attempts. Please contact the administrator.';

                    } else {

                        $remaining = 3 - $attempts;

                        $error = 'Invalid username or password. Attempts remaining: ' . $remaining;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Pet-Care</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
            color: #263445;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 1050px;
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(23, 59, 77, 0.12);
        }

        /* LEFT BRAND PANEL */
        .brand-panel {
            background: #173b4d;
            color: white;
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 50%;
        }

        .brand-panel::before {
            width: 350px;
            height: 350px;
            right: -150px;
            top: -100px;
        }

        .brand-panel::after {
            width: 280px;
            height: 280px;
            left: -150px;
            bottom: -100px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 13px;
            position: relative;
            z-index: 1;
        }

        .brand-logo-icon {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            background: rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .brand-logo h1 {
            font-size: 25px;
            letter-spacing: .5px;
        }

        .brand-logo span {
            display: block;
            font-size: 12px;
            color: #c5d7df;
            margin-top: 2px;
        }

        .brand-content {
            position: relative;
            z-index: 1;
            margin-top: 35px;
        }

        .brand-content .eyebrow {
            color: #a8d5c2;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 18px;
        }

        .brand-content h2 {
            font-size: 39px;
            line-height: 1.2;
            max-width: 390px;
            margin-bottom: 20px;
        }

        .brand-content p {
            color: #d0dce2;
            line-height: 1.8;
            font-size: 15px;
            max-width: 390px;
        }

        .pet-illustration {
            text-align: center;
            font-size: 115px;
            margin: 30px 0;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 10px 10px rgba(0,0,0,.15));
        }

        .brand-bottom {
            position: relative;
            z-index: 1;
            color: #b7cbd4;
            font-size: 12px;
        }

        /* RIGHT FORM PANEL */
        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 55px 60px;
        }

        .form-content {
            width: 100%;
            max-width: 370px;
        }

        .form-heading {
            margin-bottom: 32px;
        }

        .form-heading .welcome {
            color: #3d8b70;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
        }

        .form-heading h2 {
            font-size: 31px;
            color: #173b4d;
            margin-bottom: 9px;
        }

        .form-heading p {
            color: #84909b;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            color: #34495e;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .form-group input {
            width: 100%;
            height: 50px;
            padding: 0 15px;
            border: 1px solid #dce3e9;
            border-radius: 9px;
            background: #fbfcfd;
            outline: none;
            font-size: 14px;
            transition: .2s;
        }

        .form-group input:focus {
            border-color: #3d8b70;
            background: white;
            box-shadow: 0 0 0 3px rgba(61,139,112,.10);
        }

        .auth-button {
            width: 100%;
            height: 51px;
            background: #173b4d;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 7px;
            transition: .2s;
        }

        .auth-button:hover {
            background: #24566d;
            transform: translateY(-1px);
        }

        .message {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .error {
            color: #a52828;
            background: #fff0f0;
            border: 1px solid #f3cccc;
        }

        .success {
            color: #23633c;
            background: #edf8f0;
            border: 1px solid #ccebd4;
        }

        .form-footer {
            text-align: center;
            margin-top: 27px;
            font-size: 14px;
            color: #84909b;
        }

        .form-footer a {
            color: #24566d;
            text-decoration: none;
            font-weight: 700;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width: 800px) {
            .auth-wrapper {
                max-width: 480px;
                grid-template-columns: 1fr;
            }

            .brand-panel {
                padding: 30px;
                min-height: 230px;
            }

            .brand-content {
                margin-top: 35px;
            }

            .brand-content h2 {
                font-size: 28px;
            }

            .brand-content p,
            .pet-illustration {
                display: none;
            }

            .brand-bottom {
                margin-top: 25px;
            }

            .form-panel {
                padding: 40px 30px;
            }
        }

        @media (max-width: 420px) {
            body {
                padding: 12px;
            }

            .brand-panel {
                padding: 25px;
            }

            .form-panel {
                padding: 35px 22px;
            }
        }
    </style>
</head>

<body>

<div class="auth-wrapper">

    <section class="brand-panel">

        <div class="brand-logo">
            <div class="brand-logo-icon">🐾</div>

            <div>
                <h1>Pet-Care</h1>
                <span>Veterinary Clinic Portal</span>
            </div>
        </div>

        <div class="brand-content">

            <div class="eyebrow">WELCOME TO PET-CARE</div>

            <h2>Care for Your Pets, Every Step of the Way.</h2>

            <p>
                Keep your pet's information, medical records, and
                vaccination schedules organized in one convenient place.
            </p>

            <div class="pet-illustration">🐶 🐱</div>

        </div>

        <div class="brand-bottom">
            Your trusted companion in pet health management.
        </div>

    </section>

    <section class="form-panel">

        <div class="form-content">

            <div class="form-heading">
                <div class="welcome">PET OWNER PORTAL</div>

                <h2>Welcome Back!</h2>

                <p>Enter your account details to continue.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="message error">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="message success">
                    <?= h($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(csrf_token()) ?>"
                >

                <div class="form-group">

                    <label for="username">Username</label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        value="<?= h($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                </div>

                <button type="submit" class="auth-button">
                    Sign In
                </button>

            </form>

            <div class="form-footer">
                Don't have an account?
                <a href="register.php">Create an account</a>
            </div>

        </div>

    </section>

</div>

</body>
</html>
