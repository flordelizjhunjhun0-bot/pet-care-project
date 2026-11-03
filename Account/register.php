
<?php
require_once '../db/db.php';

/* HTML ESCAPE FUNCTION */
if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

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

        if (!$check) {
            $message = "Unable to process registration. Please try again.";
        } else {

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

                if (!$insert) {

                    $message = "Unable to create your account. Please try again.";

                } else {

                    $insert->bind_param(
                        "sssss",
                        $name,
                        $username,
                        $password_hash,
                        $email,
                        $contact
                    );

                    if ($insert->execute()) {
                        $success = true;
                    } else {
                        $message = "Registration failed. Please try again.";
                    }

                    $insert->close();
                }
            }

            $check->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | Pet-Care</title>

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
            max-width: 1100px;
            min-height: 700px;
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
            padding: 35px 55px;
        }

        .form-content {
            width: 100%;
            max-width: 390px;
        }

        .form-heading {
            margin-bottom: 23px;
        }

        .form-heading .welcome {
            color: #3d8b70;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }

        .form-heading h2 {
            font-size: 29px;
            color: #173b4d;
            margin-bottom: 7px;
        }

        .form-heading p {
            color: #84909b;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 13px;
        }

        .form-group label {
            display: block;
            color: #34495e;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-group input {
            width: 100%;
            height: 43px;
            padding: 0 13px;
            border: 1px solid #dce3e9;
            border-radius: 8px;
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
            height: 49px;
            background: #173b4d;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 7px;
            transition: .2s;
        }

        .auth-button:hover {
            background: #24566d;
            transform: translateY(-1px);
        }

        /* MESSAGES */

        .message {
            padding: 11px 13px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 15px;
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
            margin-top: 20px;
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
                min-height: 220px;
            }

            .brand-content {
                margin-top: 30px;
            }

            .brand-content h2 {
                font-size: 27px;
            }

            .brand-content p,
            .pet-illustration {
                display: none;
            }

            .brand-bottom {
                margin-top: 22px;
            }

            .form-panel {
                padding: 35px 30px;
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
                padding: 30px 22px;
            }
        }
    </style>
</head>

<body>

<div class="auth-wrapper">

    <!-- LEFT SIDE -->

    <section class="brand-panel">

        <div class="brand-logo">

            <div class="brand-logo-icon">🐾</div>

            <div>
                <h1>Pet-Care</h1>
                <span>Veterinary Clinic Portal</span>
            </div>

        </div>

        <div class="brand-content">

            <div class="eyebrow">
                JOIN OUR PET-CARE COMMUNITY
            </div>

            <h2>Every Pet Deserves the Best Care.</h2>

            <p>
                Create your account to organize your pet's
                information, medical history, appointments,
                and vaccination records.
            </p>

            <div class="pet-illustration">🐶 🐱</div>

        </div>

        <div class="brand-bottom">
            Your trusted companion in pet health management.
        </div>

    </section>

    <!-- RIGHT SIDE -->

    <section class="form-panel">

        <div class="form-content">

            <div class="form-heading">

                <div class="welcome">
                    GET STARTED
                </div>

                <h2>Create Account</h2>

                <p>
                    Fill in your details to register as a pet owner.
                </p>

            </div>

            <?php if ($message !== ''): ?>

                <div class="message error">
                    <?= h($message) ?>
                </div>

            <?php endif; ?>

            <?php if ($success): ?>

                <div class="message success">
                    Registration successful! Please wait for admin approval.

                    <br><br>

                    <a href="login.php"
                       style="color:#23633c;font-weight:700;">
                        Proceed to Login →
                    </a>
                </div>

            <?php else: ?>

                <form method="POST" action="">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h(csrf_token()) ?>"
                    >

                    <div class="form-group">

                        <label for="name">Full Name</label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="100"
                            placeholder="Enter your full name"
                            value="<?= h($_POST['name'] ?? '') ?>"
                            autocomplete="name"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="username">Username</label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            maxlength="100"
                            placeholder="Create a username"
                            value="<?= h($_POST['username'] ?? '') ?>"
                            autocomplete="username"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="email">Email Address</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="100"
                            placeholder="Enter your email address"
                            value="<?= h($_POST['email'] ?? '') ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="contact">Contact Number</label>

                        <input
                            type="tel"
                            id="contact"
                            name="contact"
                            maxlength="30"
                            placeholder="09XXXXXXXXX"
                            value="<?= h($_POST['contact'] ?? '') ?>"
                            autocomplete="tel"
                        >

                    </div>

                    <div class="form-group">

                        <label for="password">Password</label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            placeholder="Minimum 8 characters"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                    <button type="submit" class="auth-button">
                        Create Account
                    </button>

                </form>

            <?php endif; ?>

            <div class="form-footer">

                Already have an account?

                <a href="login.php">Sign In</a>

            </div>

        </div>

    </section>

</div>

</body>
</html>
