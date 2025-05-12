<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/PayrollController.php';

header('Content-Type: application/json');

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Khởi tạo controller
    $payrollController = new PayrollController($db);
    
    // Tạo phiếu lương
    $result = $payrollController->createPayroll($data);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 