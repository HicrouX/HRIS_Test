<?php
// FILE: api/config/db.php
require_once __DIR__ . '/../middleware/cors.php';

$host = "localhost";
$db_name = "hris_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["error" => "Connection failed"]);
    exit;
}
?>