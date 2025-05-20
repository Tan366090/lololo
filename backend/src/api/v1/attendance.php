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

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    case 'getEmployees':
        getEmployeeList();
        break;
    case 'getEmployeeCount':
        getEmployeeCount();
        break;
    case 'getUnmarkedEmployees':
        getUnmarkedEmployees();
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
                       a.recorded_at, a.source,
                       e.employee_code, e.name AS employee_name
                FROM attendance a
                JOIN users u ON a.employee_id = u.user_id
                JOIN user_profiles up ON u.user_id = up.user_id
                LEFT JOIN employees e ON a.employee_id = e.id
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
        
        if (!isset($data['employee_id']) || !isset($data['attendance_date']) || !isset($data['attendance_symbol'])) {
            throw new Exception('Thiếu thông tin bắt buộc');
        }
        
        // Insert new attendance record
        $sql = "INSERT INTO attendance (employee_id, attendance_date, check_in_time, 
                check_out_time, work_duration_hours, attendance_symbol, notes, source)
                VALUES (:employee_id, :attendance_date, :check_in_time, :check_out_time,
                        :work_duration_hours, :attendance_symbol, :notes, 'manual')";
        
        $stmt = $db->prepare($sql);
        
        // Create variables for binding
        $employee_id = $data['employee_id'];
        $attendance_date = $data['attendance_date'];
        $check_in_time = $data['check_in_time'] ?? null;
        $check_out_time = $data['check_out_time'] ?? null;
        $work_duration_hours = $data['work_duration_hours'] ?? null;
        $attendance_symbol = $data['attendance_symbol'];
        $notes = $data['notes'] ?? null;
        
        // Bind parameters using variables
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $stmt->bindParam(':attendance_date', $attendance_date);
        $stmt->bindParam(':check_in_time', $check_in_time);
        $stmt->bindParam(':check_out_time', $check_out_time);
        $stmt->bindParam(':work_duration_hours', $work_duration_hours);
        $stmt->bindParam(':attendance_symbol', $attendance_symbol);
        $stmt->bindParam(':notes', $notes);
        
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

// Hàm lấy danh sách nhân viên
function getEmployeeList() {
    global $db;
    
    try {
        $sql = "SELECT e.id as employee_id, e.employee_code, e.name as employee_name,
                       u.username, up.full_name
                FROM employees e
                JOIN users u ON e.id = u.user_id
                JOIN user_profiles up ON u.user_id = up.user_id
                ORDER BY e.employee_code ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $employees
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage()
        ]);
    }
}

// Hàm đếm tổng số nhân viên
function getEmployeeCount() {
    global $db;
    
    try {
        $sql = "SELECT COUNT(*) as total_employees FROM employees";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_employees' => (int)$result['total_employees']
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi đếm số lượng nhân viên: ' . $e->getMessage()
        ]);
    }
}

// Hàm lấy danh sách nhân viên chưa chấm công trong ngày
function getUnmarkedEmployees() {
    global $db;
    
    try {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        // Lấy danh sách nhân viên chưa chấm công trong ngày
        $sql = "SELECT e.id as employee_id, e.employee_code, e.name as employee_name,
                       u.username, up.full_name,
                       CASE 
                           WHEN e.status = 'active' THEN 'Đang làm việc'
                           WHEN e.status = 'on_leave' THEN 'Đang nghỉ phép'
                           ELSE 'Không xác định'
                       END as employee_status
                FROM employees e
                JOIN users u ON e.id = u.user_id
                JOIN user_profiles up ON u.user_id = up.user_id
                WHERE e.status = 'active'  -- Chỉ lấy nhân viên đang làm việc
                AND NOT EXISTS (
                    SELECT 1 FROM attendance a 
                    WHERE a.employee_id = e.id 
                    AND a.attendance_date = :date
                )
                ORDER BY e.employee_code ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Thêm thông tin về ngày chấm công
        $response = [
            'success' => true,
            'data' => $employees,
            'attendance_date' => $date,
            'total_unmarked' => count($employees)
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lấy danh sách nhân viên chưa chấm công: ' . $e->getMessage()
        ]);
    }
}