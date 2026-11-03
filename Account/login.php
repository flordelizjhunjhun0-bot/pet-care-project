<?php

session_start();
include '../db/db.php';



$message = "";

if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    

    if(mysqli_num_rows($result) > 0){

        $user = mysqli_fetch_assoc($result);

      

        // 🔒 CHECK PASSWORD FIRST
        if(
    password_verify($password, $user['password']) 
    || $password == $user['password']
){
    

            // 🔴 DEACTIVATED
            if($user['status'] == 'deactivate'){
                $message = "Please contact admin to activate your account";
            }

            // 🟡 PENDING
            else if($user['status'] != 'active'){
                $message = "Wait for admin approval";
            }
   // 🟢 ADMIN LOGIN
             else if($user['role'] == 'admin'){
                mysqli_query($conn, "UPDATE users SET attempts = 0 WHERE username = '$username'");
                $_SESSION['admin'] = $user['username'];

                header("Location:../Admin/admin_dashboard.php");
                exit();
            }
           

            // 🟢 USER LOGIN
            else {
                mysqli_query($conn, "UPDATE users SET attempts = 0 WHERE username = '$username'");

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['status'] = $user['status'];
                $_SESSION['username'] = $user['username'];

                header("Location:../User/user_dashboard.php");
                exit();
            }

        } else {

            // ❌ WRONG PASSWORD
            $attempts = $user['attempts'] + 1;

            mysqli_query($conn, "UPDATE users SET attempts = $attempts WHERE username = '$username'");

            if($attempts >= 3){
                mysqli_query($conn, "UPDATE users SET status = 'deactivate' WHERE username = '$username'");
                $message = "Account Locked!";
            } else {
                $left = 3 - $attempts;
                $message = "Wrong Password. $left attempt(s left)";
            }
        }

    } else {
        $message = "Wrong username";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<form method="POST">

    <h2>Login</h2>

    <label>Username</label>
    <input type="text" name="username" required><br><br>

    <label>Password</label>
    <input type="password" name="password" required><br><br>

    <!-- 🔴 MESSAGE DISPLAY -->
    <?php if(!empty($message)){ ?>
        <p style="color:red; text-align:center;">
            <?php echo $message; ?>
        </p>
    <?php } ?>

    <button type="submit" name="login">Login</button>

</form>

<p style="text-align:center;">
    Dont Have An Account? <a href="register.php">Register here</a>
</p>

</div>

</body>
</html>