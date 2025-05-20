<?php
// Prevent PHP errors from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON content type
header('Content-Type: application/json');

require_once '../../../../config/database.php';

// Hàm ghi log lỗi
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, __DIR__ . '/import_errors.log');
}

// Hàm ghi log thông tin
function logInfo($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, __DIR__ . '/import_info.log');
}

// Hàm trả về JSON response
function jsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Hàm đọc và parse file TXT
function parsePayrollFile($filePath) {
    try {
        if (!file_exists($filePath)) {
            throw new Exception("File không tồn tại");
        }
        
        $payrollData = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            throw new Exception("Không thể đọc file");
        }
        
        foreach ($lines as $line) {
            if (strpos($line, '=') === false) {
                continue;
            }
            list($key, $value) = explode('=', $line, 2);
            $payrollData[trim($key)] = trim($value);
        }

        // Log dữ liệu đọc được
        logInfo("Dữ liệu đọc từ file:");
        foreach ($payrollData as $key => $value) {
            logInfo("$key = $value");
        }
        
        return $payrollData;
    } catch (Exception $e) {
        logError("Lỗi parse file: " . $e->getMessage());
        throw $e;
    }
}

// Hàm validate và lấy thông tin nhân viên
function validateEmployeeData($data) {
    global $conn;
    
    try {
        // Validate dữ liệu
        $requiredFields = [
            'employee_code', 'pay_period_start', 'pay_period_end', 'work_days_payable',
            'base_salary_period', 'gross_salary', 'net_salary'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Thiếu trường bắt buộc: {$field}");
            }
        }

        // Kiểm tra nhân viên tồn tại và đang active
        $stmt = $conn->prepare("
            SELECT e.id, e.name, e.employee_code, e.base_salary, d.name as department_name 
            FROM employees e 
            LEFT JOIN departments d ON e.department_id = d.id 
            WHERE e.employee_code = ? AND e.status = 'active'
        ");
        $stmt->execute([$data['employee_code']]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            throw new Exception("Không tìm thấy nhân viên đang hoạt động với mã: {$data['employee_code']}");
        }

        return [
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee['id'],
                    'code' => $employee['employee_code'],
                    'name' => $employee['name'],
                    'department' => $employee['department_name'],
                    'base_salary' => number_format($employee['base_salary'], 0, ',', '.')
                ],
                'period' => [
                    'start' => $data['pay_period_start'],
                    'end' => $data['pay_period_end'],
                    'work_days' => $data['work_days_payable']
                ],
                'salary' => [
                    'base' => number_format($data['base_salary_period'], 0, ',', '.'),
                    'allowances' => number_format($data['allowances_total'] ?? 0, 0, ',', '.'),
                    'bonuses' => number_format($data['bonuses_total'] ?? 0, 0, ',', '.'),
                    'deductions' => number_format($data['deductions_total'] ?? 0, 0, ',', '.'),
                    'gross' => number_format($data['gross_salary'], 0, ',', '.'),
                    'tax' => number_format($data['tax_deduction'] ?? 0, 0, ',', '.'),
                    'insurance' => number_format($data['insurance_deduction'] ?? 0, 0, ',', '.'),
                    'net' => number_format($data['net_salary'], 0, ',', '.')
                ]
            ]
        ];
    } catch (Exception $e) {
        logError("Lỗi validate dữ liệu: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Hàm tạo phiếu lương mới
function createPayroll($data) {
    global $conn;
    
    try {
        // Validate dữ liệu và lấy thông tin nhân viên
        $validateResult = validateEmployeeData($data);
        if (!$validateResult['success']) {
            return $validateResult;
        }
        
        $employee = $validateResult['data']['employee'];

        // Tạo mã tham chiếu thanh toán
        $paymentReference = 'PAY-' . date('Ymd') . '-' . $employee['code'];
        
        // Insert vào database
        $sql = "INSERT INTO payroll (
                    employee_id, pay_period_start, pay_period_end, work_days_payable,
                    base_salary_period, allowances_total, bonuses_total, deductions_total,
                    gross_salary, tax_deduction, insurance_deduction, net_salary,
                    currency, payment_method, payment_reference, status,
                    generated_at, generated_by_user_id, notes
                ) VALUES (
                    :employee_id, :pay_period_start, :pay_period_end, :work_days_payable,
                    :base_salary_period, :allowances_total, :bonuses_total, :deductions_total,
                    :gross_salary, :tax_deduction, :insurance_deduction, :net_salary,
                    :currency, :payment_method, :payment_reference, :status,
                    NOW(), :generated_by_user_id, :notes
                )";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            'employee_id' => $employee['id'],
            'pay_period_start' => $data['pay_period_start'],
            'pay_period_end' => $data['pay_period_end'],
            'work_days_payable' => $data['work_days_payable'],
            'base_salary_period' => $data['base_salary_period'],
            'allowances_total' => $data['allowances_total'] ?? 0,
            'bonuses_total' => $data['bonuses_total'] ?? 0,
            'deductions_total' => $data['deductions_total'] ?? 0,
            'gross_salary' => $data['gross_salary'],
            'tax_deduction' => $data['tax_deduction'] ?? 0,
            'insurance_deduction' => $data['insurance_deduction'] ?? 0,
            'net_salary' => $data['net_salary'],
            'currency' => $data['currency'] ?? 'VND',
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'payment_reference' => $paymentReference,
            'status' => 'pending',
            'generated_by_user_id' => $_SESSION['user_id'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
        
        if ($result) {
            $payrollId = $conn->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Tạo phiếu lương thành công',
                'data' => array_merge($validateResult['data'], [
                    'id' => $payrollId,
                    'payment' => [
                        'method' => $data['payment_method'] ?? 'bank_transfer',
                        'method_text' => ($data['payment_method'] ?? 'bank_transfer') === 'bank_transfer' ? 'Chuyển khoản' : 'Tiền mặt',
                        'reference' => $paymentReference
                    ],
                    'status' => 'pending',
                    'status_text' => 'Chờ duyệt',
                    'generated_at' => date('Y-m-d H:i:s')
                ])
            ];
        }
        
        throw new Exception("Không thể tạo phiếu lương");
        
    } catch (Exception $e) {
        logError("Lỗi tạo phiếu lương: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Xử lý request
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Method not allowed');
    }

    // Kiểm tra file upload
    if (!isset($_FILES['payroll_file']) || $_FILES['payroll_file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'Vui lòng chọn file để upload');
    }
    
    $file = $_FILES['payroll_file'];
    
    // Kiểm tra định dạng file
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExt !== 'txt') {
        jsonResponse(false, 'Chỉ chấp nhận file TXT');
    }
    
    // Đọc và parse file
    $payrollData = parsePayrollFile($file['tmp_name']);
    
    // Kiểm tra xem có phải là chế độ xem trước không
    if (isset($_POST['preview']) && $_POST['preview'] === 'true') {
        $result = validateEmployeeData($payrollData);
        jsonResponse($result['success'], $result['message'], $result['data'] ?? null);
    } else {
        // Tạo phiếu lương
        $result = createPayroll($payrollData);
        jsonResponse($result['success'], $result['message'], $result['data'] ?? null);
    }
    
} catch (Exception $e) {
    logError("Lỗi xử lý request: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
} catch (Error $e) {
    logError("Lỗi PHP: " . $e->getMessage());
    jsonResponse(false, "Có lỗi xảy ra khi xử lý yêu cầu");
} 