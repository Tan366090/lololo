<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Lock.php';

// Khởi tạo kết nối database
$database = new Database();
$db = $database->getConnection();

// Khởi tạo model Lock
$lock = new Lock($db);

// Xử lý request
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Lấy dữ liệu từ request
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->resource_id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Thiếu resource_id']);
            exit;
        }
        
        // Thử khóa tài nguyên
        if ($lock->acquire($data->resource_id)) {
            echo json_encode(['message' => 'Đã khóa tài nguyên thành công']);
        } else {
            http_response_code(409);
            echo json_encode(['message' => 'Tài nguyên đang bị khóa']);
        }
        break;
        
    case 'DELETE':
        // Lấy resource_id từ query string
        $resource_id = isset($_GET['resource_id']) ? $_GET['resource_id'] : null;
        
        if (!$resource_id) {
            http_response_code(400);
            echo json_encode(['message' => 'Thiếu resource_id']);
            exit;
        }
        
        // Mở khóa tài nguyên
        if ($lock->release($resource_id)) {
            echo json_encode(['message' => 'Đã mở khóa tài nguyên']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Không tìm thấy khóa']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method không được hỗ trợ']);
        break;
} 