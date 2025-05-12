<?php
namespace App\Controllers;

class PayrollController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new \App\Config\Database();
        $this->conn = $this->db->getConnection();
    }

    // Lấy danh sách phiếu lương
    public function index() {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            $department = $_GET['department'] ?? '';
            $month = $_GET['month'] ?? '';
            $year = $_GET['year'] ?? '';

            $query = "SELECT 
                p.id,
                p.employee_id,
                e.code as employee_code,
                e.full_name as employee_name,
                d.name as department,
                e.position,
                p.period_start,
                p.period_end,
                p.work_days,
                p.basic_salary,
                p.allowances,
                p.bonuses,
                p.deductions,
                p.gross_salary,
                p.tax,
                p.insurance,
                p.net_salary,
                p.payment_date,
                p.payment_method,
                p.payment_reference,
                p.status,
                p.created_by,
                p.created_at,
                p.notes
            FROM payroll p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE 1=1";
            
            $params = [];
            
            if ($search) {
                $query .= " AND (e.code LIKE ? OR e.full_name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($department) {
                $query .= " AND d.id = ?";
                $params[] = $department;
            }
            
            if ($month && $year) {
                $query .= " AND MONTH(p.period_start) = ? AND YEAR(p.period_start) = ?";
                $params[] = $month;
                $params[] = $year;
            }

            $offset = ($page - 1) * $limit;
            $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $payrolls = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => $payrolls,
                'page' => $page,
                'limit' => $limit
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Lấy chi tiết một phiếu lương
    public function show($id) {
        try {
            error_log("PayrollController::show() called with ID: " . $id);
            
            // First verify the payroll exists
            $checkQuery = "SELECT COUNT(*) as count FROM payroll WHERE payroll_id = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $checkStmt->execute();
            $count = $checkStmt->fetch(\PDO::FETCH_ASSOC)['count'];
            
            if ($count == 0) {
                error_log("No payroll found with ID: " . $id);
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy phiếu lương với ID: ' . $id
                ];
            }

            // Get main payroll information with minimal joins first
            $query = "SELECT p.*, 
                     e.employee_code, e.name as employee_name,
                     d.name as department_name,
                     u.username as generated_by_username
              FROM payroll p
              LEFT JOIN employees e ON p.employee_id = e.id
              LEFT JOIN departments d ON e.department_id = d.id
              LEFT JOIN users u ON p.generated_by_user_id = u.user_id
              WHERE p.payroll_id = :id";
            
            error_log("Executing main query: " . $query);
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $payroll = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$payroll) {
                error_log("Error fetching payroll data");
                return [
                    'success' => false,
                    'message' => 'Lỗi khi lấy thông tin phiếu lương: Không thể tìm thấy dữ liệu'
                ];
            }

            error_log("Found payroll record: " . json_encode($payroll));

            // Initialize arrays for related data
            $payroll['allowances'] = [];
            $payroll['bonuses'] = [];
            $payroll['deductions'] = [];
            $payroll['approvals'] = [];
            $payroll['payment'] = null;

            try {
                // Get allowances
                $allowancesQuery = "SELECT pa.*, a.name as allowance_name, a.description
                                  FROM payroll_allowances pa
                                  LEFT JOIN allowances a ON pa.allowance_id = a.allowance_id
                                  WHERE pa.payroll_id = :id";
                error_log("Executing allowances query: " . $allowancesQuery);
                $allowancesStmt = $this->conn->prepare($allowancesQuery);
                $allowancesStmt->bindValue(':id', $id, \PDO::PARAM_INT);
                $allowancesStmt->execute();
                $payroll['allowances'] = $allowancesStmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("Found " . count($payroll['allowances']) . " allowances");

                // Get bonuses
                $bonusesQuery = "SELECT pb.*, b.bonus_type, b.reason as bonus_reason
                               FROM payroll_bonuses pb
                               LEFT JOIN bonuses b ON pb.bonus_id = b.bonus_id
                               WHERE pb.payroll_id = :id";
                error_log("Executing bonuses query: " . $bonusesQuery);
                $bonusesStmt = $this->conn->prepare($bonusesQuery);
                $bonusesStmt->bindValue(':id', $id, \PDO::PARAM_INT);
                $bonusesStmt->execute();
                $payroll['bonuses'] = $bonusesStmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("Found " . count($payroll['bonuses']) . " bonuses");

                // Get deductions
                $deductionsQuery = "SELECT pd.*, d.name as deduction_name, d.description
                                  FROM payroll_deductions pd
                                  LEFT JOIN deductions d ON pd.deduction_id = d.deduction_id
                                  WHERE pd.payroll_id = :id";
                error_log("Executing deductions query: " . $deductionsQuery);
                $deductionsStmt = $this->conn->prepare($deductionsQuery);
                $deductionsStmt->bindValue(':id', $id, \PDO::PARAM_INT);
                $deductionsStmt->execute();
                $payroll['deductions'] = $deductionsStmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("Found " . count($payroll['deductions']) . " deductions");

                // Get approval history
                $approvalsQuery = "SELECT pa.*, u.username as approver_name
                                 FROM payroll_approvals pa
                                 LEFT JOIN users u ON pa.approver_id = u.user_id
                                 WHERE pa.payroll_id = :id
                                 ORDER BY pa.approval_level";
                error_log("Executing approvals query: " . $approvalsQuery);
                $approvalsStmt = $this->conn->prepare($approvalsQuery);
                $approvalsStmt->bindValue(':id', $id, \PDO::PARAM_INT);
                $approvalsStmt->execute();
                $payroll['approvals'] = $approvalsStmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("Found " . count($payroll['approvals']) . " approvals");

                // Get payment information
                $paymentQuery = "SELECT * FROM payroll_payments WHERE payroll_id = :id";
                error_log("Executing payment query: " . $paymentQuery);
                $paymentStmt = $this->conn->prepare($paymentQuery);
                $paymentStmt->bindValue(':id', $id, \PDO::PARAM_INT);
                $paymentStmt->execute();
                $payroll['payment'] = $paymentStmt->fetch(\PDO::FETCH_ASSOC);
                error_log("Payment info: " . ($payroll['payment'] ? "found" : "not found"));

            } catch (\PDOException $e) {
                error_log("Error fetching related data: " . $e->getMessage());
                error_log("SQL State: " . $e->getCode());
                error_log("Error Info: " . print_r($e->errorInfo, true));
                // Continue with the main payroll data even if related data fails
            }

            // Format the response
            $formattedPayroll = [
                'id' => $payroll['payroll_id'],
                'employee' => [
                    'id' => $payroll['employee_id'],
                    'code' => $payroll['employee_code'],
                    'name' => $payroll['employee_name'],
                    'department' => $payroll['department_name']
                ],
                'period' => [
                    'start' => $payroll['pay_period_start'],
                    'end' => $payroll['pay_period_end'],
                    'work_days' => $payroll['work_days_payable']
                ],
                'salary' => [
                    'base' => number_format($payroll['base_salary_period'], 0, ',', '.'),
                    'allowances' => number_format($payroll['allowances_total'], 0, ',', '.'),
                    'bonuses' => number_format($payroll['bonuses_total'], 0, ',', '.'),
                    'deductions' => number_format($payroll['deductions_total'], 0, ',', '.'),
                    'gross' => number_format($payroll['gross_salary'], 0, ',', '.'),
                    'tax' => number_format($payroll['tax_deduction'], 0, ',', '.'),
                    'insurance' => number_format($payroll['insurance_deduction'], 0, ',', '.'),
                    'net' => number_format($payroll['net_salary'], 0, ',', '.')
                ],
                'payment' => [
                    'date' => $payroll['payment_date'],
                    'method' => $payroll['payment_method'],
                    'reference' => $payroll['payment_reference']
                ],
                'status' => [
                    'code' => $payroll['status'],
                    'text' => $this->getStatusText($payroll['status'])
                ],
                'created_by' => [
                    'username' => $payroll['generated_by_username']
                ],
                'created_at' => $payroll['generated_at'],
                'notes' => $payroll['notes'],
                'allowances_details' => $payroll['allowances'],
                'bonuses_details' => $payroll['bonuses'],
                'deductions_details' => $payroll['deductions'],
                'approvals' => $payroll['approvals'],
                'payment_details' => $payroll['payment']
            ];

            error_log("Successfully formatted payroll data");
            return [
                'success' => true,
                'data' => $formattedPayroll
            ];
        } catch (\Exception $e) {
            error_log("Error in PayrollController::show(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Lỗi khi xem chi tiết phiếu lương: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }

    private function getStatusText($status) {
        $statusMap = [
            'pending' => 'Chờ duyệt',
            'calculated' => 'Đã tính lương',
            'approved' => 'Đã duyệt',
            'paid' => 'Đã thanh toán',
            'rejected' => 'Từ chối'
        ];
        return $statusMap[$status] ?? $status;
    }

    // Tạo phiếu lương mới
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$this->validatePayrollData($data)) {
                return [
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ'
                ];
            }

            $this->conn->beginTransaction();

            $query = "INSERT INTO payroll (employee_id, period_start, period_end, basic_salary, 
                                     allowances, bonuses, deductions, net_salary, status, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['employee_id'],
                $data['period_start'],
                $data['period_end'],
                $data['basic_salary'],
                $data['allowances'] ?? 0,
                $data['bonuses'] ?? 0,
                $data['deductions'] ?? 0,
                $data['net_salary'] ?? $data['basic_salary'],
                getCurrentUserId()
            ]);

            $payrollId = $this->conn->lastInsertId();

            if (isset($data['details']) && is_array($data['details'])) {
                $this->savePayrollDetails($payrollId, $data['details']);
            }

            $this->createApprovalSteps($payrollId);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Tạo phiếu lương thành công',
                'data' => ['id' => $payrollId]
            ];
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Cập nhật phiếu lương
    public function update($id) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$this->validatePayrollData($data)) {
                return [
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ'
                ];
            }

            $this->conn->beginTransaction();

            $query = "UPDATE payroll SET 
                     period_start = ?,
                     period_end = ?,
                     basic_salary = ?,
                     allowances = ?,
                     bonuses = ?,
                     deductions = ?,
                     net_salary = ?
                     WHERE id = ? AND status = 'draft'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['period_start'],
                $data['period_end'],
                $data['basic_salary'],
                $data['allowances'] ?? 0,
                $data['bonuses'] ?? 0,
                $data['deductions'] ?? 0,
                $data['net_salary'] ?? $data['basic_salary'],
                $id
            ]);

            if (isset($data['details']) && is_array($data['details'])) {
                $this->updatePayrollDetails($id, $data['details']);
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Cập nhật phiếu lương thành công'
            ];
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Xóa phiếu lương
    public function destroy($id) {
        try {
            $this->conn->beginTransaction();

            // Kiểm tra trạng thái
            $stmt = $this->conn->prepare("SELECT status FROM payroll WHERE id = ?");
            $stmt->execute([$id]);
            $payroll = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$payroll) {
                throw new \Exception('Không tìm thấy phiếu lương');
            }

            if ($payroll['status'] !== 'draft') {
                throw new \Exception('Chỉ có thể xóa phiếu lương ở trạng thái nháp');
            }

            // Xóa chi tiết
            $stmt = $this->conn->prepare("DELETE FROM payroll_details WHERE payroll_id = ?");
            $stmt->execute([$id]);

            // Xóa phê duyệt
            $stmt = $this->conn->prepare("DELETE FROM payroll_approvals WHERE payroll_id = ?");
            $stmt->execute([$id]);

            // Xóa phiếu lương
            $stmt = $this->conn->prepare("DELETE FROM payroll WHERE id = ?");
            $stmt->execute([$id]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Xóa phiếu lương thành công'
            ];
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Các phương thức phụ trợ
    private function validatePayrollData($data) {
        $required = ['employee_id', 'period_start', 'period_end', 'basic_salary'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        return true;
    }

    private function savePayrollDetails($payrollId, $details) {
        $query = "INSERT INTO payroll_details (payroll_id, component_id, amount, type)
                 VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        foreach ($details as $detail) {
            $stmt->execute([
                $payrollId,
                $detail['component_id'],
                $detail['amount'],
                $detail['type']
            ]);
        }
    }

    private function updatePayrollDetails($payrollId, $details) {
        // Xóa chi tiết cũ
        $stmt = $this->conn->prepare("DELETE FROM payroll_details WHERE payroll_id = ?");
        $stmt->execute([$payrollId]);

        // Thêm chi tiết mới
        $this->savePayrollDetails($payrollId, $details);
    }

    private function createApprovalSteps($payrollId) {
        $approvalLevels = $this->getApprovalLevels();
        $query = "INSERT INTO payroll_approvals (payroll_id, approval_level, approver_id, status)
                 VALUES (?, ?, ?, 'pending')";
        $stmt = $this->conn->prepare($query);

        foreach ($approvalLevels as $level) {
            $stmt->execute([
                $payrollId,
                $level['level'],
                $level['approver_id']
            ]);
        }
    }

    private function getApprovalLevels() {
        // Implement logic to get approval levels from configuration
        return [
            ['level' => 1, 'approver_id' => 1], // Example
            ['level' => 2, 'approver_id' => 2]  // Example
        ];
    }

    public function createPayroll($data) {
        try {
            // Validate required fields
            $requiredFields = [
                'employee_id', 'pay_period_start', 'pay_period_end',
                'work_days_payable', 'base_salary_period', 'allowances_total',
                'bonuses_total', 'deductions_total', 'gross_salary',
                'tax_deduction', 'insurance_deduction', 'net_salary'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Trường {$field} là bắt buộc");
                }
            }

            // Check if payroll already exists for this employee and period
            $checkSql = "SELECT 1 FROM payroll 
                        WHERE employee_id = :employee_id 
                        AND pay_period_start = :start_date 
                        AND pay_period_end = :end_date";
            
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([
                'employee_id' => $data['employee_id'],
                'start_date' => $data['pay_period_start'],
                'end_date' => $data['pay_period_end']
            ]);

            if ($checkStmt->fetch()) {
                throw new Exception("Đã tồn tại phiếu lương cho nhân viên này trong kỳ lương này");
            }

            // Insert new payroll
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

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                'employee_id' => $data['employee_id'],
                'pay_period_start' => $data['pay_period_start'],
                'pay_period_end' => $data['pay_period_end'],
                'work_days_payable' => $data['work_days_payable'],
                'base_salary_period' => $data['base_salary_period'],
                'allowances_total' => $data['allowances_total'],
                'bonuses_total' => $data['bonuses_total'],
                'deductions_total' => $data['deductions_total'],
                'gross_salary' => $data['gross_salary'],
                'tax_deduction' => $data['tax_deduction'],
                'insurance_deduction' => $data['insurance_deduction'],
                'net_salary' => $data['net_salary'],
                'currency' => $data['currency'] ?? 'VND',
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? null
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Tạo phiếu lương thành công',
                    'payroll_id' => $this->conn->lastInsertId()
                ];
            }

            throw new Exception("Không thể tạo phiếu lương");

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getPayrollDetails($payrollId) {
        try {
            $sql = "SELECT p.*, e.name as employee_name 
                    FROM payroll p 
                    JOIN employees e ON p.employee_id = e.id 
                    WHERE p.payroll_id = :payroll_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['payroll_id' => $payrollId]);
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payroll) {
                throw new Exception("Không tìm thấy phiếu lương");
            }

            return [
                'success' => true,
                'data' => $payroll
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 