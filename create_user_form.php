<?php
session_start();
include 'db.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $international_phone = isset($_POST['international_phone']) ? htmlspecialchars($_POST['international_phone']) : '';
    $business_phone = isset($_POST['business_phone']) ? htmlspecialchars($_POST['business_phone']) : '';
    $personal_phone = isset($_POST['personal_phone']) ? htmlspecialchars($_POST['personal_phone']) : '';
    $home_number = isset($_POST['home_number']) ? htmlspecialchars($_POST['home_number']) : '';

    if (empty($name)) {
        $_SESSION['error_message'] = "Name is required.";
        header('Location: create_user_form.php');
        exit();
    }

    // Define validation patterns for different phone types
    $validation_patterns = [
        'international_phone' => '/^[0-9]{11,15}$/',
        'business_phone' => '/^[0-9]{11}$/',
        'personal_phone' => '/^[0-9]{11}$/',
        'home_number' => '/^[0-9]{10}$/'
    ];

    $phone_numbers = [
        'international_phone' => $international_phone,
        'business_phone' => $business_phone,
        'personal_phone' => $personal_phone,
        'home_number' => $home_number
    ];

    // Validate phone numbers according to their types
    foreach ($phone_numbers as $type => $phone) {
        if (!empty($phone) && !preg_match($validation_patterns[$type], $phone)) {
            $_SESSION['error_message'] = "The $type number is invalid.";
            header('Location: create_user_form.php');
            exit();
        }
    }

    $conn = getDbConnection();

    // Prepare an array to hold non-empty phone numbers for the query
    $params = [];
    $types = '';
    $conditions = [];

    foreach ($phone_numbers as $column => $phone) {
        if (!empty($phone)) {
            $conditions[] = "$column = ?";
            $params[] = $phone;
            $types .= 's'; // 's' indicates string type for bind_param
        }
    }

    if (empty($conditions)) {
        $_SESSION['error_message'] = "At least one phone number must be provided.";
        $conn->close();
        header('Location: create_user_form.php');
        exit();
    }

    // Prepare the SQL query with dynamic conditions based on non-empty phone numbers
    $sql = "SELECT id FROM new_users WHERE " . implode(' OR ', $conditions);
    $stmt = $conn->prepare($sql);

    // Ensure that the correct number of parameters is bound to the query
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "A user with one of these phone numbers already exists.";
        $stmt->close();
        $conn->close();
        header('Location: create_user_form.php');
        exit();
    }

    // Prepare the SQL query for inserting a new user, ensuring no empty values are included
    $insert_values = [];
    $bind_params = [];
    $types = "s"; // Start with type 's' for name

    $insert_values[] = $name;
    $bind_params[] = $name;

    foreach ($phone_numbers as $phone) {
        if (!empty($phone)) {
            $insert_values[] = $phone;
            $types .= 's'; // Add type 's' for each phone number
            $bind_params[] = $phone;
        } else {
            $insert_values[] = NULL;
            $types .= 's'; // Add type 's' for each phone number, including NULLs
            $bind_params[] = NULL;
        }
    }

    // Ensure that the correct number of placeholders is used
    $placeholders = implode(', ', array_fill(0, count($insert_values), '?'));
    $sql = "INSERT INTO new_users (name, international_phone, business_phone, personal_phone, home_number) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);

    // Ensure that the correct number of parameters is bound to the query
    $stmt->bind_param($types, ...$bind_params);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User created successfully";
        $stmt->close();
        $conn->close();
        header('Location: search.php');
        exit();
    } else {
        $_SESSION['error_message'] = "An error occurred while creating the user";
        $stmt->close();
        $conn->close();
        header('Location: create_user_form.php');
        exit();
    }
}

$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Create User</title>
    <link rel="icon" href="photo/logo.png" type="image/png">
</head>
<body>
    <div class="container">
        <img src="photo/logo.png" alt="">
        <h1>Create User</h1>
        <form action="create_user_form.php" method="post">
            <input type="text" id="name" name="name" placeholder="Enter your name">
            <input type="text" id="international_phone" name="international_phone" placeholder="Enter international phone">
            <input type="text" id="business_phone" name="business_phone" placeholder="Enter business phone">
            <input type="text" id="personal_phone" name="personal_phone" placeholder="Enter personal phone">
            <input type="text" id="home_number" name="home_number" placeholder="Enter home phone">
            <button type="submit">Create User</button>
            <a href="search.php" class="button-link">Back To Search</a>
        </form>
        <?php
        if ($error_message) {
            echo "<p class='error'>{$error_message}</p>";
        }
        if (isset($_SESSION['success_message'])) {
            echo "<p class='success'>{$_SESSION['success_message']}</p>";
            unset($_SESSION['success_message']);
        }
        ?>
    </div>
</body>
</html>
