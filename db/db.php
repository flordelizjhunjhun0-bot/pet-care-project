<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "project";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection failed. Please check XAMPP MySQL.");
}

date_default_timezone_set('Asia/Manila');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Escape output to prevent HTML injection */
function h($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* Validate numeric IDs */
function valid_id($value) {
    $id = filter_var($value, FILTER_VALIDATE_INT);

    return ($id !== false && $id > 0) ? (int)$id : 0;
}

/* Protect user pages */
function require_user($conn) {

    $user_id = valid_id($_SESSION['user_id'] ?? 0);

    if (!$user_id) {
        header("Location: ../Account/login.php");
        exit();
    }

    $stmt = $conn->prepare(
        "SELECT id, role, status
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if (
        !$user ||
        $user['role'] !== 'user' ||
        $user['status'] !== 'active'
    ) {
        $_SESSION = [];
        session_destroy();

        header("Location: ../Account/login.php");
        exit();
    }

    return $user_id;
}

/* Protect admin pages */
function require_admin($conn) {

    $admin_id = valid_id($_SESSION['admin_id'] ?? 0);

    /* Support an older admin session */
    if (!$admin_id && !empty($_SESSION['admin'])) {

        $username = $_SESSION['admin'];

        $stmt = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = ?
             AND role = 'admin'
             AND status = 'active'
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $admin = $stmt->get_result()->fetch_assoc();

        $admin_id = valid_id($admin['id'] ?? 0);

        if ($admin_id) {
            $_SESSION['admin_id'] = $admin_id;
        }
    }

    if (!$admin_id) {
        header("Location: ../Account/login.php");
        exit();
    }

    $stmt = $conn->prepare(
        "SELECT id
         FROM users
         WHERE id = ?
         AND role = 'admin'
         AND status = 'active'
         LIMIT 1"
    );

    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {

        $_SESSION = [];
        session_destroy();

        header("Location: ../Account/login.php");
        exit();
    }

    return $admin_id;
}

?>