<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Get database connection
$conn = Database::getConnection();

// Helper function to format response
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Helper function to format money
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.');
}

// 1. Phân bố nhân viên theo phòng ban
function getEmployeeDistribution() {
    global $conn;
    try {
        $query = "SELECT 
                    d.name as department,
                    COUNT(e.id) as count,
                    AVG(p.performance_score) as avgPerformance,
                    SUM(e.base_salary) as totalSalary
                 FROM departments d 
                 LEFT JOIN employees e ON d.id = e.department_id 
                 LEFT JOIN performances p ON e.id = p.employee_id
                 WHERE d.status = 'active' AND e.status = 'active'
                 GROUP BY d.id, d.name";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch()) {
            $data[] = [
                'department' => $row['department'],
                'count' => (int)$row['count'],
                'avgPerformance' => round((float)$row['avgPerformance'], 1),
                'totalSalary' => formatMoney($row['totalSalary'])
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 2. Xu hướng nhân sự
function getEmployeeTrend() {
    global $conn;
    try {
        $query = "SELECT 
                    MONTH(hire_date) as month,
                    COUNT(*) as new_hires,
                    COUNT(IF(status = 'terminated', 1, NULL)) as terminated_count
                 FROM employees 
                 WHERE YEAR(hire_date) = YEAR(CURRENT_DATE())
                 GROUP BY MONTH(hire_date)
                 ORDER BY month";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'month' => (int)$row['month'],
                'new_hires' => (int)$row['new_hires'],
                'terminated' => (int)$row['terminated_count']
            ];
        }
        
        return [
            'status' => 'success',
            'data' => $data
        ];
    } catch (Exception $e) {
        error_log("Error in getEmployeeTrend: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// 3. Chi phí lương theo tháng
function getSalaryCost() {
    global $conn;
    try {
        $query = "SELECT 
                    MONTH(pay_period_start) as month,
                    SUM(gross_salary) as totalSalary,
                    SUM(allowances_total) as allowances,
                    SUM(bonuses_total) as bonuses
                 FROM payroll 
                 WHERE YEAR(pay_period_start) = YEAR(CURRENT_DATE())
                 GROUP BY MONTH(pay_period_start)
                 ORDER BY month";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch()) {
            $data[] = [
                'month' => (int)$row['month'],
                'totalSalary' => (float)$row['totalSalary'],
                'allowances' => (float)$row['allowances'],
                'bonuses' => (float)$row['bonuses']
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 4. Phân bố độ tuổi
function getAgeDistribution() {
    global $conn;
    try {
        $query = "SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, up.date_of_birth, CURRENT_DATE()) BETWEEN 20 AND 25 THEN '20-25'
                        WHEN TIMESTAMPDIFF(YEAR, up.date_of_birth, CURRENT_DATE()) BETWEEN 26 AND 30 THEN '26-30'
                        WHEN TIMESTAMPDIFF(YEAR, up.date_of_birth, CURRENT_DATE()) BETWEEN 31 AND 35 THEN '31-35'
                        WHEN TIMESTAMPDIFF(YEAR, up.date_of_birth, CURRENT_DATE()) BETWEEN 36 AND 40 THEN '36-40'
                        WHEN TIMESTAMPDIFF(YEAR, up.date_of_birth, CURRENT_DATE()) BETWEEN 41 AND 45 THEN '41-45'
                        ELSE '46+'
                    END as ageGroup,
                    up.gender,
                    COUNT(*) as count
                 FROM user_profiles up
                 JOIN employees e ON up.user_id = e.user_id
                 WHERE e.status = 'active'
                 GROUP BY ageGroup, up.gender
                 ORDER BY ageGroup";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch()) {
            $data[] = [
                'ageGroup' => $row['ageGroup'],
                'gender' => $row['gender'],
                'count' => (int)$row['count']
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 5. Đánh giá năng lực
function getPerformanceEvaluation() {
    global $conn;
    try {
        $query = "SELECT 
                    AVG(performance_score) as avgScore,
                    COUNT(*) as totalEvaluations,
                    SUM(CASE WHEN performance_score >= 4 THEN 1 ELSE 0 END) as highPerformers,
                    SUM(CASE WHEN performance_score < 3 THEN 1 ELSE 0 END) as lowPerformers
                 FROM performances 
                 WHERE performance_score IS NOT NULL";
        
        $stmt = $conn->query($query);
        $row = $stmt->fetch();
        
        return [
            'avgScore' => round((float)$row['avgScore'], 1),
            'totalEvaluations' => (int)$row['totalEvaluations'],
            'highPerformers' => (int)$row['highPerformers'],
            'lowPerformers' => (int)$row['lowPerformers']
        ];
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 6. Top 5 phòng ban
function getTopDepartments() {
    global $conn;
    try {
        $query = "SELECT 
                    d.name as department,
                    COUNT(e.id) as employeeCount,
                    AVG(p.performance_score) as avgPerformance,
                    SUM(e.base_salary) as totalSalary
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id
                 LEFT JOIN performances p ON e.id = p.employee_id
                 WHERE d.status = 'active' AND e.status = 'active'
                 GROUP BY d.id, d.name
                 ORDER BY avgPerformance DESC
                 LIMIT 5";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch()) {
            $data[] = [
                'department' => $row['department'],
                'employeeCount' => (int)$row['employeeCount'],
                'avgPerformance' => round((float)$row['avgPerformance'], 1),
                'totalSalary' => formatMoney($row['totalSalary'])
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

function getMonthlySalaryCosts() {
    global $conn;
    
    $sql = "SELECT 
                DATE_FORMAT(pay_period_start, '%Y-%m') as month,
                SUM(gross_salary) as total_salary,
                SUM(base_salary_period) as base_salary,
                SUM(allowances_total) as allowances,
                SUM(bonuses_total) as bonuses,
                SUM(tax_deduction) as tax,
                SUM(insurance_deduction) as insurance,
                COUNT(DISTINCT employee_id) as employee_count
            FROM payroll 
            WHERE pay_period_start BETWEEN '2023-01-01' AND '2024-12-31'
            GROUP BY DATE_FORMAT(pay_period_start, '%Y-%m')
            HAVING employee_count = 10
            ORDER BY month ASC";
            
    $result = $conn->query($sql);
    $data = [];
    
    if ($result) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'month' => $row['month'],
                'total_salary' => (float)$row['total_salary'],
                'base_salary' => (float)$row['base_salary'],
                'allowances' => (float)$row['allowances'],
                'bonuses' => (float)$row['bonuses'],
                'tax' => (float)$row['tax'],
                'insurance' => (float)$row['insurance']
            ];
        }
    }
    
    return $data;
}

// Xử lý request
$action = $_GET['action'] ?? '';

switch($action) {
    case 'employee-distribution':
        sendResponse(getEmployeeDistribution());
        break;
        
    case 'employee-trend':
        sendResponse(getEmployeeTrend());
        break;
        
    case 'salary-cost':
        sendResponse(getSalaryCost());
        break;
        
    case 'age-distribution':
        sendResponse(getAgeDistribution());
        break;
        
    case 'performance-evaluation':
        sendResponse(getPerformanceEvaluation());
        break;
        
    case 'top-departments':
        sendResponse(getTopDepartments());
        break;
        
    case 'monthly-salary-costs':
        $response = getMonthlySalaryCosts();
        sendResponse($response);
        break;
        
    default:
        sendResponse(['error' => 'Invalid action'], 400);
}
?>