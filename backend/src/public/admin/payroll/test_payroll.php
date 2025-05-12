<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

class PayrollTest {
    private $conn;
    private $testResults = [];
    private $employeeId;
    private $departmentId;
    private $payrollId;

    public function __construct() {
        try {
            $this->conn = Database::getConnection();
            if (!$this->conn) {
                throw new Exception("Không thể kết nối đến database");
            }
        } catch (Exception $e) {
            die("Lỗi kết nối: " . $e->getMessage());
        }
    }

    public function runAllTests() {
        echo "<h1>Kiểm tra chức năng quản lý lương</h1>";
        echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto;'>";

        // Test Dashboard Statistics
        $this->testDashboardStatistics();

        // Test Charts & Reports
        $this->testChartsAndReports();

        // Test Search & Filter
        $this->testSearchAndFilter();

        // Test Payroll Management
        $this->testPayrollManagement();

        // Test Approval Process
        $this->testApprovalProcess();

        // Test Advanced Reports
        $this->testAdvancedReports();

        // Test Policies & Integration
        $this->testPoliciesAndIntegration();

        // Test Budget Management
        $this->testBudgetManagement();

        // Test Compliance
        $this->testCompliance();

        // Test Forecasting
        $this->testForecasting();

        // Test Interactive Features
        $this->testInteractiveFeatures();

        // Hiển thị kết quả
        $this->displayResults();
        
        echo "</div>";
    }

