<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/database.php';

// Tạm thời set user_id mặc định để test
$_SESSION['user_id'] = 1;

// Lấy method và parameters
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

// Debug request
error_log("Request method: " . $method);
error_log("Request parameters: " . print_r($_GET, true));

// Lấy kết nối database
$db = new Database();
$conn = $db->getConnection();

// Xử lý các methods
switch ($method) {
    case 'GET':
        if ($action === 'statistics') {
            try {
                // Lấy thống kê
                $stmt = $conn->query("
                    SELECT 
                        COUNT(*) as total_leaves,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_leaves,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_leaves,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_leaves
                    FROM leaves
                ");
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $stats]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error getting statistics: ' . $e->getMessage()
                ]);
            }
            exit();
        }

        if ($action === 'top_employees') {
            $stmt = $conn->query("
                SELECT u.username as employee_name, COUNT(*) as total_leaves
                FROM leaves l
                JOIN users u ON l.employee_id = u.user_id
                GROUP BY l.employee_id
                ORDER BY total_leaves DESC
                LIMIT 5
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
            exit();
        }

        if ($action === 'leaves_trend') {
            $stmt = $conn->query("
                SELECT DATE(created_at) as date, COUNT(*) as total
                FROM leaves
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
            exit();
        }

        if ($id) {
            try {
                // Lấy chi tiết một đơn
                $stmt = $conn->prepare("
                    SELECT l.*, 
                           u.username as employee_name,
                           u.email as employee_email
                    FROM leaves l
                    LEFT JOIN users u ON l.employee_id = u.user_id
                    WHERE l.id = ?
                ");
                $stmt->execute([$id]);
                $leave = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($leave) {
                    echo json_encode(['success' => true, 'data' => $leave]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Leave request not found']);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error getting leave details: ' . $e->getMessage()
                ]);
            }
        } else {
            try {
                // Lấy danh sách đơn với phân trang và filter
                $page = $_GET['page'] ?? 1;
                $per_page = $_GET['per_page'] ?? 10;
                $offset = ($page - 1) * $per_page;
                
                $where = [];
                $params = [];
                
                if (isset($_GET['status'])) {
                    $where[] = "l.status = ?";
                    $params[] = $_GET['status'];
                }
                
                if (isset($_GET['leave_type'])) {
                    $where[] = "l.leave_type = ?";
                    $params[] = $_GET['leave_type'];
                }
                
                if (isset($_GET['search'])) {
                    $where[] = "(
                        l.leave_code = ? OR 
                        l.reason LIKE ? OR 
                        l.status LIKE ? OR
                        l.leave_type LIKE ? OR
                        l.approver_comments LIKE ? OR
                        e.name LIKE ?
                    )";
                    $search = "%{$_GET['search']}%";
                    $params[] = $_GET['search']; // Exact match for leave_code
                    $params[] = $search;
                    $params[] = $search;
                    $params[] = $search;
                    $params[] = $search;
                    $params[] = $search; // For employee_name search
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                // Đếm tổng số records
                $countQuery = "SELECT COUNT(*) as total 
                              FROM leaves l 
                              JOIN employees e ON l.employee_id = e.id
                              JOIN users u ON e.user_id = u.user_id 
                              LEFT JOIN users a ON l.approved_by_user_id = a.user_id 
                              $whereClause";
                
                $stmt = $conn->prepare($countQuery);
                $stmt->execute($params);
                $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Lấy danh sách
                $query = "
                    SELECT 
                        l.*,
                        e.name as employee_name,
                        a.username as approver_name,
                        u.email as employee_email
                    FROM leaves l
                    JOIN employees e ON l.employee_id = e.id
                    JOIN users u ON e.user_id = u.user_id
                    LEFT JOIN users a ON l.approved_by_user_id = a.user_id
                    $whereClause
                    ORDER BY l.created_at DESC
                    LIMIT $per_page OFFSET $offset";
                
                $stmt = $conn->prepare($query);
                $stmt->execute($params);
                $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Thêm thông tin gợi ý tìm kiếm nếu có yêu cầu
                if (isset($_GET['action']) && $_GET['action'] === 'suggestions') {
                    $suggestions = [];
                    foreach ($leaves as $leave) {
                        $type = 'leave_code';
                        if (stripos($leave['employee_name'], $_GET['search']) !== false) {
                            $type = 'employee';
                        } elseif (stripos($leave['reason'], $_GET['search']) !== false) {
                            $type = 'reason';
                        } elseif (stripos($leave['status'], $_GET['search']) !== false) {
                            $type = 'status';
                        }

                        $suggestions[] = [
                            'type' => $type,
                            'title' => $leave['leave_code'],
                            'subtitle' => $leave['employee_name'] . ' - ' . $leave['leave_type']
                        ];
                    }

                    echo json_encode([
                        'success' => true,
                        'suggestions' => array_slice($suggestions, 0, 10)
                    ]);
                    exit;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $leaves,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $per_page
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error getting leave list: ' . $e->getMessage()
                ]);
            }
        }
        break;
        
    case 'POST':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['employee_id', 'leave_type', 'start_date', 'end_date', 'reason'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                    exit();
                }
            }
            
            // Calculate duration
            $start = new DateTime($input['start_date']);
            $end = new DateTime($input['end_date']);
            $duration = $start->diff($end)->days + 1;

            // Sinh mã đơn tự động
            $today = date('Ymd');
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM leaves WHERE DATE(created_at) = CURDATE()");
            $countStmt->execute();
            $countToday = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
            $leave_code = 'LV' . $today . '-' . str_pad($countToday, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("
                INSERT INTO leaves (
                    leave_code, employee_id, leave_type, start_date, end_date,
                    leave_duration_days, reason, status, attachment_url,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $leave_code,
                $input['employee_id'],
                $input['leave_type'],
                $input['start_date'],
                $input['end_date'],
                $duration,
                $input['reason'],
                $input['attachment_url'] ?? null
            ]);
            
            $leave_id = $conn->lastInsertId();
            
            // Create notification
            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id, title, message, type,
                    related_entity_type, related_entity_id
                ) VALUES (?, 'Đơn nghỉ phép mới', ?, 'info', 'LeaveRequest', ?)
            ");
            $message = "Có đơn nghỉ phép mới cần duyệt";
            $notif_stmt->execute([$_SESSION['user_id'], $message, $leave_id]);
            
            echo json_encode(['success' => true, 'id' => $leave_id]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creating leave request: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'PUT':
        try {
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Leave request ID is required']);
                exit();
            }
            
            // Kiểm tra đơn có tồn tại không
            $check_stmt = $conn->prepare("SELECT id FROM leaves WHERE id = ?");
            $check_stmt->execute([$id]);
            if (!$check_stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Leave request not found']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields for update
            if (!isset($input['employee_id']) || !isset($input['leave_type']) || 
                !isset($input['start_date']) || !isset($input['end_date'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields for update']);
                exit();
            }

            // Validate date format and logic
            try {
                $start = new DateTime($input['start_date']);
                $end = new DateTime($input['end_date']);
                
                if ($end < $start) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'End date cannot be before start date']);
                    exit();
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid date format']);
                exit();
            }

            // Validate leave type
            $valid_leave_types = ['Annual', 'Sick', 'Personal', 'Other'];
            if (!in_array($input['leave_type'], $valid_leave_types)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid leave type']);
                exit();
            }
            
            // Kiểm tra trạng thái hiện tại của đơn
            $current_status_stmt = $conn->prepare("SELECT status FROM leaves WHERE id = ?");
            $current_status_stmt->execute([$id]);
            $current_status = $current_status_stmt->fetchColumn();

            // Validation cho việc từ chối đơn
            if (isset($input['status']) && $input['status'] === 'rejected') {
                // Kiểm tra lý do từ chối trước
                if (!isset($input['approved_by_user_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Approver ID is required for rejection']);
                    exit();
                }
                if (!isset($input['approver_comments']) || trim($input['approver_comments']) === '') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
                    exit();
                }
                // Sau đó mới kiểm tra trạng thái hiện tại
                if ($current_status === 'rejected') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Leave request is already rejected']);
                    exit();
                }
            }

            // Validation cho việc duyệt đơn
            if (isset($input['status']) && $input['status'] === 'approved') {
                if (!isset($input['approved_by_user_id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Approver ID is required for approval']);
                    exit();
                }
                if ($current_status === 'approved') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Leave request is already approved']);
                    exit();
                }
            }
            
            // Calculate duration if dates are updated
            $start = new DateTime($input['start_date']);
            $end = new DateTime($input['end_date']);
            $duration = $start->diff($end)->days + 1;
            
            $updates = [];
            $params = [];
            
            $fields = [
                'employee_id',
                'leave_type',
                'start_date',
                'end_date',
                'reason',
                'status',
                'approved_by_user_id',
                'approver_comments',
                'attachment_url'
            ];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            $updates[] = "leave_duration_days = ?";
            $params[] = $duration;
            $updates[] = "updated_at = NOW()";
            
            $query = "UPDATE leaves SET " . implode(", ", $updates) . " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['success' => true, 'id' => $id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update leave request']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating leave request: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'DELETE':
        try {
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Leave request ID is required']);
                exit();
            }
            
            // Check if leave request exists
            $check_stmt = $conn->prepare("SELECT id FROM leaves WHERE id = ?");
            $check_stmt->execute([$id]);
            if (!$check_stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Leave request not found']);
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM leaves WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Leave request deleted successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting leave request: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}