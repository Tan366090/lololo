<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get total salary for current month
    $totalSalaryQuery = "SELECT COALESCE(SUM(net_salary), 0) as total_salary 
                        FROM payroll 
                        WHERE MONTH(payroll_date) = MONTH(CURRENT_DATE()) 
                        AND YEAR(payroll_date) = YEAR(CURRENT_DATE())";
    
    // Get total bonus for current month
    $totalBonusQuery = "SELECT COALESCE(SUM(bonus), 0) as total_bonus 
                       FROM payroll 
                       WHERE MONTH(payroll_date) = MONTH(CURRENT_DATE()) 
                       AND YEAR(payroll_date) = YEAR(CURRENT_DATE())";
    
    // Get average salary
    $avgSalaryQuery = "SELECT COALESCE(AVG(net_salary), 0) as avg_salary 
                      FROM payroll 
                      WHERE MONTH(payroll_date) = MONTH(CURRENT_DATE()) 
                      AND YEAR(payroll_date) = YEAR(CURRENT_DATE())";
    
    // Get total number of payrolls
    $totalPayrollsQuery = "SELECT COUNT(*) as total_payrolls 
                          FROM payroll 
                          WHERE MONTH(payroll_date) = MONTH(CURRENT_DATE()) 
                          AND YEAR(payroll_date) = YEAR(CURRENT_DATE())";

    // Execute queries with error checking
    $totalSalaryResult = $conn->query($totalSalaryQuery);
    if ($totalSalaryResult === false) {
        throw new PDOException("Error executing total salary query: " . implode(" ", $conn->errorInfo()));
    }
    $totalSalary = $totalSalaryResult->fetch(PDO::FETCH_ASSOC)['total_salary'];

    $totalBonusResult = $conn->query($totalBonusQuery);
    if ($totalBonusResult === false) {
        throw new PDOException("Error executing total bonus query: " . implode(" ", $conn->errorInfo()));
    }
    $totalBonus = $totalBonusResult->fetch(PDO::FETCH_ASSOC)['total_bonus'];

    $avgSalaryResult = $conn->query($avgSalaryQuery);
    if ($avgSalaryResult === false) {
        throw new PDOException("Error executing average salary query: " . implode(" ", $conn->errorInfo()));
    }
    $avgSalary = $avgSalaryResult->fetch(PDO::FETCH_ASSOC)['avg_salary'];

    $totalPayrollsResult = $conn->query($totalPayrollsQuery);
    if ($totalPayrollsResult === false) {
        throw new PDOException("Error executing total payrolls query: " . implode(" ", $conn->errorInfo()));
    }
    $totalPayrolls = $totalPayrollsResult->fetch(PDO::FETCH_ASSOC)['total_payrolls'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'totalSalary' => (float)$totalSalary,
            'totalBonus' => (float)$totalBonus,
            'averageSalary' => (float)$avgSalary,
            'totalPayrolls' => (int)$totalPayrolls
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in getPayrollStats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Server error in getPayrollStats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage(),
        'details' => $e->getMessage()
    ]);
} 