<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Get database connection
$conn = Database::getConnection();

try {
    $results = [];
    
    // 1. Kiểm tra cấu trúc bảng
    $query1 = "DESCRIBE employees";
    $stmt1 = $conn->query($query1);
    $results['structure'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Kiểm tra dữ liệu hiện tại
    $query2 = "SELECT id, hire_date, status, MONTH(hire_date) as month, YEAR(hire_date) as year 
               FROM employees 
               WHERE YEAR(hire_date) = YEAR(CURRENT_DATE()) 
               ORDER BY hire_date";
    $stmt2 = $conn->query($query2);
    $results['current_data'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Kiểm tra câu truy vấn đơn giản
    $query3 = "SELECT 
                MONTH(hire_date) as month,
                COUNT(*) as new_hires
               FROM employees 
               WHERE YEAR(hire_date) = YEAR(CURRENT_DATE())
               GROUP BY MONTH(hire_date)
               ORDER BY month";
    $stmt3 = $conn->query($query3);
    $results['simple_query'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Kiểm tra phần terminated với COUNT
    $query4 = "SELECT 
                MONTH(hire_date) as month,
                COUNT(*) as new_hires,
                COUNT(IF(status = 'terminated', 1, NULL)) as terminated_count
               FROM employees 
               WHERE YEAR(hire_date) = YEAR(CURRENT_DATE())
               GROUP BY MONTH(hire_date)
               ORDER BY month";
    $stmt4 = $conn->query($query4);
    $results['terminated_count'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Kiểm tra với SUM
    $query5 = "SELECT 
                MONTH(hire_date) as month,
                COUNT(*) as new_hires,
                SUM(status = 'terminated') as terminated_sum
               FROM employees 
               WHERE YEAR(hire_date) = YEAR(CURRENT_DATE())
               GROUP BY MONTH(hire_date)
               ORDER BY month";
    $stmt5 = $conn->query($query5);
    $results['sum_query'] = $stmt5->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?> 