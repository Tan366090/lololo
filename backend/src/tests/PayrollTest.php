<?php
// Hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối database
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=qlnhansu",
        "root",
        "",
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        )
    );
} catch(PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// Hàm test tạo phiếu lương
function testCreatePayroll($db) {
    echo "<h3>Test tạo phiếu lương mới:</h3>";
    
    // Lấy thông tin thời gian hiện tại
    $timestamp = time();
    $current_month = date('m', $timestamp);
    $current_day = date('d', $timestamp);
    $fixed_year = '2024'; // Sử dụng năm 2024 cố định
    
    $start_date = $fixed_year . '-' . $current_month . '-01';
    $end_date = $fixed_year . '-' . $current_month . '-' . date('t', $timestamp);
    
    echo "<p>Timestamp hiện tại: " . $timestamp . "</p>";
    echo "<p>Thời gian hiện tại: " . date('d/m/Y H:i:s', $timestamp) . "</p>";
    echo "<p>Năm sử dụng: " . $fixed_year . "</p>";
    echo "<p>Tháng hiện tại: " . $current_month . "</p>";
    echo "<p>Đang tìm nhân viên chưa có phiếu lương trong kỳ: " . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date)) . "</p>";
    
    $sql = "SELECT e.id, e.name 
            FROM employees e 
            WHERE e.status = 'active' 
            AND NOT EXISTS (
                SELECT 1 FROM payroll p 
                WHERE p.employee_id = e.id 
                AND p.pay_period_start = :start_date 
                AND p.pay_period_end = :end_date
            )
            LIMIT 1";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo "<p style='color: orange;'>⚠ Không tìm thấy nhân viên nào chưa có phiếu lương trong kỳ này</p>";
        return;
    }

    // Chuẩn bị dữ liệu test
    $data = [
        'employee_id' => $employee['id'],
        'pay_period_start' => $start_date,
        'pay_period_end' => $end_date,
        'work_days_payable' => 22.0,
        'base_salary_period' => 10000000,
        'allowances_total' => 2000000,
        'bonuses_total' => 1000000,
        'deductions_total' => 500000,
        'gross_salary' => 13000000,
        'tax_deduction' => 1000000,
        'insurance_deduction' => 500000,
        'net_salary' => 11500000,
        'currency' => 'VND',
        'status' => 'pending',
        'notes' => 'Test payroll creation'
    ];

    try {
        // Thực hiện tạo phiếu lương
        $sql = "INSERT INTO payroll (
                    employee_id, pay_period_start, pay_period_end,
                    work_days_payable, base_salary_period, allowances_total,
                    bonuses_total, deductions_total, gross_salary,
                    tax_deduction, insurance_deduction, net_salary,
                    currency, status, notes, generated_at
                ) VALUES (
                    :employee_id, :pay_period_start, :pay_period_end,
                    :work_days_payable, :base_salary_period, :allowances_total,
                    :bonuses_total, :deductions_total, :gross_salary,
                    :tax_deduction, :insurance_deduction, :net_salary,
                    :currency, :status, :notes, NOW()
                )";

        $stmt = $db->prepare($sql);
        $result = $stmt->execute($data);

        if ($result) {
            echo "<p style='color: green;'>✓ Tạo phiếu lương thành công cho nhân viên {$employee['name']}!</p>";
            
            // Kiểm tra dữ liệu đã lưu
            $sql = "SELECT p.*, e.name as employee_name 
                    FROM payroll p 
                    JOIN employees e ON p.employee_id = e.id 
                    WHERE p.employee_id = :employee_id 
                    ORDER BY p.payroll_id DESC LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute(['employee_id' => $data['employee_id']]);
            $savedPayroll = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "<p>Chi tiết phiếu lương đã tạo:</p>";
            echo "<ul>";
            echo "<li>Mã nhân viên: " . $savedPayroll['employee_id'] . "</li>";
            echo "<li>Tên nhân viên: " . $savedPayroll['employee_name'] . "</li>";
            echo "<li>Kỳ lương: " . date('d/m/Y', strtotime($savedPayroll['pay_period_start'])) . " - " . date('d/m/Y', strtotime($savedPayroll['pay_period_end'])) . "</li>";
            echo "<li>Lương cơ bản: " . number_format($savedPayroll['base_salary_period']) . " VND</li>";
            echo "<li>Tổng phụ cấp: " . number_format($savedPayroll['allowances_total']) . " VND</li>";
            echo "<li>Tổng thưởng: " . number_format($savedPayroll['bonuses_total']) . " VND</li>";
            echo "<li>Tổng khấu trừ: " . number_format($savedPayroll['deductions_total']) . " VND</li>";
            echo "<li>Lương thực lĩnh: " . number_format($savedPayroll['net_salary']) . " VND</li>";
            echo "</ul>";
        }
    } catch(PDOException $e) {
        echo "<p style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</p>";
    }
}

// Giao diện HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Tạo Phiếu Lương</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .test-container { 
            border: 1px solid #ddd; 
            padding: 20px; 
            margin: 20px 0;
            border-radius: 5px;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Test Tạo Phiếu Lương</h1>
    
    <div class="test-container">
        <?php testCreatePayroll($db); ?>
    </div>

    <p><a href="PayrollTest.php">Chạy lại test</a></p>
</body>
</html> 