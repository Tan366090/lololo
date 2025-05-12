<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Get date range from request
    $startDate = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING) ?? date('Y-m-01');
    $endDate = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING) ?? date('Y-m-t');

    // Validate dates
    if (!validateDate($startDate) || !validateDate($endDate)) {
        throw new Exception('Invalid date format');
    }

    // Get tax report data
    $stmt = $pdo->prepare("
        SELECT 
            e.employee_code,
            e.name as employee_name,
            e.tax_code,
            d.name as department,
            p.base_salary,
            p.allowances,
            p.bonuses,
            p.total_income,
            p.taxable_income,
            p.personal_tax,
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
        'total_income' => 0,
        'total_taxable_income' => 0,
        'total_personal_tax' => 0
    ];

    foreach ($payrolls as $payroll) {
        $totals['total_income'] += $payroll['total_income'];
        $totals['total_taxable_income'] += $payroll['taxable_income'];
        $totals['total_personal_tax'] += $payroll['personal_tax'];
    }

    // Get department summaries
    $stmt = $pdo->prepare("
        SELECT 
            d.name as department,
            COUNT(DISTINCT e.id) as employee_count,
            SUM(p.total_income) as total_income,
            SUM(p.taxable_income) as total_taxable_income,
            SUM(p.personal_tax) as total_personal_tax
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

    // Get tax rate information
    $stmt = $pdo->prepare("
        SELECT 
            level,
            min_income,
            max_income,
            rate,
            description
        FROM tax_rates
        WHERE is_active = 1
        ORDER BY level
    ");
    $stmt->execute();
    $taxRates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get tax deductions
    $stmt = $pdo->prepare("
        SELECT 
            type,
            amount,
            description
        FROM tax_deductions
        WHERE is_active = 1
        ORDER BY type
    ");
    $stmt->execute();
    $taxDeductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare report data
    $report = [
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'payrolls' => $payrolls,
        'totals' => $totals,
        'department_summaries' => $departmentSummaries,
        'tax_rates' => $taxRates,
        'tax_deductions' => $taxDeductions
    ];

    // Return success response
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Get tax report error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to validate date format
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
} 