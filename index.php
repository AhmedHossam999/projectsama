<?php
session_start();
include 'db.php';

$error_message = null;
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Login</title>
    <link rel="icon" href="photo/logo.png" type="image/png">
</head>
<body>
    <div class="container">
        <img src="photo/logo.png" alt="">
        <h1>Welcome</h1>    
        <h2>Login</h2>
        <form action="login.php" method="post">
            <input type="text" id="name" name="name" placeholder="Enter your name" >
            <input type="password" id="pass" name="pass" placeholder="Enter your password" >
            <button type="submit">Login</button>
        </form>
        <?php
        if ($error_message) {
            echo "<p class='error'>{$error_message}</p>";
        }
        ?>
    </div>
</body>
</html>


