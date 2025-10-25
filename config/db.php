<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'railway_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Enable exception mode for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Function to execute query and return result
function executeQuery($conn, $sql) {
    try {
        $result = $conn->query($sql);
        return ['success' => true, 'result' => $result];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to get last insert ID
function getLastInsertId($conn) {
    return $conn->insert_id;
}

// Function to escape string
function escapeString($conn, $string) {
    return $conn->real_escape_string($string);
}
?>
