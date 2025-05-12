<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get monthly salary data
    $monthlySalaryQuery = "SELECT 
                            DATE_FORMAT(payroll_date, '%Y-%m') as month,
                            SUM(net_salary) as total_salary,
                            SUM(bonus) as total_bonus,
                            AVG(net_salary) as avg_salary
                          FROM payroll 
                          WHERE payroll_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                          GROUP BY DATE_FORMAT(payroll_date, '%Y-%m')
                          ORDER BY month";

    // Get department salary distribution
    $departmentSalaryQuery = "SELECT 
                              d.department_name,
                              AVG(p.net_salary) as avg_salary,
                              SUM(p.bonus) as total_bonus,
                              COUNT(p.id) as employee_count
                            FROM payroll p
                            JOIN employees e ON p.employee_id = e.id
                            JOIN departments d ON e.department_id = d.id
                            WHERE MONTH(p.payroll_date) = MONTH(CURRENT_DATE())
                            AND YEAR(p.payroll_date) = YEAR(CURRENT_DATE())
                            GROUP BY d.id, d.department_name";

    // Get salary trend data
    $salaryTrendQuery = "SELECT 
                          DATE_FORMAT(payroll_date, '%Y-%m') as month,
                          AVG(basic_salary) as avg_basic_salary,
                          AVG(allowance) as avg_allowance,
                          AVG(bonus) as avg_bonus
                        FROM payroll
                        WHERE payroll_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(payroll_date, '%Y-%m')
                        ORDER BY month";

    // Get salary component data
    $salaryComponentQuery = "SELECT 
                             SUM(basic_salary) as total_basic_salary,
                             SUM(allowance) as total_allowance,
                             SUM(bonus) as total_bonus,
                             SUM(deduction) as total_deduction
                           FROM payroll
                           WHERE MONTH(payroll_date) = MONTH(CURRENT_DATE())
                           AND YEAR(payroll_date) = YEAR(CURRENT_DATE())";

    $monthlySalaryData = $conn->query($monthlySalaryQuery)->fetchAll(PDO::FETCH_ASSOC);
    $departmentSalaryData = $conn->query($departmentSalaryQuery)->fetchAll(PDO::FETCH_ASSOC);
    $salaryTrendData = $conn->query($salaryTrendQuery)->fetchAll(PDO::FETCH_ASSOC);
    $salaryComponentData = $conn->query($salaryComponentQuery)->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'monthlySalary' => $monthlySalaryData,
            'departmentSalary' => $departmentSalaryData,
            'salaryTrend' => $salaryTrendData,
            'salaryComponent' => $salaryComponentData
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} 