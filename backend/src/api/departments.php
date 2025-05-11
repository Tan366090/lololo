<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for HTTPS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log errors
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Query to get all departments with manager information
    $query = "SELECT 
        d.id, 
        d.name, 
        d.description,
        d.manager_id,
        d.status,
        d.created_at,
        d.updated_at,
        e.name as manager_name,
        e.email as manager_email,
        e.phone as manager_phone,
        p.name as manager_position
    FROM departments d
    LEFT JOIN employees e ON d.manager_id = e.id
    LEFT JOIN positions p ON e.position_id = p.id
    ORDER BY d.name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $departments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $departments[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'manager_id' => $row['manager_id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'manager_name' => $row['manager_name'],
            'manager_email' => $row['manager_email'],
            'manager_phone' => $row['manager_phone'],
            'manager_position' => $row['manager_position']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $departments
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in departments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Server error in departments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} 