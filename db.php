<?php
// Database configuration
$servername = "localhost";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password (empty)
$dbname = "Women_blog"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $error =("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for better character support
$conn->set_charset("utf8mb4");
?>
