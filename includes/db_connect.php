<?php
// Set Pakistan timezone
date_default_timezone_set('Asia/Karachi');

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "Irfan Qureshi";
$password = "Qureshi123";
$dbname = "aquaflow_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set MySQL session timezone to Pakistan time
$conn->query("SET time_zone = '+05:00'");
?>
