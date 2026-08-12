<?php
/**
 * config.php - Database Configuration File
 * This file establishes a connection to the MySQL database using PDO.
 * It's included in every other file that needs database access.
 */

// Database credentials - Update these if your XAMPP uses different settings
$host = 'localhost';      // Server name (usually localhost for XAMPP)
$dbname = 'hospital_appointment';  // Database name we created in SQL file
$username = 'root';       // Default XAMPP MySQL username
$password = '';           // Default XAMPP MySQL password is empty

try {
    // Create a PDO (PHP Data Objects) connection - secure and modern way to connect database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set PDO error mode to exception - errors will be thrown as exceptions
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection is successful - uncomment line below to test
    // echo "Connected successfully"; 
} catch(PDOException $e) {
    // If connection fails, display the error message
    die("Connection failed: " . $e->getMessage());
}

// Start session to store login information (used across all pages)
session_start();
?>
