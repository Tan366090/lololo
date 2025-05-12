<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header("Content-Security-Policy: default-src 'self'; font-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:;");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../controllers/PayrollController.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $payrollController = new \App\Controllers\PayrollController();

    // Handle GET request for single payroll record
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        try {
            $id = (int)$_GET['id'];
            error_log("Fetching payroll details for ID: " . $id);
            
            $result = $payrollController->show($id);
            
            if (!$result['success']) {
                error_log("Error in payroll details: " . $result['message']);
                http_response_code(404);
            }
            
            echo json_encode($result);
            exit;
        } catch (\Exception $e) {
            error_log("Error fetching payroll details: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi xem chi tiết phiếu lương: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Get query parameters for list view
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $department = isset($_GET['department']) ? $_GET['department'] : '';
    $month = isset($_GET['month']) ? $_GET['month'] : '';
    $year = isset($_GET['year']) ? $_GET['year'] : '';

    // Build base query
    $query = "SELECT 
                p.payroll_id as id,
                p.employee_id,
                e.name as employee_name,
                e.employee_code as employee_code,
                d.name as department_name,
                p.pay_period_start,
                p.pay_period_end,
                p.work_days_payable,
                p.base_salary_period,
                p.allowances_total,
                p.bonuses_total,
                p.deductions_total,
                p.gross_salary,
                p.tax_deduction,
                p.insurance_deduction,
                p.net_salary,
                p.currency,
                p.payment_date,
                p.status,
                p.generated_at as created_at,
                p.generated_by_user_id as created_by,
                p.notes,
                p.payment_method,
                p.payment_reference,
                u.username as created_by_username
            FROM payroll p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN users u ON p.generated_by_user_id = u.user_id
            WHERE 1=1";

    $params = [];

    // Add search condition
    if ($search) {
        $query .= " AND (e.name LIKE ? OR e.employee_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Add department filter
    if ($department) {
        $query .= " AND e.department_id = ?";
        $params[] = $department;
    }

    // Add month filter
    if ($month) {
        $query .= " AND MONTH(p.pay_period_start) = ?";
        $params[] = $month;
    }

    // Add year filter
    if ($year) {
        $query .= " AND YEAR(p.pay_period_start) = ?";
        $params[] = $year;
    }

    // Get total count for pagination
    $countQuery = str_replace("SELECT p.payroll_id as id,", "SELECT COUNT(*) as total", $query);
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalItems / $limit);

    // Add pagination with named parameters
    $query .= " ORDER BY p.pay_period_start DESC, p.generated_at DESC LIMIT :limit OFFSET :offset";
    
    // Execute main query
    $stmt = $conn->prepare($query);
    
    // Bind all existing parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)(($page - 1) * $limit), PDO::PARAM_INT);
    
    $stmt->execute();
    $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data
    $formattedPayrolls = array_map(function($pay) {
        return [
            'id' => $pay['id'],
            'employee' => [
                'id' => $pay['employee_id'],
                'code' => $pay['employee_code'],
                'name' => $pay['employee_name'],
                'department' => $pay['department_name']
            ],
            'period' => [
                'start' => $pay['pay_period_start'],
                'end' => $pay['pay_period_end'],
                'month' => date('m/Y', strtotime($pay['pay_period_start'])),
                'work_days' => $pay['work_days_payable']
            ],
            'salary' => [
                'base' => number_format($pay['base_salary_period'], 0, ',', '.'),
                'allowances' => number_format($pay['allowances_total'], 0, ',', '.'),
                'bonuses' => number_format($pay['bonuses_total'], 0, ',', '.'),
                'deductions' => number_format($pay['deductions_total'], 0, ',', '.'),
                'gross' => number_format($pay['gross_salary'], 0, ',', '.'),
                'tax' => number_format($pay['tax_deduction'], 0, ',', '.'),
                'insurance' => number_format($pay['insurance_deduction'], 0, ',', '.'),
                'net' => number_format($pay['net_salary'], 0, ',', '.')
            ],
            'payment' => [
                'date' => $pay['payment_date'],
                'method' => $pay['payment_method'],
                'method_text' => $pay['payment_method'] === 'bank_transfer' ? 'Chuyển khoản' : 'Tiền mặt',
                'reference' => $pay['payment_reference']
            ],
            'status' => [
                'code' => $pay['status'],
                'text' => $pay['status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán'
            ],
            'created_by' => [
                'id' => $pay['created_by'],
                'username' => $pay['created_by_username']
            ],
            'created_at' => $pay['created_at'],
            'notes' => $pay['notes']
        ];
    }, $payrolls);

    echo json_encode([
        'success' => true,
        'data' => $formattedPayrolls,
        'totalItems' => $totalItems,
        'currentPage' => $page,
        'totalPages' => $totalPages
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?> 