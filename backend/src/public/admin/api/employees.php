<?php
// Include database connection
require_once '../../config/database.php';

// Get database connection
$conn = Database::getConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get ID from URL if present
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Get input data for POST/PUT requests
$input = [];
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
}

// Helper function to send JSON response
function sendResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to handle errors
function handleError($message, $status = 400) {
    sendResponse(['error' => $message], $status);
}

// Handle different HTTP methods
switch ($method) {
    case 'GET':
        if ($id) {
            // Get single employee
            $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
            
            if ($employee) {
                sendResponse($employee);
            } else {
                handleError('Employee not found', 404);
            }
        } else {
            // Get all employees
            $stmt = $conn->query("SELECT * FROM employees");
            $employees = $stmt->fetchAll();
            sendResponse($employees);
        }
        break;
        
    case 'POST':
        // Create new employee
        $required_fields = ['name', 'email', 'department_id', 'position_id'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field])) {
                handleError("Missing required field: $field");
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO employees (name, email, department_id, position_id) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$input['name'], $input['email'], $input['department_id'], $input['position_id']])) {
            sendResponse(['id' => $conn->lastInsertId()], 201);
        } else {
            handleError('Failed to create employee');
        }
        break;
        
    case 'PUT':
        // Update employee
        if (!$id) {
            handleError('Employee ID is required');
        }
        
        $stmt = $conn->prepare("UPDATE employees SET name = ?, email = ?, department_id = ?, position_id = ? WHERE id = ?");
        
        if ($stmt->execute([$input['name'], $input['email'], $input['department_id'], $input['position_id'], $id])) {
            sendResponse(['message' => 'Employee updated successfully']);
        } else {
            handleError('Failed to update employee');
        }
        break;
        
    case 'DELETE':
        // Delete employee
        if (!$id) {
            handleError('Employee ID is required');
        }
        
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            sendResponse(['message' => 'Employee deleted successfully']);
        } else {
            handleError('Failed to delete employee');
        }
        break;
        
    default:
        handleError('Method not allowed', 405);
} 