<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Get date range from request
    $startDate = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING) ?? date('Y-m-01');
    $endDate = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING) ?? date('Y-m-t');

    // Get insurance report data
    $stmt = $pdo->prepare("
        SELECT 
            e.employee_code,
            e.name as employee_name,
            d.name as department,
            p.base_salary,
            p.social_insurance,
            p.health_insurance,
            p.unemployment_insurance,
            p.total_insurance,
            p.pay_period_start,
            p.pay_period_end
        FROM payrolls p
        JOIN employees e ON p.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE p.pay_period_start BETWEEN :start_date AND :end_date
        AND p.status = 'approved'
        ORDER BY d.name, e.employee_code
    ");

    $stmt->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totals = [
        'total_social_insurance' => 0,
        'total_health_insurance' => 0,
        'total_unemployment_insurance' => 0,
        'total_insurance' => 0
    ];

    foreach ($payrolls as $payroll) {
        $totals['total_social_insurance'] += $payroll['social_insurance'];
        $totals['total_health_insurance'] += $payroll['health_insurance'];
        $totals['total_unemployment_insurance'] += $payroll['unemployment_insurance'];
        $totals['total_insurance'] += $payroll['total_insurance'];
    }

    // Get department summaries
    $stmt = $pdo->prepare("
        SELECT 
            d.name as department,
            COUNT(DISTINCT e.id) as employee_count,
            SUM(p.social_insurance) as total_social_insurance,
            SUM(p.health_insurance) as total_health_insurance,
            SUM(p.unemployment_insurance) as total_unemployment_insurance,
            SUM(p.total_insurance) as total_insurance
        FROM payrolls p
        JOIN employees e ON p.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE p.pay_period_start BETWEEN :start_date AND :end_date
        AND p.status = 'approved'
        GROUP BY d.id, d.name
        ORDER BY d.name
    ");

    $stmt->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $departmentSummaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get insurance rate information
    $stmt = $pdo->prepare("
        SELECT 
            type,
            employee_rate,
            employer_rate,
            max_base,
            description
        FROM insurance_rates
        WHERE is_active = 1
        ORDER BY type
    ");
    $stmt->execute();
    $insuranceRates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare report data
    $report = [
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'payrolls' => $payrolls,
        'totals' => $totals,
        'department_summaries' => $departmentSummaries,
        'insurance_rates' => $insuranceRates
    ];

    // Return success response
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);

} catch (Exception $e) {
    error_log("Get insurance report error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 