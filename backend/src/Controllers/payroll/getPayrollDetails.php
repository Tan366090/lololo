<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $payrollId = $_GET['id'];

    // Get payroll details with related information
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            e.employee_code,
            e.name as employee_name,
            d.name as department,
            pos.name as position,
            u.name as created_by
        FROM payrolls p
        LEFT JOIN employees e ON p.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN positions pos ON e.position_id = pos.id
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $payrollId]);
    $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payroll) {
        throw new Exception('Payroll not found');
    }

    // Get deductions breakdown
    $stmt = $pdo->prepare("
        SELECT 
            type,
            amount,
            description
        FROM payroll_deductions
        WHERE payroll_id = :payroll_id
    ");
    $stmt->execute(['payroll_id' => $payrollId]);
    $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get allowances breakdown
    $stmt = $pdo->prepare("
        SELECT 
            type,
            amount,
            description
        FROM payroll_allowances
        WHERE payroll_id = :payroll_id
    ");
    $stmt->execute(['payroll_id' => $payrollId]);
    $allowances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'payroll' => $payroll,
            'deductions' => $deductions,
            'allowances' => $allowances
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 