<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "Debug: Script started\n";

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/config.php';

echo "Debug: After requiring files\n";

class PayrollTest {
    private $conn;
    private $testData = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Test 1: Kiểm tra thống kê tổng quan
    public function testDashboardStats() {
        echo "=== Test Dashboard Statistics ===\n";
        
        // Test tổng lương tháng
        $sql = "SELECT SUM(net_salary) as total_salary FROM payroll WHERE MONTH(pay_period_end) = MONTH(CURRENT_DATE())";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        echo "Total Monthly Salary: " . number_format($row['total_salary']) . " VNĐ\n";

        // Test tổng thưởng
        $sql = "SELECT SUM(bonuses_total) as total_bonus FROM payroll WHERE MONTH(pay_period_end) = MONTH(CURRENT_DATE())";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        echo "Total Monthly Bonus: " . number_format($row['total_bonus']) . " VNĐ\n";

        // Test lương trung bình
        $sql = "SELECT AVG(net_salary) as avg_salary FROM payroll WHERE MONTH(pay_period_end) = MONTH(CURRENT_DATE())";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        echo "Average Monthly Salary: " . number_format($row['avg_salary']) . " VNĐ\n";

        // Test tổng số phiếu lương
        $sql = "SELECT COUNT(*) as total_payrolls FROM payroll WHERE MONTH(pay_period_end) = MONTH(CURRENT_DATE())";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        echo "Total Monthly Payrolls: " . $row['total_payrolls'] . "\n";
    }

    // Test 2: Kiểm tra biểu đồ lương theo tháng
    public function testMonthlySalaryChart() {
        echo "\n=== Test Monthly Salary Chart ===\n";
        
        $sql = "SELECT 
                    MONTH(pay_period_end) as month,
                    SUM(net_salary) as total_salary,
                    SUM(bonuses_total) as total_bonus,
                    AVG(net_salary) as avg_salary
                FROM payroll 
                WHERE YEAR(pay_period_end) = YEAR(CURRENT_DATE())
                GROUP BY MONTH(pay_period_end)
                ORDER BY month";
        
        $result = $this->conn->query($sql);
        while($row = $result->fetch_assoc()) {
            echo "Month " . $row['month'] . ":\n";
            echo "  Total Salary: " . number_format($row['total_salary']) . " VNĐ\n";
            echo "  Total Bonus: " . number_format($row['total_bonus']) . " VNĐ\n";
            echo "  Average Salary: " . number_format($row['avg_salary']) . " VNĐ\n";
        }
    }

    // Test 3: Kiểm tra phân bố lương theo phòng ban
    public function testDepartmentSalaryDistribution() {
        echo "\n=== Test Department Salary Distribution ===\n";
        
        $sql = "SELECT 
                    d.name as department_name,
                    COUNT(p.payroll_id) as employee_count,
                    AVG(p.net_salary) as avg_salary,
                    SUM(p.bonuses_total) as total_bonus
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE MONTH(p.pay_period_end) = MONTH(CURRENT_DATE())
                GROUP BY d.id, d.name";
        
        $result = $this->conn->query($sql);
        while($row = $result->fetch_assoc()) {
            echo "Department: " . $row['department_name'] . "\n";
            echo "  Employee Count: " . $row['employee_count'] . "\n";
            echo "  Average Salary: " . number_format($row['avg_salary']) . " VNĐ\n";
            echo "  Total Bonus: " . number_format($row['total_bonus']) . " VNĐ\n";
        }
    }

    // Test 4: Kiểm tra thêm phiếu lương mới
    public function testAddPayroll() {
        echo "\n=== Test Add New Payroll ===\n";
        
        // Kiểm tra nhân viên chưa có bảng lương trong kỳ này
        $sql = "SELECT e.id, e.name 
                FROM employees e 
                LEFT JOIN payroll p ON e.id = p.employee_id 
                    AND p.pay_period_start = ? 
                    AND p.pay_period_end = ?
                WHERE e.status = 'active' 
                AND p.payroll_id IS NULL 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $period_start = date('Y-m-01');
        $period_end = date('Y-m-t');
        $stmt->bind_param("ss", $period_start, $period_end);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo "No eligible employees found for this period\n";
            return;
        }
        $employee = $result->fetch_assoc();
        echo "Using employee: " . $employee['name'] . " (ID: " . $employee['id'] . ")\n";
        
        // Tạo dữ liệu test
        $testData = [
            'employee_id' => $employee['id'],
            'pay_period_start' => $period_start,
            'pay_period_end' => $period_end,
            'work_days_payable' => 22.0,
            'base_salary_period' => 10000000,
            'allowances_total' => 2000000,
            'bonuses_total' => 1000000,
            'deductions_total' => 500000,
            'gross_salary' => 13000000,
            'tax_deduction' => 1300000,
            'insurance_deduction' => 1170000,
            'net_salary' => 10530000,
            'status' => 'pending',
            'generated_by_user_id' => 1
        ];

