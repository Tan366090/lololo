<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/security.php';

header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $employeeCode = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
    if (!$employeeCode) {
        throw new Exception('Employee code is required');
    }

    // Prepare and execute query
    $stmt = $pdo->prepare("
        SELECT e.*, d.name as department_name, p.name as position_name
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions p ON e.position_id = p.id
        WHERE e.employee_code = :code
    ");
    $stmt->execute(['code' => $employeeCode]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found'
        ]);
        exit;
    }

    // Get payroll history
    $stmt = $pdo->prepare("
        SELECT p.*, 
               p.base_salary as base_salary_period,
               p.allowances as allowances_total,
               p.bonuses as bonuses_total,
               p.deductions as deductions_total,
               p.net_salary
        FROM payrolls p
        WHERE p.employee_id = :employee_id
        ORDER BY p.pay_period_start DESC
        LIMIT 12
    ");
    $stmt->execute(['employee_id' => $employee['id']]);
    $payrollHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response
    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'payrollHistory' => $payrollHistory
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Employee search error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching for employee'
    ]);
} 