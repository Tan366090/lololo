<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    $page = $data['page'] ?? 1;
    $limit = $data['limit'] ?? 10;
    $search = $data['search'] ?? '';
    $department = $data['department'] ?? '';
    $month = $data['month'] ?? '';

    // Tính offset cho phân trang
    $offset = ($page - 1) * $limit;

    // Xây dựng câu query
    $sql = "
        SELECT 
            p.*,
            e.name as employee_name,
            e.employee_code,
            d.name as department_name
        FROM payroll p
        JOIN employees e ON p.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE 1=1
    ";
    $params = [];

    // Thêm điều kiện tìm kiếm
    if ($search) {
        $sql .= " AND (e.name LIKE :search OR e.employee_code LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($department) {
        $sql .= " AND e.department_id = :department";
        $params['department'] = $department;
    }

    if ($month) {
        $sql .= " AND DATE_FORMAT(p.pay_period_start, '%Y-%m') = :month";
        $params['month'] = $month;
    }

    // Đếm tổng số records
    $countSql = str_replace("p.*, e.name", "COUNT(*)", $sql);
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Thêm phân trang
    $sql .= " ORDER BY p.pay_period_start DESC LIMIT :limit OFFSET :offset";
    $params['limit'] = $limit;
    $params['offset'] = $offset;

    // Thực thi query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'payrolls' => $payrolls,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);

} catch (Exception $e) {
    error_log("Get payroll list error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi tải danh sách lương'
    ]);
} 