        // Thêm vào database
        $sql = "INSERT INTO payroll (
                    employee_id, pay_period_start, pay_period_end, work_days_payable,
                    base_salary_period, allowances_total, bonuses_total, deductions_total,
                    gross_salary, tax_deduction, insurance_deduction, net_salary,
                    status, generated_by_user_id, generated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issdddddddddsi", 
            $testData['employee_id'],
            $testData['pay_period_start'],
            $testData['pay_period_end'],
            $testData['work_days_payable'],
            $testData['base_salary_period'],
            $testData['allowances_total'],
            $testData['bonuses_total'],
            $testData['deductions_total'],
            $testData['gross_salary'],
            $testData['tax_deduction'],
            $testData['insurance_deduction'],
            $testData['net_salary'],
            $testData['status'],
            $testData['generated_by_user_id']
        );

        if($stmt->execute()) {
            echo "Successfully added new payroll\n";
            $this->testData['new_payroll_id'] = $stmt->insert_id;
        } else {
            echo "Error adding payroll: " . $stmt->error . "\n";
        }
    }

    // Test 5: Kiểm tra sửa phiếu lương
    public function testEditPayroll() {
        echo "\n=== Test Edit Payroll ===\n";
        
        if(!isset($this->testData['new_payroll_id'])) {
            echo "No payroll to edit\n";
            return;
        }

        $payrollId = $this->testData['new_payroll_id'];
        $newData = [
            'base_salary_period' => 12000000,
            'allowances_total' => 2500000,
            'bonuses_total' => 1500000,
            'deductions_total' => 600000,
            'gross_salary' => 16000000,
            'tax_deduction' => 1600000,
            'insurance_deduction' => 1440000,
            'net_salary' => 12960000
        ];

        $sql = "UPDATE payroll SET 
                base_salary_period = ?,
                allowances_total = ?,
                bonuses_total = ?,
                deductions_total = ?,
                gross_salary = ?,
                tax_deduction = ?,
                insurance_deduction = ?,
                net_salary = ?
                WHERE payroll_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ddddddddi", 
            $newData['base_salary_period'],
            $newData['allowances_total'],
            $newData['bonuses_total'],
            $newData['deductions_total'],
            $newData['gross_salary'],
            $newData['tax_deduction'],
            $newData['insurance_deduction'],
            $newData['net_salary'],
            $payrollId
        );

        if($stmt->execute()) {
            echo "Successfully updated payroll\n";
        } else {
            echo "Error updating payroll: " . $stmt->error . "\n";
        }
    }

    // Test 6: Kiểm tra xóa phiếu lương
    public function testDeletePayroll() {
        echo "\n=== Test Delete Payroll ===\n";
        
        if(!isset($this->testData['new_payroll_id'])) {
            echo "No payroll to delete\n";
            return;
        }

        $payrollId = $this->testData['new_payroll_id'];
        $sql = "DELETE FROM payroll WHERE payroll_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $payrollId);

        if($stmt->execute()) {
            echo "Successfully deleted payroll\n";
        } else {
            echo "Error deleting payroll: " . $stmt->error . "\n";
        }
    }

    // Test 7: Kiểm tra tìm kiếm và lọc
    public function testSearchAndFilter() {
        echo "\n=== Test Search and Filter ===\n";
        
        // Test tìm kiếm theo tên nhân viên
        $searchTerm = "Nguyen";
        $sql = "SELECT p.*, e.name, d.name as department_name 
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE e.name LIKE ?";
        
        $stmt = $this->conn->prepare($sql);
        $searchPattern = "%$searchTerm%";
        $stmt->bind_param("s", $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "Search results for '$searchTerm':\n";
        while($row = $result->fetch_assoc()) {
            echo "Employee: " . $row['name'] . "\n";
            echo "Department: " . $row['department_name'] . "\n";
            echo "Net Salary: " . number_format($row['net_salary']) . " VNĐ\n";
        }

        // Test lọc theo phòng ban
        $departmentId = 1;
        $sql = "SELECT p.*, e.name, d.name as department_name 
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE d.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "\nFilter results for department ID $departmentId:\n";
        while($row = $result->fetch_assoc()) {
            echo "Employee: " . $row['name'] . "\n";
            echo "Department: " . $row['department_name'] . "\n";
            echo "Net Salary: " . number_format($row['net_salary']) . " VNĐ\n";
        }
    }

    // Test 8: Kiểm tra xuất Excel
    public function testExportExcel() {
        echo "\n=== Test Export to Excel ===\n";
        
        // Tạo file Excel
        $filename = "payroll_export_" . date('Y-m-d') . ".xlsx";
        $sql = "SELECT 
                    p.*, e.name, d.name as department_name,
                    u.username as generated_by_name
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                JOIN users u ON p.generated_by_user_id = u.user_id
                WHERE MONTH(p.pay_period_end) = MONTH(CURRENT_DATE())";
        
        $result = $this->conn->query($sql);
        
        // Tạo file Excel (giả lập)
        echo "Creating Excel file: $filename\n";
        echo "Total records to export: " . $result->num_rows . "\n";
        
        // Hiển thị dữ liệu sẽ xuất
        while($row = $result->fetch_assoc()) {
            echo "Exporting record for: " . $row['name'] . "\n";
        }
    }

    // Chạy tất cả các test
    public function runAllTests() {
        $this->testDashboardStats();
        $this->testMonthlySalaryChart();
        $this->testDepartmentSalaryDistribution();
        $this->testAddPayroll();
        $this->testEditPayroll();
        $this->testDeletePayroll();
        $this->testSearchAndFilter();
        $this->testExportExcel();
    }
}

// Khởi tạo và chạy test
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $tester = new PayrollTest($conn);
    $tester->runAllTests();

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 