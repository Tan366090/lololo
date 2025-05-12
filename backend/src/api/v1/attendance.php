<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the root directory path
$rootDir = dirname(dirname(__DIR__));

// Check if required files exist
$required_files = [
    $rootDir . '/config/database.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("Required file not found: $file");
    }
}

require_once $rootDir . '/config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check database connection
try {
    $db = Database::getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Lấy action từ request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        getAllAttendance();
        break;
    case 'getByEmployee':
        getAttendanceByEmployee();
        break;
    case 'add':
        addAttendance();
        break;
    case 'update':
        updateAttendance();
        break;
    case 'delete':
        deleteAttendance();
        break;
    case 'getStatistics':
        getAttendanceStatistics();
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

// Hàm lấy danh sách chấm công
function getAllAttendance() {
    global $db;
    
    try {
        $sql = "SELECT a.attendance_id, a.employee_id, u.username, up.full_name,
                       a.attendance_date, a.check_in_time, a.check_out_time,
                       a.work_duration_hours, a.attendance_symbol, a.notes,
                       a.recorded_at, a.source
                FROM attendance a
                JOIN users u ON a.employee_id = u.user_id
                JOIN user_profiles up ON u.user_id = up.user_id
                ORDER BY a.attendance_date DESC, a.recorded_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $attendance
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lấy danh sách chấm công: ' . $e->getMessage()
        ]);
    }
}

// Hàm lấy lịch sử chấm công của nhân viên
function getAttendanceByEmployee() {
    global $db;
    
    $employeeId = $_GET['employeeId'] ?? null;
    
    if (!$employeeId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu ID nhân viên'
        ]);
        return;
    }
    
    try {
        $sql = "SELECT a.attendance_id, a.attendance_date, a.check_in_time, 
                       a.check_out_time, a.work_duration_hours, 
                       a.attendance_symbol, a.notes, a.recorded_at, a.source
                FROM attendance a
                WHERE a.employee_id = :employee_id
                ORDER BY a.attendance_date DESC, a.recorded_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $attendance
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lấy lịch sử chấm công: ' . $e->getMessage()
        ]);
    }
}

// Hàm thêm bản ghi chấm công
function addAttendance() {
    global $db;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required_fields = ['employee_id', 'attendance_date', 'attendance_symbol'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Thiếu trường bắt buộc: $field");
            }
        }
        
        // Check if attendance record already exists
        $sql = "SELECT attendance_id FROM attendance 
                WHERE employee_id = :employee_id AND attendance_date = :attendance_date";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindParam(':attendance_date', $data['attendance_date']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Đã tồn tại bản ghi chấm công cho nhân viên này vào ngày này');
        }
        
        // Insert new attendance record
        $sql = "INSERT INTO attendance (employee_id, attendance_date, check_in_time, 
                check_out_time, work_duration_hours, attendance_symbol, notes, source)
                VALUES (:employee_id, :attendance_date, :check_in_time, :check_out_time,
                        :work_duration_hours, :attendance_symbol, :notes, 'manual')";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindParam(':attendance_date', $data['attendance_date']);
        $stmt->bindParam(':check_in_time', $data['check_in_time'] ?? null);
        $stmt->bindParam(':check_out_time', $data['check_out_time'] ?? null);
        $stmt->bindParam(':work_duration_hours', $data['work_duration_hours'] ?? null);
        $stmt->bindParam(':attendance_symbol', $data['attendance_symbol']);
        $stmt->bindParam(':notes', $data['notes'] ?? null);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm bản ghi chấm công thành công',
                'attendance_id' => $db->lastInsertId()
            ]);
        } else {
            throw new Exception('Lỗi khi thêm bản ghi chấm công');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi thêm bản ghi chấm công: ' . $e->getMessage()
        ]);
    }
}

// Hàm cập nhật bản ghi chấm công
function updateAttendance() {
    global $db;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['attendance_id'])) {
            throw new Exception('Thiếu ID bản ghi chấm công');
        }
        
        // Build update query dynamically based on provided fields
        $update_fields = [];
        $params = [];
        
        $allowed_fields = [
            'check_in_time', 'check_out_time', 'work_duration_hours',
            'attendance_symbol', 'notes'
        ];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($update_fields)) {
            throw new Exception('Không có trường nào được cập nhật');
        }
        
        $params[':attendance_id'] = $data['attendance_id'];
        
        $sql = "UPDATE attendance SET " . implode(', ', $update_fields) . 
               " WHERE attendance_id = :attendance_id";
        
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute($params)) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật bản ghi chấm công thành công'
            ]);
        } else {
            throw new Exception('Lỗi khi cập nhật bản ghi chấm công');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi cập nhật bản ghi chấm công: ' . $e->getMessage()
        ]);
    }
}

// Hàm xóa bản ghi chấm công
function deleteAttendance() {
    global $db;
    
    try {
        $attendance_id = $_GET['id'] ?? null;
        
        if (!$attendance_id) {
            throw new Exception('Thiếu ID bản ghi chấm công');
        }
        
        $sql = "DELETE FROM attendance WHERE attendance_id = :attendance_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':attendance_id', $attendance_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Xóa bản ghi chấm công thành công'
            ]);
        } else {
            throw new Exception('Lỗi khi xóa bản ghi chấm công');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi xóa bản ghi chấm công: ' . $e->getMessage()
        ]);
    }
}

// Hàm lấy thống kê chấm công
function getAttendanceStatistics() {
    global $db;
    
    try {
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        
        $sql = "SELECT 
                    a.employee_id,
                    u.username,
                    up.full_name,
                    COUNT(CASE WHEN a.attendance_symbol = 'P' THEN 1 END) as present_days,
                    COUNT(CASE WHEN a.attendance_symbol = 'A' THEN 1 END) as absent_days,
                    COUNT(CASE WHEN a.attendance_symbol = 'L' THEN 1 END) as leave_days,
                    COUNT(CASE WHEN a.attendance_symbol = 'WFH' THEN 1 END) as wfh_days,
                    AVG(a.work_duration_hours) as avg_work_hours
                FROM attendance a
                JOIN users u ON a.employee_id = u.user_id
                JOIN user_profiles up ON u.user_id = up.user_id
                WHERE a.attendance_date BETWEEN :start_date AND :end_date
                GROUP BY a.employee_id, u.username, up.full_name";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $statistics
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lấy thống kê chấm công: ' . $e->getMessage()
        ]);
    }
}