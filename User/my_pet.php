<?php
session_start();
include '../db/db.php';

/* CHECK LOGIN */
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$currentuser = $_SESSION['user_id'];

/* GET PETS */
$sql = "SELECT * FROM pets WHERE user_id = '$currentuser'";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Pets</title>
    <link rel="stylesheet" href="users.css">
</head>

<body>
<!-- ========================= -->
<!-- SIDEBAR -->
<!-- ========================= -->
<div class="sidebar">
    <h1>🐾 Pet Care</h1>

    <a href="user_dashboard.php">Dashboard</a>
    <a href="add_pet.php">Add Pet</a>
    <a href="my_pet.php">My Pets</a>
     <a href="../Account/logout.php">Logout</a>
</div>

<div class="main">
    <div class = "page-title">

    <h2>🐾 <?php echo $_SESSION['username']; ?>'s Pets</h2>
    </div>



    <table >
        <tr>
            <th>Pet Name</th>
            <th>Type</th>
            <th>Actions</th>
        </tr>

       <?php if(mysqli_num_rows($result) > 0 ){ ?>
            <?php while($row = mysqli_fetch_assoc($result)){

       ?>
       <tr>
        <td><?php echo $row['pet_name']; ?></td>
        <td> <?php echo $row['type'];  ?></td>
        <td>
           <button><a href="view.php?pet_id=<?php echo $row['id']; ?>">view</a></button>
           <button><a href="edit_pet.php?id=<?php echo $row['id']; ?>">edit</a></button>
            <button><a href="delete_pet.php?id=<?php echo $row['id']; ?>"onclick ="return confirm('delete pet?')">delete</a></button>
        </td>
       </tr>




       <?php }?>

       <?php } ?>

    </table>

    
   
   

    

</div>

</body>
</html>