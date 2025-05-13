<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if database.php exists
$dbFile = '../../config/database.php';
if (!file_exists($dbFile)) {
    die("Error: database.php not found at path: " . $dbFile);
}

// Include database connection
require_once $dbFile;

try {
    // Get database connection
    $conn = Database::getConnection();
    
    // Test query
    $query = "SELECT COUNT(*) as total FROM employees";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database connection successful!<br>";
    echo "Total employees: " . $result['total'];
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine();
}
?>