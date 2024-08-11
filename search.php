<?php
session_start();
include 'db.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['name'])) {
    header('Location: index.php');
    exit();
}

$conn = getDbConnection();
$name = $_SESSION['name'];
$search_result = null;
$error_message = null;
$success_message = null;

// Get user's search count and last search date
$stmt = $conn->prepare("SELECT id, search_count, last_search_date FROM users WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found or database error.");
}

$user_id = $user['id'];
$search_count = $user['search_count'] ?? 0;
$last_search_date = $user['last_search_date'] ?? '';
$current_date = date('Y-m-d');

if ($last_search_date != $current_date) {
    $search_count = 0;
    $stmt = $conn->prepare("UPDATE users SET search_count = 0, last_search_date = ? WHERE name = ?");
    $stmt->bind_param("ss", $current_date, $name);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $search_phone = htmlspecialchars($_POST['search_phone']);

    if (empty($search_phone)) {
        $_SESSION['error_message'] = "Please enter a mobile phone number.";
        header('Location: search.php');
        exit();
    } elseif (!preg_match('/^[0-9]{10,15}$/', $search_phone)) {
        $_SESSION['error_message'] = "Please enter a valid phone number (10 to 15 digits)";
        header('Location: search.php');
        exit();
    } elseif ($search_count < 3) {
        // Search only in the new_users table
        $stmt = $conn->prepare("SELECT id, name, international_phone, business_phone, personal_phone ,home_number
                                FROM new_users 
                                WHERE international_phone = ? 
                                OR business_phone = ? 
                                OR home_number = ?
                                OR personal_phone = ?");
        $stmt->bind_param("ssss", $search_phone, $search_phone, $search_phone , $search_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $search_result = $result->fetch_assoc();
        $stmt->close();

        if ($search_result) {
            // Insert into search_results directly
            $newuser_id = $search_result['id'];
            $stmt = $conn->prepare("INSERT INTO search_results (user_id,newuser_id, phone) VALUES (?,?, ?)");
            if ($stmt === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("iss",$user_id, $newuser_id, $search_phone);
            if (!$stmt->execute()) {
                die('Execute failed: ' . htmlspecialchars($stmt->error));
            }
            $_SESSION['success_message'] = "User found";
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "User not found";
           
        }

        $search_count++;
        // Update search count
        $stmt = $conn->prepare("UPDATE users SET search_count = ? WHERE name = ?");
        $stmt->bind_param("is", $search_count, $name);
        $stmt->execute();
        $stmt->close();

        // Redirect to search page to display messages
        header('Location: search.php');
        exit();
    } else {
        $_SESSION['error_message'] = "You have reached the maximum number of searches for today.";
        header('Location: search.php');
        exit();
    }
}

// Retrieve messages from session if available
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

$search_phone = isset($_POST['search_phone']) ? htmlspecialchars($_POST['search_phone']) : '';

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Search Users</title>
    <link rel="icon" href="photo/logo.png" type="image/png">
</head>
<body>
    <div class="container">
        <img src="photo/logo.png" alt="">
        <div class="inf">
            <p>Welcome <strong><?php echo htmlspecialchars($name); ?></strong></p>
        </div>
        <form action="search.php" method="post">
            <input type="text" id="search_phone" name="search_phone" placeholder="Enter phone number" value="<?php echo htmlspecialchars($search_phone); ?>">
            <button type="submit">Search</button>
            <a href="logout.php" class="button-link">Logout</a>
            <!-- <a href='create_user_form.php' class='button-link'>Add User</a> -->
            <!-- <a href="upload.php" class="button-link">Upload File</a> -->
        </form>
        <?php
        if ($search_result) {
            echo "<p>User found</p>";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$search_result && !$error_message) {
            echo "<p>User not found</p>";
        }
        if ($error_message) {
            echo "<p class='error'>{$error_message}</p>";
            echo "<a href='create_user_form.php' class='button-link'>Add User</a>";
        }
        if ($success_message) {
            echo "<p class='success'>{$success_message}</p>";
        }
        ?>
    </div>
</body>
</html>
