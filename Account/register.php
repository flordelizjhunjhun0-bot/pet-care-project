<?php
session_start();
include '../db/db.php';

$message = "";
$message1 = "";

if(isset($_POST['register'])){

    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // check username exists
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0){

        $message = "Username is taken";

    } else {

        // hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // insert user
        $sql = "INSERT INTO users (name, username, password, email, status, role)
                VALUES ('$name', '$username', '$password_hash', '$email', 'pending', 'user')";

        if(mysqli_query($conn, $sql)){
            $message1 = "Successfully Registered. Wait for admin approval.";
        } else {
            $message = "Registration failed";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<form method="POST">

    <h2>Register</h2>

    <label>Name</label>
    <input type="text" name="name" required><br><br>

    <label>Username</label>
    <input type="text" name="username" required><br><br>

    <label>Email</label>
    <input type="email" name="email" required><br><br>

    <label>Password</label>
    <input type="password" name="password" required><br><br>

    <!-- ERROR MESSAGE -->
    <?php if(!empty($message)){ ?>
        <p style="color:red; text-align:center;">
            <?php echo $message; ?>
        </p>
    <?php } ?>

    <!-- SUCCESS MESSAGE -->
    <?php if(!empty($message1)){ ?>
        <p style="color:green; text-align:center;">
            <?php echo $message1; ?>
        </p>
    <?php } ?>

    <button type="submit" name="register">Register</button>

    <p style="text-align:center;">
        Already have an account? <a href="login.php">Login here</a>
    </p>

</form>

</div>

</body>
</html>