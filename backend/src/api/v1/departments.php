<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Helper function để trả về JSON response
function jsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    return;
}

// Kiểm tra quyền truy cập
$auth = new Auth();
$user = $auth->getUser();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]);
    exit;
}

// Lấy kết nối database
$conn = Database::getConnection();

// Lấy action từ request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        getAllDepartments();
        break;
    case 'getById':
        getDepartmentById();
        break;
    case 'create':
        createDepartment();
        break;
    case 'update':
        updateDepartment();
        break;
    case 'delete':
        deleteDepartment();
        break;
    case 'getEmployees':
        getDepartmentEmployees();
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action không hợp lệ'
        ]);
}

// Hàm lấy danh sách phòng ban
function getAllDepartments() {
    global $conn;
    
    try {
        // First, let's get basic department info
        $query = "SELECT 
            d.id,
            d.name as department_name,
            d.description,
            d.created_at,
            d.updated_at,
            d.parent_id,
            d.manager_id,
            -- Count employees
            (SELECT COUNT(*) FROM employees WHERE department_id = d.id) as employee_count,
            -- Get parent department name
            (SELECT name FROM departments WHERE id = d.parent_id) as parent_department_name
        FROM departments d
        ORDER BY d.name ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $formatted_departments = array_map(function($dept) use ($conn) {
            $manager = null;
            if ($dept['manager_id']) {
                $manager_query = "SELECT e.id, e.name, p.name as position_name 
                                FROM employees e 
                                LEFT JOIN positions p ON e.position_id = p.id 
                                WHERE e.id = ?";
                $manager_stmt = $conn->prepare($manager_query);
                $manager_stmt->execute([$dept['manager_id']]);
                $manager = $manager_stmt->fetch(PDO::FETCH_ASSOC);
            }

            return [
                'id' => $dept['id'],
                'name' => $dept['department_name'],
                'description' => $dept['description'],
                'manager' => [
                    'id' => $dept['manager_id'],
                    'name' => $manager ? $manager['name'] : null,
                    'position' => $manager ? $manager['position_name'] : null
                ],
                'employee_count' => (int)$dept['employee_count'],
                'parent_department' => [
                    'id' => $dept['parent_id'],
                    'name' => $dept['parent_department_name']
                ],
                'created_at' => $dept['created_at'],
                'updated_at' => $dept['updated_at'],
                'status' => $dept['employee_count'] > 0 ? 'active' : 'inactive'
            ];
        }, $departments);

        echo json_encode([
            'status' => 'success',
            'data' => $formatted_departments
        ]);

    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Hàm lấy thông tin phòng ban theo ID
function getDepartmentById() {
    global $conn;
    
    $id = $_GET['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu ID phòng ban'
        ]);
        return;
    }
    
    try {
        $sql = "SELECT d.id, d.name, d.description, d.created_at, d.updated_at,
                       COUNT(e.user_id) as employee_count
                FROM departments d
                LEFT JOIN employees e ON d.id = e.department_id
                WHERE d.id = ?
                GROUP BY d.id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$department) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phòng ban'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $department
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lấy thông tin phòng ban: ' . $e->getMessage()
        ]);
    }
}

// Hàm tạo phòng ban mới
function createDepartment() {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty($data['name'])) {
        jsonResponse(false, 'Tên phòng ban không được để trống');
        return;
    }

    try {
        $sql = "INSERT INTO departments (name, description, manager_id, parent_id, created_at, updated_at) 
                VALUES (:name, :description, :manager_id, :parent_id, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':manager_id', $data['manager_id']);
        $stmt->bindParam(':parent_id', $data['parent_id']);
        
        if ($stmt->execute()) {
            $id = $conn->lastInsertId();
            jsonResponse(true, 'Thêm phòng ban thành công', ['id' => $id]);
        } else {
            jsonResponse(false, 'Không thêm được phòng ban');
        }
    } catch (PDOException $e) {
        jsonResponse(false, 'Lỗi: ' . $e->getMessage());
    }
}

// Hàm cập nhật phòng ban
function updateDepartment() {
    global $conn;
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(false, 'ID phòng ban không hợp lệ');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty($data['name'])) {
        jsonResponse(false, 'Tên phòng ban không được để trống');
        return;
    }

    try {
        $sql = "UPDATE departments 
                SET name = :name, 
                    description = :description,
                    manager_id = :manager_id,
                    parent_id = :parent_id,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':manager_id', $data['manager_id']);
        $stmt->bindParam(':parent_id', $data['parent_id']);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            jsonResponse(true, 'Cập nhật phòng ban thành công');
        } else {
            jsonResponse(false, 'Không cập nhật được phòng ban');
        }
    } catch (PDOException $e) {
        jsonResponse(false, 'Lỗi: ' . $e->getMessage());
    }
}

// Hàm xóa phòng ban
function deleteDepartment() {
    global $conn;
    
    $id = $_GET['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu ID phòng ban'
        ]);
        return;
    }
    
    try {
        // Kiểm tra xem phòng ban có nhân viên không
        $sql = "SELECT COUNT(*) as count FROM employees WHERE department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['count'] > 0) {
            throw new Exception('Không thể xóa phòng ban vì còn nhân viên');
        }
        
        // Xóa phòng ban
        $sql = "DELETE FROM departments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Không tìm thấy phòng ban');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Xóa phòng ban thành công'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi xóa phòng ban: ' . $e->getMessage()
        ]);
    }
}

// Hàm lấy danh sách nhân viên trong phòng ban
function getDepartmentEmployees() {
    global $conn;
    
    $id = $_GET['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu ID phòng ban'
        ]);
        return;
    }
    
    try {
        $sql = "SELECT 
                e.user_id,
                e.name,
                e.email,
                e.phone,
                e.employee_code,
                e.hire_date,
                e.contract_type,
                e.status,
                p.name as position_name,
                up.full_name,
                up.gender,
                up.date_of_birth
                FROM employees e
                JOIN user_profiles up ON e.user_id = up.user_id
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE e.department_id = ?
                ORDER BY e.name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
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