
<?php
// StudentHub Practical 8
// MySQL Database Connection

$host = "localhost";
$username = "root";
$password = "";
$database = "studenthub_db";

// Create database connection
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Check your MySQL settings.");
}

// Set character encoding
$conn->set_charset("utf8mb4");
?>
