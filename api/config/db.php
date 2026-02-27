<?php
// FILE: api/config/db.php

/**
 * HELIOHOST DATABASE CONFIGURATION
 * 
 * 1. Log in to cPanel on HelioHost.
 * 2. Go to "MySQL Databases".
 * 3. Create a new database (e.g., username_hris_db).
 * 4. Create a new user (e.g., username_admin) and set a password.
 * 5. Add the user to the database with ALL PRIVILEGES.
 * 6. Set the values below or use environment variables.
 */

require_once __DIR__ . '/../middleware/cors.php';

// Support for environment variables (Aiven/Vercel) or manual configuration
$host = getenv('DB_HOST') ?: "localhost"; // Usually localhost on HelioHost
$db_name = getenv('DB_NAME') ?: "hris_db"; // e.g., username_hris_db
$username = getenv('DB_USER') ?: "root"; // e.g., username_admin
$password = getenv('DB_PASS') ?: ""; // e.g., your_db_password

// Aiven often requires SSL; check if a CA cert is provided
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

if (getenv('DB_SSL_CA')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = getenv('DB_SSL_CA');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, $options);
} catch(PDOException $e) {
    // Log error for debugging but don't expose sensitive info to the client
    error_log("DB Connection Failed: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
?>