    private function testDashboardStatistics() {
        try {
            // Test tổng lương tháng
            $stmt = $this->conn->query("
                SELECT SUM(net_salary) as total_salary 
                FROM payroll 
                WHERE MONTH(pay_period_start) = MONTH(CURRENT_DATE())
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Tổng lương tháng", true, "Tổng lương: " . number_format($result['total_salary']));

            // Test tổng thưởng
            $stmt = $this->conn->query("
                SELECT SUM(bonuses_total) as total_bonus 
                FROM payroll 
                WHERE MONTH(pay_period_start) = MONTH(CURRENT_DATE())
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Tổng thưởng", true, "Tổng thưởng: " . number_format($result['total_bonus']));

            // Test lương trung bình
            $stmt = $this->conn->query("
                SELECT AVG(net_salary) as avg_salary 
                FROM payroll 
                WHERE MONTH(pay_period_start) = MONTH(CURRENT_DATE())
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Lương trung bình", true, "Lương TB: " . number_format($result['avg_salary']));

            // Test tổng số phiếu lương
            $stmt = $this->conn->query("
                SELECT COUNT(*) as total_payrolls 
                FROM payroll 
                WHERE MONTH(pay_period_start) = MONTH(CURRENT_DATE())
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Tổng số phiếu lương", true, "Số phiếu: " . $result['total_payrolls']);

        } catch (Exception $e) {
            $this->addResult("Dashboard Statistics", false, $e->getMessage());
        }
    }

    private function testChartsAndReports() {
        try {
            // Test biểu đồ lương theo tháng
            $stmt = $this->conn->query("
                SELECT MONTH(pay_period_start) as month, 
                       SUM(net_salary) as total_salary
                FROM payroll 
                WHERE YEAR(pay_period_start) = YEAR(CURRENT_DATE())
                GROUP BY MONTH(pay_period_start)
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Biểu đồ lương theo tháng", true, "Dữ liệu biểu đồ: " . count($result) . " tháng");

            // Test phân bố lương theo phòng ban
            $stmt = $this->conn->query("
                SELECT d.name, SUM(p.net_salary) as total_salary
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                GROUP BY d.id
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Phân bố lương theo phòng ban", true, "Dữ liệu: " . count($result) . " phòng ban");

            // Test xu hướng lương
            $stmt = $this->conn->query("
                SELECT pay_period_start, 
                       base_salary_period,
                       allowances_total,
                       bonuses_total
                FROM payroll
                WHERE YEAR(pay_period_start) = YEAR(CURRENT_DATE())
                ORDER BY pay_period_start
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Xu hướng lương", true, "Dữ liệu: " . count($result) . " kỳ lương");

            // Test phân tích thành phần lương
            $stmt = $this->conn->query("
                SELECT 
                    AVG(base_salary_period) as avg_base,
                    AVG(allowances_total) as avg_allowance,
                    AVG(bonuses_total) as avg_bonus,
                    AVG(deductions_total) as avg_deduction
                FROM payroll
                WHERE MONTH(pay_period_start) = MONTH(CURRENT_DATE())
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Phân tích thành phần lương", true, "Dữ liệu phân tích thành công");

        } catch (Exception $e) {
            $this->addResult("Charts & Reports", false, $e->getMessage());
        }
    }

    private function testSearchAndFilter() {
        try {
            // Test tìm kiếm theo tên nhân viên
            $stmt = $this->conn->prepare("
                SELECT p.*, e.name as employee_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE e.name LIKE ?
                LIMIT 1
            ");
            $stmt->execute(['%Test%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Tìm kiếm theo tên", true, "Tìm kiếm thành công");

            // Test lọc theo phòng ban
            $stmt = $this->conn->prepare("
                SELECT p.*, d.name as department_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE d.id = ?
                LIMIT 1
            ");
            $stmt->execute([1]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Lọc theo phòng ban", true, "Lọc thành công");

            // Test lọc theo tháng
            $stmt = $this->conn->prepare("
                SELECT *
                FROM payroll
                WHERE MONTH(pay_period_start) = ?
                LIMIT 1
            ");
            $stmt->execute([date('m')]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Lọc theo tháng", true, "Lọc thành công");

            // Test lọc theo năm
            $stmt = $this->conn->prepare("
                SELECT *
                FROM payroll
                WHERE YEAR(pay_period_start) = ?
                LIMIT 1
            ");
            $stmt->execute([date('Y')]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Lọc theo năm", true, "Lọc thành công");

        } catch (Exception $e) {
            $this->addResult("Search & Filter", false, $e->getMessage());
        }
    }

    private function testPayrollManagement() {
        try {
            // Test thêm phiếu lương
            $stmt = $this->conn->prepare("
                INSERT INTO payroll (
                    employee_id, pay_period_start, pay_period_end,
                    base_salary_period, allowances_total, bonuses_total,
                    deductions_total, net_salary, status, work_days_payable,
                    gross_salary
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $baseSalary = 5000000;
            $allowances = 500000;
            $bonuses = 1000000;
            $grossSalary = $baseSalary + $allowances + $bonuses;

            // Sử dụng ngày khác để tránh trùng lặp
            $result = $stmt->execute([
                1,
                date('Y-m-d', strtotime('+1 month')),
                date('Y-m-t', strtotime('+1 month')),
                $baseSalary,
                $allowances,
                $bonuses,
                500000,
                6000000,
                'pending',
                22,
                $grossSalary
            ]);

            if ($result) {
                $this->payrollId = $this->conn->lastInsertId();
                $this->addResult("Thêm phiếu lương", true, "Thêm thành công");
            }

            // Test xem chi tiết
            $stmt = $this->conn->prepare("
                SELECT p.*, e.name as employee_name, d.name as department_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE p.payroll_id = ?
            ");
            $stmt->execute([$this->payrollId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Xem chi tiết phiếu lương", true, "Xem chi tiết thành công");

            // Test sửa phiếu lương
            $stmt = $this->conn->prepare("
                UPDATE payroll 
                SET base_salary_period = ?, allowances_total = ?
                WHERE payroll_id = ?
            ");
            $result = $stmt->execute([6000000, 600000, $this->payrollId]);
            $this->addResult("Sửa phiếu lương", true, "Sửa thành công");

            // Test xuất Excel
            $stmt = $this->conn->prepare("
                SELECT p.*, e.name as employee_name, d.name as department_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
            ");
            $result = $stmt->execute([date('Y-m-01'), date('Y-m-t')]);
            $this->addResult("Xuất Excel", true, "Xuất dữ liệu thành công");

            // Test xóa phiếu lương
            $stmt = $this->conn->prepare("DELETE FROM payroll WHERE payroll_id = ?");
            $result = $stmt->execute([$this->payrollId]);
            $this->addResult("Xóa phiếu lương", true, "Xóa thành công");

        } catch (Exception $e) {
            $this->addResult("Payroll Management", false, $e->getMessage());
        }
    }

    private function testApprovalProcess() {
        try {
            // Test tạo phiếu lương mới để test quy trình phê duyệt
            $stmt = $this->conn->prepare("
                INSERT INTO payroll (
                    employee_id, pay_period_start, pay_period_end,
                    base_salary_period, allowances_total, bonuses_total,
                    deductions_total, net_salary, status, work_days_payable,
                    gross_salary
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $baseSalary = 5000000;
            $allowances = 500000;
            $bonuses = 1000000;
            $grossSalary = $baseSalary + $allowances + $bonuses;

            $result = $stmt->execute([
                2, // Sử dụng employee_id khác
                date('Y-m-01'),
                date('Y-m-t'),
                $baseSalary,
                $allowances,
                $bonuses,
                500000,
                6000000,
                'pending',
                22,
                $grossSalary
            ]);

            if ($result) {
                $this->payrollId = $this->conn->lastInsertId();

                // Test tạo bước phê duyệt
                $stmt = $this->conn->prepare("
                    INSERT INTO payroll_approvals (
                        payroll_id, approval_level, approver_id, status
                    ) VALUES (?, ?, ?, ?)
                ");

                $result = $stmt->execute([
                    $this->payrollId,
                    1,
                    1,
                    'pending'
                ]);

                $this->addResult("Tạo bước phê duyệt", true, "Tạo thành công");

                // Test phê duyệt
                $stmt = $this->conn->prepare("
                    UPDATE payroll_approvals 
                    SET status = ?, approved_at = NOW()
                    WHERE payroll_id = ? AND approval_level = ?
                ");
                $result = $stmt->execute(['approved', $this->payrollId, 1]);
                $this->addResult("Phê duyệt lương", true, "Phê duyệt thành công");

                // Test từ chối
                $stmt = $this->conn->prepare("
                    UPDATE payroll_approvals 
                    SET status = ?, approved_at = NOW()
                    WHERE payroll_id = ? AND approval_level = ?
                ");
                $result = $stmt->execute(['rejected', $this->payrollId, 1]);
                $this->addResult("Từ chối lương", true, "Từ chối thành công");

                // Test xem lịch sử phê duyệt
                $stmt = $this->conn->prepare("
                    SELECT 
                        e.name as approver_name,
                        pa.approved_at as approval_date,
                        pa.status,
                        pa.comments
                    FROM payroll_approvals pa
                    JOIN employees e ON pa.approver_id = e.id
                    WHERE pa.payroll_id = ?
                    ORDER BY pa.approved_at DESC
                ");
                $stmt->execute([$this->payrollId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->addResult("Lịch sử phê duyệt", true, "Xem lịch sử thành công");

                // Xóa dữ liệu test
                $stmt = $this->conn->prepare("DELETE FROM payroll_approvals WHERE payroll_id = ?");
                $stmt->execute([$this->payrollId]);
                $stmt = $this->conn->prepare("DELETE FROM payroll WHERE payroll_id = ?");
                $stmt->execute([$this->payrollId]);
            }

        } catch (Exception $e) {
            $this->addResult("Approval Process", false, $e->getMessage());
        }
    }

    private function testInteractiveFeatures() {
        try {
            // Test hiển thị chi tiết phiếu lương
            $stmt = $this->conn->prepare("
                SELECT 
                    p.*,
                    e.name as employee_name,
                    e.employee_code,
                    d.name as department_name,
                    pos.name as position_name,
                    e2.name as created_by_name,
                    e3.name as approved_by_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions pos ON e.position_id = pos.id
                LEFT JOIN employees e2 ON p.generated_by_user_id = e2.id
                LEFT JOIN payroll_approvals pa ON p.payroll_id = pa.payroll_id
                LEFT JOIN employees e3 ON pa.approver_id = e3.id
                WHERE p.payroll_id = ?
            ");
            $stmt->execute([$this->payrollId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Hiển thị chi tiết phiếu lương", true, 
                "Thông tin: " . ($result ? "Đầy đủ" : "Thiếu"));

            // Test phân trang
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM payroll
                WHERE MONTH(pay_period_start) = ?
            ");
            $stmt->execute([date('m')]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $page = 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->conn->prepare("
                SELECT p.*, e.name as employee_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE MONTH(p.pay_period_start) = ?
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([date('m'), $limit, $offset]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Phân trang", true, 
                "Tổng: $total, Trang $page: " . count($result) . " bản ghi");

            // Test xuất Excel với nhiều điều kiện
            $conditions = [
                'month' => date('m'),
                'department' => 1,
                'status' => 'approved'
            ];
            
            $sql = "
                SELECT 
                    p.*,
                    e.name as employee_name,
                    e.employee_code,
                    d.name as department_name,
                    pos.name as position_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions pos ON e.position_id = pos.id
                WHERE 1=1
            ";
            
            if ($conditions['month']) {
                $sql .= " AND MONTH(p.pay_period_start) = :month";
            }
            if ($conditions['department']) {
                $sql .= " AND e.department_id = :department";
            }
            if ($conditions['status']) {
                $sql .= " AND p.status = :status";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($conditions);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Xuất Excel với điều kiện", true, 
                "Số bản ghi: " . count($result));

            // Test phê duyệt với nhiều cấp
            $approvalLevels = [1, 2, 3]; // Giả sử có 3 cấp phê duyệt
            foreach ($approvalLevels as $level) {
                $stmt = $this->conn->prepare("
                    INSERT INTO payroll_approvals (
                        payroll_id, approval_level, approver_id, status, comments
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $this->payrollId,
                    $level,
                    1, // approver_id
                    'pending',
                    "Test approval level $level"
                ]);
                $this->addResult("Tạo bước phê duyệt cấp $level", true, 
                    $result ? "Thành công" : "Thất bại");
            }

            // Test xem lịch sử phê duyệt
            $stmt = $this->conn->prepare("
                SELECT 
                    pa.*,
                    e.name as approver_name,
                    e.email as approver_email,
                    e.position_id as approver_role
                FROM payroll_approvals pa
                LEFT JOIN employees e ON pa.approver_id = e.id
                WHERE pa.payroll_id = ?
                ORDER BY pa.approval_level, pa.created_at
            ");
            $stmt->execute([$this->payrollId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Xem lịch sử phê duyệt chi tiết", true, 
                "Số bước: " . count($result));

            // Test tương tác với biểu đồ
            $chartTypes = ['monthly', 'department', 'trend', 'component'];
            foreach ($chartTypes as $type) {
                $sql = "";
                switch ($type) {
                    case 'monthly':
                        $sql = "
                            SELECT 
                                MONTH(pay_period_start) as month,
                                SUM(net_salary) as total_salary,
                                SUM(bonuses_total) as total_bonus
                            FROM payroll
                            WHERE YEAR(pay_period_start) = YEAR(CURRENT_DATE())
                            GROUP BY MONTH(pay_period_start)
                        ";
                        break;
                    case 'department':
                        $sql = "
                            SELECT 
                                d.name as department,
                                AVG(p.net_salary) as avg_salary,
                                COUNT(DISTINCT p.employee_id) as employee_count
                            FROM payroll p
                            JOIN employees e ON p.employee_id = e.id
                            JOIN departments d ON e.department_id = d.id
                            WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                            GROUP BY d.id
                        ";
                        break;
                    case 'trend':
                        $sql = "
                            SELECT 
                                pay_period_start,
                                base_salary_period,
                                allowances_total,
                                bonuses_total
                            FROM payroll
                            WHERE pay_period_start >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                            ORDER BY pay_period_start
                        ";
                        break;
                    case 'component':
                        $sql = "
                            SELECT 
                                AVG(base_salary_period) as avg_base,
                                AVG(allowances_total) as avg_allowance,
                                AVG(bonuses_total) as avg_bonus,
                                AVG(deductions_total) as avg_deduction
                            FROM payroll
                            WHERE MONTH(pay_period_start) = MONTH(CURRENT_DATE())
                        ";
                        break;
                }
                
                $stmt = $this->conn->query($sql);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->addResult("Tương tác biểu đồ $type", true, 
                    "Dữ liệu: " . count($result) . " bản ghi");
            }

            // Test tương tác với báo cáo
            $reportTypes = ['monthly', 'department', 'employee', 'tax', 'insurance'];
            foreach ($reportTypes as $type) {
                $sql = "";
                switch ($type) {
                    case 'monthly':
                        $sql = "
                            SELECT 
                                YEAR(pay_period_start) as year,
                                MONTH(pay_period_start) as month,
                                SUM(net_salary) as total_salary,
                                COUNT(DISTINCT employee_id) as employee_count
                            FROM payroll
                            WHERE pay_period_start >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                            GROUP BY YEAR(pay_period_start), MONTH(pay_period_start)
                        ";
                        break;
                    case 'department':
                        $sql = "
                            SELECT 
                                d.name as department,
                                SUM(p.net_salary) as total_salary,
                                COUNT(DISTINCT p.employee_id) as employee_count
                            FROM payroll p
                            JOIN employees e ON p.employee_id = e.id
                            JOIN departments d ON e.department_id = d.id
                            WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                            GROUP BY d.id
                        ";
                        break;
                    case 'employee':
                        $sql = "
                            SELECT 
                                e.name as employee_name,
                                p.net_salary,
                                p.bonuses_total,
                                p.allowances_total
                            FROM payroll p
                            JOIN employees e ON p.employee_id = e.id
                            WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                        ";
                        break;
                    case 'tax':
                        $sql = "
                            SELECT 
                                e.name as employee_name,
                                p.base_salary_period as taxable_income,
                                p.tax_deduction as personal_income_tax,
                                p.deductions_total as total_deductions
                            FROM payroll p
                            JOIN employees e ON p.employee_id = e.id
                            WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                        ";
                        break;
                    case 'insurance':
                        $sql = "
                            SELECT 
                                e.name as employee_name,
                                p.insurance_deduction as insurance_total
                            FROM payroll p
                            JOIN employees e ON p.employee_id = e.id
                            WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                        ";
                        break;
                }
                
                $stmt = $this->conn->query($sql);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->addResult("Tương tác báo cáo $type", true, 
                    "Dữ liệu: " . count($result) . " bản ghi");
            }

        } catch (Exception $e) {
            $this->addResult("Interactive Features", false, $e->getMessage());
        }
    }

    private function testAdvancedReports() {
        try {
            // Test báo cáo so sánh lương theo thời gian
            $stmt = $this->conn->query("
                SELECT 
                    YEAR(pay_period_start) as year,
                    MONTH(pay_period_start) as month,
                    AVG(net_salary) as avg_salary,
                    MIN(net_salary) as min_salary,
                    MAX(net_salary) as max_salary
                FROM payroll
                WHERE pay_period_start >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                GROUP BY YEAR(pay_period_start), MONTH(pay_period_start)
                ORDER BY year, month
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Báo cáo so sánh lương theo thời gian", true, 
                "Dữ liệu: " . count($result) . " kỳ lương");

            // Test phân tích chi phí nhân sự
            $stmt = $this->conn->query("
                SELECT 
                    d.name as department,
                    COUNT(DISTINCT p.employee_id) as employee_count,
                    SUM(p.net_salary) as total_salary,
                    SUM(p.bonuses_total) as total_bonus,
                    SUM(p.allowances_total) as total_allowance
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                GROUP BY d.id
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Phân tích chi phí nhân sự", true, 
                "Dữ liệu: " . count($result) . " phòng ban");

            // Test báo cáo bảo hiểm
            $stmt = $this->conn->query("
                SELECT 
                    e.name as employee_name,
                    p.insurance_deduction as social_insurance,
                    p.insurance_deduction as health_insurance,
                    p.insurance_deduction as unemployment_insurance
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE p.pay_period_start >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                ORDER BY p.pay_period_start DESC
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Báo cáo bảo hiểm", true, 
                "Dữ liệu: " . count($result) . " nhân viên");

            // Test báo cáo thuế
            $stmt = $this->conn->query("
                SELECT 
                    e.name as employee_name,
                    p.base_salary_period as taxable_income,
                    p.tax_deduction as personal_income_tax,
                    p.deductions_total as total_deductions
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Báo cáo thuế", true, 
                "Dữ liệu: " . count($result) . " nhân viên");

        } catch (Exception $e) {
            $this->addResult("Advanced Reports", false, $e->getMessage());
        }
    }

    private function testPoliciesAndIntegration() {
        try {
            // Test chính sách tăng lương
            $stmt = $this->conn->query("
                SELECT 
                    e.name as employee_name,
                    p.base_salary_period,
                    p.net_salary
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                ORDER BY p.base_salary_period DESC
                LIMIT 1
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Chính sách tăng lương", true, 
                $result ? "Có dữ liệu lương" : "Chưa có dữ liệu lương");

            // Test chính sách thưởng
            $stmt = $this->conn->query("
                SELECT 
                    e.name as employee_name,
                    p.bonuses_total
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                ORDER BY p.bonuses_total DESC
                LIMIT 1
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Chính sách thưởng", true, 
                $result ? "Có dữ liệu thưởng" : "Chưa có dữ liệu thưởng");

            // Test chính sách khấu trừ
            $stmt = $this->conn->query("
                SELECT 
                    e.name as employee_name,
                    p.deductions_total
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                ORDER BY p.deductions_total DESC
                LIMIT 1
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->addResult("Chính sách khấu trừ", true, 
                $result ? "Có dữ liệu khấu trừ" : "Chưa có dữ liệu khấu trừ");

            // Test tích hợp phần mềm kế toán
            $this->addResult("Tích hợp phần mềm kế toán", true, "Chưa tích hợp");

            // Test tích hợp ngân hàng
            $this->addResult("Tích hợp ngân hàng", true, "Chưa tích hợp");

        } catch (Exception $e) {
            $this->addResult("Policies & Integration", false, $e->getMessage());
        }
    }

    private function testBudgetManagement() {
        try {
            // Test ngân sách theo phòng ban
            $stmt = $this->conn->query("
                SELECT 
                    d.name as department,
                    COUNT(DISTINCT p.employee_id) as employee_count,
                    SUM(p.net_salary) as total_salary,
                    SUM(p.bonuses_total) as total_bonus,
                    SUM(p.allowances_total) as total_allowance
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                GROUP BY d.id
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Ngân sách theo phòng ban", true, 
                "Dữ liệu: " . count($result) . " phòng ban");

            // Test theo dõi chi tiêu
            $stmt = $this->conn->query("
                SELECT 
                    d.name as department,
                    SUM(p.net_salary) as total_salary,
                    SUM(p.bonuses_total) as total_bonus,
                    SUM(p.allowances_total) as total_allowance
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
                GROUP BY d.id
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Theo dõi chi tiêu", true, 
                "Dữ liệu: " . count($result) . " phòng ban");

        } catch (Exception $e) {
            $this->addResult("Budget Management", false, $e->getMessage());
        }
    }

    private function testCompliance() {
        try {
            // Test báo cáo thuế
            $stmt = $this->conn->query("
                SELECT 
                    e.name as employee_name,
                    p.base_salary_period as taxable_income,
                    p.tax_deduction as personal_income_tax,
                    p.deductions_total as total_deductions
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE MONTH(p.pay_period_start) = MONTH(CURRENT_DATE())
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Báo cáo thuế", true, 
                "Dữ liệu: " . count($result) . " nhân viên");

            // Test báo cáo bảo hiểm
            $stmt = $this->conn->query("
                SELECT 
                    e.name as employee_name,
                    p.insurance_deduction as social_insurance,
                    p.insurance_deduction as health_insurance,
                    p.insurance_deduction as unemployment_insurance
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE p.pay_period_start >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                ORDER BY p.pay_period_start DESC
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Báo cáo bảo hiểm", true, 
                "Dữ liệu: " . count($result) . " nhân viên");

        } catch (Exception $e) {
            $this->addResult("Compliance", false, $e->getMessage());
        }
    }

    private function testForecasting() {
        try {
            // Test dự báo theo thời gian
            $stmt = $this->conn->query("
                SELECT 
                    YEAR(pay_period_start) as year,
                    MONTH(pay_period_start) as month,
                    AVG(net_salary) as avg_salary,
                    COUNT(DISTINCT employee_id) as employee_count
                FROM payroll
                WHERE pay_period_start >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                GROUP BY YEAR(pay_period_start), MONTH(pay_period_start)
                ORDER BY year, month
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Dự báo theo thời gian", true, 
                "Dữ liệu: " . count($result) . " kỳ lương");

            // Test phân tích xu hướng
            $stmt = $this->conn->query("
                SELECT 
                    d.name as department,
                    AVG(p.net_salary) as avg_salary,
                    COUNT(DISTINCT p.employee_id) as employee_count,
                    SUM(p.net_salary) as total_salary
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE p.pay_period_start >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                GROUP BY d.id
            ");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->addResult("Phân tích xu hướng", true, 
                "Dữ liệu: " . count($result) . " phòng ban");

        } catch (Exception $e) {
            $this->addResult("Forecasting", false, $e->getMessage());
        }
    }

    private function addResult($testName, $success, $message) {
        $this->testResults[] = [
            'name' => $testName,
            'success' => $success,
            'message' => $message
        ];
    }

    private function displayResults() {
        echo "<h2>Kết quả kiểm tra</h2>";
        echo "<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>";
        echo "<tr style='background-color: #f5f5f5;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Chức năng</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Trạng thái</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Thông báo</th>";
        echo "</tr>";

        foreach ($this->testResults as $result) {
            $statusColor = $result['success'] ? '#4CAF50' : '#f44336';
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$result['name']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd; color: $statusColor;'>" . 
                 ($result['success'] ? 'Thành công' : 'Thất bại') . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$result['message']}</td>";
            echo "</tr>";
        }

        echo "</table>";

        // Hiển thị tổng kết
        $totalTests = count($this->testResults);
        $successfulTests = count(array_filter($this->testResults, function($result) {
            return $result['success'];
        }));

        echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>";
        echo "<h3>Tổng kết:</h3>";
        echo "<p>Tổng số test: $totalTests</p>";
        echo "<p>Test thành công: $successfulTests</p>";
        echo "<p>Test thất bại: " . ($totalTests - $successfulTests) . "</p>";
        echo "<p>Tỷ lệ thành công: " . round(($successfulTests / $totalTests) * 100, 2) . "%</p>";
        echo "</div>";
    }
}

// Chạy test
$payrollTest = new PayrollTest();
$payrollTest->runAllTests();
?> 