<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'statistics':
            $query = "SELECT 
                COUNT(*) as total_leaves,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_leaves,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_leaves,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_leaves,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_leaves,
                SUM(CASE WHEN status = 'approved' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as approved_today,
                SUM(CASE WHEN status = 'approved' AND YEARWEEK(created_at) = YEARWEEK(CURDATE()) THEN 1 ELSE 0 END) as approved_this_week,
                SUM(CASE WHEN status = 'approved' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as approved_this_month,
                SUM(CASE WHEN status = 'rejected' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as rejected_today,
                SUM(CASE WHEN status = 'rejected' AND YEARWEEK(created_at) = YEARWEEK(CURDATE()) THEN 1 ELSE 0 END) as rejected_this_week,
                SUM(CASE WHEN status = 'rejected' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as rejected_this_month,
                COUNT(DISTINCT CASE WHEN status = 'pending' THEN employee_id END) as pending_employee_count,
                SUM(CASE WHEN status = 'pending' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as pending_today,
                AVG(CASE WHEN status = 'pending' THEN TIMESTAMPDIFF(HOUR, created_at, NOW()) END) as pending_avg_time
            FROM leaves";
            break;

        case 'top_employees':
            $query = "SELECT 
                e.name as employee_name,
                COUNT(l.id) as total_leaves
            FROM employees e
            LEFT JOIN leaves l ON e.id = l.employee_id
            GROUP BY e.id, e.name
            ORDER BY total_leaves DESC
            LIMIT 5";
            break;

        case 'leaves_trend':
            $query = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total
            FROM leaves
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date";
            break;

        default:
            $query = "SELECT 
                l.*,
                e.name as employee_name,
                lt.name as leave_type_name
            FROM leaves l
            LEFT JOIN employees e ON l.employee_id = e.id
            LEFT JOIN leave_types lt ON l.leave_type_id = lt.id
            ORDER BY l.id";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 