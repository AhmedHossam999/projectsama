<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $pass = htmlspecialchars($_POST['pass']);

    if (empty($name) || empty($pass)) {
        $_SESSION['error'] = "Please enter your name and password.";
        header('Location: index.php');
        exit();
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE name = ? AND pass = ?");
    $stmt->bind_param("ss", $name, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['name'] = $name;

        // Check if the search count needs to be reset
        $current_date = date('Y-m-d');
        if ($user['last_search_date'] != $current_date) {
            $stmt = $conn->prepare("UPDATE users SET search_count = 0, last_search_date = ? WHERE name = ?");
            $stmt->bind_param("ss", $current_date, $name);
            $stmt->execute();
        }

        header('Location: search.php');
    } else {
        $_SESSION['error'] = "Invalid name or password.";
        header('Location: index.php');
    }

    $stmt->close();
    $conn->close();
} else {
    $_SESSION['error'] = "Please enter your name and password.";
    header('Location: index.php');
}


