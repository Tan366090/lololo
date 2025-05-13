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

// 1. Get general statistics
function getGeneralStats() {
    global $conn;
    
    try {
        // Total employees with detailed status
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(IF(status = 'active', 1, 0)) as active,
                SUM(IF(status = 'on_leave', 1, 0)) as on_leave,
                SUM(IF(status = 'inactive', 1, 0)) as inactive
            FROM employees
        ");
        $employeeStats = $stmt->fetch();
        $totalEmployees = (int)($employeeStats['total'] ?? 0);
        $activeEmployees = (int)($employeeStats['active'] ?? 0);
        $onLeaveEmployees = (int)($employeeStats['on_leave'] ?? 0);
        $inactiveEmployees = (int)($employeeStats['inactive'] ?? 0);
        
        // New employees this month with salary info
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                COALESCE(AVG(base_salary), 0) as avg_salary
            FROM employees 
            WHERE MONTH(hire_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(hire_date) = YEAR(CURRENT_DATE())
        ");
        $newHireStats = $stmt->fetch();
        $newEmployees = (int)($newHireStats['total'] ?? 0);
        $newEmployeeAvgSalary = (float)($newHireStats['avg_salary'] ?? 0);
        
        // Terminated employees this month
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total
            FROM employees 
            WHERE MONTH(termination_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(termination_date) = YEAR(CURRENT_DATE())
            AND status = 'terminated'
        ");
        $terminationStats = $stmt->fetch();
        $terminatedEmployees = (int)($terminationStats['total'] ?? 0);
        
        // Total salary fund
        $stmt = $conn->query("
            SELECT 
                COALESCE(SUM(base_salary), 0) as total_salary
            FROM employees 
            WHERE status = 'active'
        ");
        $salaryStats = $stmt->fetch();
        $totalSalary = (float)($salaryStats['total_salary'] ?? 0);
        
        // Department statistics
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT manager_id) as total_managers
            FROM departments 
            WHERE status = 'active'
        ");
        $deptStats = $stmt->fetch();
        $totalDepartments = (int)($deptStats['total'] ?? 0);
        $totalManagers = (int)($deptStats['total_managers'] ?? 0);
        
        // Leave statistics with detailed breakdown
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(IF(leave_type = 'Annual', 1, 0)) as annual,
                SUM(IF(leave_type = 'Sick', 1, 0)) as sick,
                SUM(IF(leave_type NOT IN ('Annual', 'Sick'), 1, 0)) as other
            FROM leaves 
            WHERE status = 'approved' 
            AND CURRENT_DATE() BETWEEN start_date AND end_date
        ");
        $leaveStats = $stmt->fetch();
        $onLeave = (int)($leaveStats['total'] ?? 0);
        $annualLeave = (int)($leaveStats['annual'] ?? 0);
        $sickLeave = (int)($leaveStats['sick'] ?? 0);
        $otherLeave = (int)($leaveStats['other'] ?? 0);
        
        // Performance statistics with better filtering
        $stmt = $conn->query("
            SELECT 
                COALESCE(AVG(performance_score), 0) as avg_score,
                COUNT(*) as total_evaluations,
                SUM(IF(performance_score >= 4, 1, 0)) as high_performers,
                SUM(IF(performance_score < 3, 1, 0)) as low_performers
            FROM performances 
            WHERE status = 'completed'
            AND evaluation_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        ");
        $perfStats = $stmt->fetch();
        $avgPerformance = round((float)($perfStats['avg_score'] ?? 0), 1);
        $totalEvaluations = (int)($perfStats['total_evaluations'] ?? 0);
        $highPerformers = (int)($perfStats['high_performers'] ?? 0);
        $lowPerformers = (int)($perfStats['low_performers'] ?? 0);
        
        // Training statistics with better filtering
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT course_id) as unique_courses,
                COUNT(DISTINCT employee_id) as participants
            FROM training_registrations 
            WHERE status = 'attended'
            AND registration_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)
        ");
        $trainingStats = $stmt->fetch();
        $activeTrainings = (int)($trainingStats['total'] ?? 0);
        $uniqueCourses = (int)($trainingStats['unique_courses'] ?? 0);
        $trainingParticipants = (int)($trainingStats['participants'] ?? 0);
        
        // Recruitment statistics
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(IF(status = 'open', 1, 0)) as open_positions
            FROM job_positions
        ");
        $recruitmentStats = $stmt->fetch();
        $openPositions = (int)($recruitmentStats['open_positions'] ?? 0);

        return [
            'totalEmployees' => $totalEmployees,
            'employeeStatus' => [
                'active' => $activeEmployees,
                'onLeave' => $onLeaveEmployees,
                'inactive' => $inactiveEmployees
            ],
            'newEmployees' => [
                'count' => $newEmployees,
                'avgSalary' => formatMoney($newEmployeeAvgSalary)
            ],
            'terminatedEmployees' => [
                'total' => $terminatedEmployees
            ],
            'salaryInfo' => [
                'total' => formatMoney($totalSalary)
            ],
            'departmentInfo' => [
                'total' => $totalDepartments,
                'managers' => $totalManagers
            ],
            'leaveInfo' => [
                'total' => $onLeave,
                'annual' => $annualLeave,
                'sick' => $sickLeave,
                'other' => $otherLeave
            ],
            'performanceInfo' => [
                'avgScore' => $avgPerformance,
                'totalEvaluations' => $totalEvaluations,
                'highPerformers' => $highPerformers,
                'lowPerformers' => $lowPerformers
            ],
            'trainingInfo' => [
                'activeSessions' => $activeTrainings,
                'uniqueCourses' => $uniqueCourses,
                'participants' => $trainingParticipants
            ],
            'recruitmentInfo' => [
                'openPositions' => $openPositions
            ]
        ];
    } catch (Exception $e) {
        error_log("Error in getGeneralStats: " . $e->getMessage());
        sendResponse(['error' => 'Failed to fetch general statistics'], 500);
    }
}

// 2. Get employee distribution by department
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

// 3. Get employee trend data
function getEmployeeTrend() {
    global $conn;
    
    try {
        // Get new hires
        $queryNew = "SELECT 
                        MONTH(hire_date) as month,
                        COUNT(*) as newHires,
                        AVG(base_salary) as avgSalary
                     FROM employees 
                     WHERE YEAR(hire_date) = YEAR(CURRENT_DATE())
                     GROUP BY MONTH(hire_date)
                     ORDER BY month";
        
        // Get terminations
        $queryTerminated = "SELECT 
                            MONTH(termination_date) as month,
                            COUNT(*) as terminated
                         FROM employees 
                         WHERE YEAR(termination_date) = YEAR(CURRENT_DATE())
                         GROUP BY MONTH(termination_date)
                         ORDER BY month";
        
        $resultNew = $conn->query($queryNew);
        $resultTerminated = $conn->query($queryTerminated);
        
        $data = [];
        $months = [];
        
        // Initialize months array
        for($i = 1; $i <= 12; $i++) {
            $months[$i] = [
                'month' => $i,
                'newHires' => 0,
                'terminated' => 0,
                'avgSalary' => 0
            ];
        }
        
        // Update new hires data
        while($row = $resultNew->fetch()) {
            $months[$row['month']]['newHires'] = (int)$row['newHires'];
            $months[$row['month']]['avgSalary'] = (float)$row['avgSalary'];
        }
        
        // Update terminations data
        while($row = $resultTerminated->fetch()) {
            $months[$row['month']]['terminated'] = (int)$row['terminated'];
        }
        
        // Convert to array
        foreach($months as $month) {
            $data[] = $month;
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 4. Get salary cost data
function getSalaryCost() {
    global $conn;
    
    try {
        // Get actual salary costs from payroll
        $queryActual = "SELECT 
                        MONTH(pay_period_start) as month,
                        SUM(gross_salary) as actualCost,
                        SUM(allowances_total) as allowances,
                        SUM(bonuses_total) as bonuses,
                        SUM(deductions_total) as deductions
                     FROM payroll 
                     WHERE YEAR(pay_period_start) = YEAR(CURRENT_DATE())
                     GROUP BY MONTH(pay_period_start)
                     ORDER BY month";
        
        $resultActual = $conn->query($queryActual);
        $data = [];
        $months = [];
        
        // Initialize months array
        for($i = 1; $i <= 12; $i++) {
            $months[$i] = [
                'month' => $i,
                'actualCost' => 0,
                'allowances' => 0,
                'bonuses' => 0,
                'deductions' => 0
            ];
        }
        
        // Update actual costs
        while($row = $resultActual->fetch()) {
            $months[$row['month']]['actualCost'] = (float)$row['actualCost'];
            $months[$row['month']]['allowances'] = (float)$row['allowances'];
            $months[$row['month']]['bonuses'] = (float)$row['bonuses'];
            $months[$row['month']]['deductions'] = (float)$row['deductions'];
        }
        
        // Convert to array
        foreach($months as $month) {
            $data[] = $month;
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 5. Get age distribution
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
                        ELSE '46-50'
                    END as ageGroup,
                    up.gender,
                    COUNT(*) as count,
                    AVG(e.base_salary) as avgSalary,
                    AVG(p.performance_score) as avgPerformance
                 FROM user_profiles up
                 JOIN employees e ON up.user_id = e.user_id
                 LEFT JOIN performances p ON e.id = p.employee_id
                 WHERE e.status = 'active'
                 GROUP BY ageGroup, up.gender
                 ORDER BY ageGroup";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch()) {
            $data[] = [
                'ageGroup' => $row['ageGroup'],
                'gender' => $row['gender'],
                'count' => (int)$row['count'],
                'avgSalary' => formatMoney($row['avgSalary']),
                'avgPerformance' => round((float)$row['avgPerformance'], 1)
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 6. Get performance evaluation data
function getPerformanceEvaluation() {
    global $conn;
    
    try {
        $query = "SELECT 
                    AVG(performance_score) as currentScore,
                    AVG(score) as targetScore,
                    COUNT(*) as totalEvaluations,
                    SUM(CASE WHEN performance_score >= 4 THEN 1 ELSE 0 END) as highPerformers,
                    SUM(CASE WHEN performance_score < 3 THEN 1 ELSE 0 END) as lowPerformers
                 FROM performances 
                 WHERE status = 'completed'";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch()) {
            $data[] = [
                'criteria' => 'Overall Performance',
                'currentScore' => round((float)$row['currentScore'], 1),
                'targetScore' => round((float)$row['targetScore'], 1),
                'totalEvaluations' => (int)$row['totalEvaluations'],
                'highPerformers' => (int)$row['highPerformers'],
                'lowPerformers' => (int)$row['lowPerformers']
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 7. Get top departments
function getTopDepartments() {
    global $conn;
    
    try {
        $query = "SELECT 
                    d.name as department,
                    AVG(p.performance_score) as kpi,
                    COUNT(e.id) as employeeCount,
                    SUM(e.base_salary) as totalSalary,
                    COUNT(DISTINCT pr.id) as activeProjects
                 FROM departments d
                 LEFT JOIN employees e ON d.id = e.department_id
                 LEFT JOIN performances p ON e.id = p.employee_id
                 LEFT JOIN project_resources pr ON e.id = pr.employee_id
                 WHERE d.status = 'active' AND e.status = 'active'
                 GROUP BY d.id, d.name
                 ORDER BY kpi DESC
                 LIMIT 5";
        
        $stmt = $conn->query($query);
        $data = [];
        
        while($row = $stmt->fetch()) {
            $data[] = [
                'department' => $row['department'],
                'kpi' => round((float)$row['kpi'], 1),
                'employeeCount' => (int)$row['employeeCount'],
                'totalSalary' => formatMoney($row['totalSalary']),
                'activeProjects' => (int)$row['activeProjects']
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 8. Get quick actions data
function getQuickActions() {
    global $conn;
    
    try {
        // Pending leave requests
        $stmt = $conn->query("SELECT COUNT(*) as total FROM leaves WHERE status = 'pending'");
        $pendingLeaves = $stmt->fetch()['total'];
        
        // Pending payroll approvals
        $stmt = $conn->query("SELECT COUNT(*) as total FROM payroll_approvals WHERE status = 'pending'");
        $pendingPayroll = $stmt->fetch()['total'];
        
        // Pending performance reviews
        $stmt = $conn->query("SELECT COUNT(*) as total FROM performances WHERE status = 'draft'");
        $pendingReviews = $stmt->fetch()['total'];
        
        // Pending tasks
        $stmt = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'pending'");
        $pendingTasks = $stmt->fetch()['total'];

        // Pending job applications
        $stmt = $conn->query("SELECT COUNT(*) as total FROM job_applications WHERE status = 'pending'");
        $pendingApplications = $stmt->fetch()['total'];

        // Upcoming interviews
        $stmt = $conn->query("SELECT COUNT(*) as total FROM interviews WHERE interview_date >= CURRENT_DATE()");
        $upcomingInterviews = $stmt->fetch()['total'];

        return [
            'pendingLeaves' => $pendingLeaves,
            'pendingPayroll' => $pendingPayroll,
            'pendingReviews' => $pendingReviews,
            'pendingTasks' => $pendingTasks,
            'pendingApplications' => $pendingApplications,
            'upcomingInterviews' => $upcomingInterviews
        ];
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// 9. Get footer data
function getFooterData() {
    global $conn;
    
    try {
        // System info
        $systemInfo = [
            'version' => 'v3.0.1',
            'lastUpdate' => date('d/m/Y'),
            'backupStatus' => 'Active',
            'serverStatus' => 'Online'
        ];
        
        // Important warnings
        $stmt = $conn->query("SELECT COUNT(*) as total FROM contracts WHERE status = 'active' AND end_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)");
        $expiringContracts = $stmt->fetch()['total'];
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM training_courses WHERE status = 'active' AND created_at <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)");
        $upcomingEvents = $stmt->fetch()['total'];
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM system_logs WHERE log_level IN ('error', 'critical') AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 24 HOUR)");
        $issues = $stmt->fetch()['total'];
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'pending' AND due_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)");
        $pendingTasks = $stmt->fetch()['total'];
        
        $warnings = [
            'expiringContracts' => $expiringContracts,
            'upcomingEvents' => $upcomingEvents,
            'issues' => $issues,
            'pendingTasks' => $pendingTasks
        ];
        
        // Quick stats
        $stmt = $conn->query("SELECT COUNT(*) as total FROM system_logs WHERE log_type = 'Application' AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 24 HOUR)");
        $visits = $stmt->fetch()['total'];
        
        $stmt = $conn->query("SELECT COUNT(*) as total FROM system_logs WHERE log_level IN ('error', 'critical') AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 24 HOUR)");
        $systemErrors = $stmt->fetch()['total'];
        
        $stmt = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM sessions WHERE last_activity >= DATE_SUB(CURRENT_DATE(), INTERVAL 24 HOUR)");
        $activeUsers = $stmt->fetch()['total'];
        
        $quickStats = [
            'visits' => $visits,
            'uptime' => '99.9%',
            'lastActivity' => '5 minutes ago',
            'systemErrors' => $systemErrors,
            'activeUsers' => $activeUsers
        ];

        return [
            'systemInfo' => $systemInfo,
            'warnings' => $warnings,
            'quickStats' => $quickStats
        ];
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

// Handle request
$action = $_GET['action'] ?? '';

switch($action) {
    case 'general-stats':
        sendResponse(getGeneralStats());
        break;
        
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
        
    case 'quick-actions':
        sendResponse(getQuickActions());
        break;
        
    case 'footer-data':
        sendResponse(getFooterData());
        break;
        
    default:
        sendResponse(['error' => 'Invalid action'], 400);
}
?>
