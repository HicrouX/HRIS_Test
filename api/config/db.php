// FILE: api/config/db.php
require_once __DIR__ . '/../middleware/cors.php';

// Support for environment variables (Aiven/Vercel) with XAMPP defaults
$host = getenv('DB_HOST') ?: "localhost";
$db_name = getenv('DB_NAME') ?: "hris_db";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";

// Aiven often requires SSL; check if a CA cert is provided
$options = [];
if (getenv('DB_SSL_CA')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = getenv('DB_SSL_CA');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Log error for debugging but don't expose sensitive info to the client
    error_log("DB Connection Failed: " . $e->getMessage());
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
?>