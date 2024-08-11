<?php
function getDbConnection() {
    $host = 'localhost'; // Your MySQL host
    $db = 'project'; // Your database name
    $user = 'root'; // Your database username
    $pass = ''; // Your database password

    $conn = mysqli_connect($host, $user, $pass, $db);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}
?>
