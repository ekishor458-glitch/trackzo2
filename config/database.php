<?php
// Database Configuration - Production Version
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'trackzo_user');
define('DB_PASS', 'TrackZo@123456');
define('DB_NAME', 'trackzo_buildflow');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception("Database Connection Error: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    date_default_timezone_set('Asia/Kolkata');

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
