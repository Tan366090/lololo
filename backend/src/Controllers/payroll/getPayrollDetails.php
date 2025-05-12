<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Lấy tham số từ request
    $employeeId = isset($_GET['employee_id']) ? $_GET['employee_id'] : null;
    $payrollId = isset($_GET['payroll_id']) ? $_GET['payroll_id'] : null;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

    // Xây dựng câu query cơ bản
    $query = "
        SELECT * FROM vw_payroll_details 
        WHERE 1=1
    ";
    $params = [];

    // Thêm điều kiện tìm kiếm
    if ($employeeId) {
        $query .= " AND employee_id = :employee_id";
        $params['employee_id'] = $employeeId;
    }
    if ($payrollId) {
        $query .= " AND payroll_id = :payroll_id";
        $params['payroll_id'] = $payrollId;
    }
    if ($startDate) {
        $query .= " AND pay_period_start >= :start_date";
        $params['start_date'] = $startDate;
    }
    if ($endDate) {
        $query .= " AND pay_period_end <= :end_date";
        $params['end_date'] = $endDate;
    }

    // Sắp xếp theo thời gian
    $query .= " ORDER BY pay_period_start DESC";

    // Thực thi query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($payrolls)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy phiếu lương'
        ]);
        exit;
    }

    // Format dữ liệu trả về
    $formattedPayrolls = array_map(function($payroll) {
        return [
            'payroll_id' => $payroll['payroll_id'],
            'employee' => [
                'id' => $payroll['employee_id'],
                'code' => $payroll['employee_code'],
                'name' => $payroll['employee_name']
            ],
            'period' => [
                'start' => $payroll['pay_period_start'],
                'end' => $payroll['pay_period_end'],
                'work_days' => $payroll['work_days_payable']
            ],
            'salary' => [
                'base' => $payroll['base_salary_period'],
                'allowances' => [
                    'total' => $payroll['allowances_total'],
                    'details' => $payroll['total_allowance_amount']
                ],
                'bonuses' => [
                    'total' => $payroll['bonuses_total'],
                    'details' => $payroll['total_bonus_amount']
                ],
                'deductions' => [
                    'total' => $payroll['deductions_total'],
                    'details' => $payroll['total_deduction_amount']
                ],
                'gross' => $payroll['gross_salary'],
                'tax' => $payroll['tax_deduction'],
                'insurance' => $payroll['insurance_deduction'],
                'net' => $payroll['net_salary']
            ],
            'payment' => [
                'date' => $payroll['payment_date'],
                'method' => $payroll['payment_method'],
                'reference' => $payroll['payment_reference'],
                'status' => $payroll['status']
            ],
            'history' => [
                'action_type' => $payroll['action_type'],
                'action_date' => $payroll['action_date'],
                'action_by' => $payroll['action_by_username']
            ],
            'notes' => $payroll['notes']
        ];
    }, $payrolls);

    echo json_encode([
        'success' => true,
        'data' => $formattedPayrolls
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 