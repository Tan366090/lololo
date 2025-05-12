<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Get and validate input
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['employee_id']) || !is_numeric($data['employee_id'])) {
        throw new Exception('Invalid employee ID');
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Check if draft already exists
        $stmt = $pdo->prepare("
            SELECT id 
            FROM payrolls 
            WHERE employee_id = :employee_id 
            AND status = 'draft'
        ");
        $stmt->execute(['employee_id' => $data['employee_id']]);
        $existingDraft = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingDraft) {
            // Update existing draft
            $stmt = $pdo->prepare("
                UPDATE payrolls 
                SET 
                    base_salary = :base_salary,
                    allowances = :allowances,
                    bonuses = :bonuses,
                    deductions = :deductions,
                    net_salary = :net_salary,
                    pay_period_start = :pay_period_start,
                    pay_period_end = :pay_period_end,
                    notes = :notes,
                    updated_at = NOW(),
                    updated_by = :user_id
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $existingDraft['id'],
                'base_salary' => $data['base_salary'],
                'allowances' => $data['allowances'],
                'bonuses' => $data['bonuses'],
                'deductions' => $data['deductions'],
                'net_salary' => $data['net_salary'],
                'pay_period_start' => $data['pay_period_start'],
                'pay_period_end' => $data['pay_period_end'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            $payrollId = $existingDraft['id'];
        } else {
            // Create new draft
            $stmt = $pdo->prepare("
                INSERT INTO payrolls (
                    employee_id,
                    base_salary,
                    allowances,
                    bonuses,
                    deductions,
                    net_salary,
                    pay_period_start,
                    pay_period_end,
                    status,
                    notes,
                    created_by,
                    created_at
                ) VALUES (
                    :employee_id,
                    :base_salary,
                    :allowances,
                    :bonuses,
                    :deductions,
                    :net_salary,
                    :pay_period_start,
                    :pay_period_end,
                    'draft',
                    :notes,
                    :user_id,
                    NOW()
                )
            ");
            $stmt->execute([
                'employee_id' => $data['employee_id'],
                'base_salary' => $data['base_salary'],
                'allowances' => $data['allowances'],
                'bonuses' => $data['bonuses'],
                'deductions' => $data['deductions'],
                'net_salary' => $data['net_salary'],
                'pay_period_start' => $data['pay_period_start'],
                'pay_period_end' => $data['pay_period_end'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            $payrollId = $pdo->lastInsertId();
        }

        // Save allowances if provided
        if (isset($data['allowances_breakdown']) && is_array($data['allowances_breakdown'])) {
            $stmt = $pdo->prepare("
                DELETE FROM payroll_allowances 
                WHERE payroll_id = :payroll_id
            ");
            $stmt->execute(['payroll_id' => $payrollId]);

            $stmt = $pdo->prepare("
                INSERT INTO payroll_allowances (
                    payroll_id,
                    type,
                    amount,
                    description
                ) VALUES (
                    :payroll_id,
                    :type,
                    :amount,
                    :description
                )
            ");

            foreach ($data['allowances_breakdown'] as $allowance) {
                $stmt->execute([
                    'payroll_id' => $payrollId,
                    'type' => $allowance['type'],
                    'amount' => $allowance['amount'],
                    'description' => $allowance['description']
                ]);
            }
        }

        // Save deductions if provided
        if (isset($data['deductions_breakdown']) && is_array($data['deductions_breakdown'])) {
            $stmt = $pdo->prepare("
                DELETE FROM payroll_deductions 
                WHERE payroll_id = :payroll_id
            ");
            $stmt->execute(['payroll_id' => $payrollId]);

            $stmt = $pdo->prepare("
                INSERT INTO payroll_deductions (
                    payroll_id,
                    type,
                    amount,
                    description
                ) VALUES (
                    :payroll_id,
                    :type,
                    :amount,
                    :description
                )
            ");

            foreach ($data['deductions_breakdown'] as $deduction) {
                $stmt->execute([
                    'payroll_id' => $payrollId,
                    'type' => $deduction['type'],
                    'amount' => $deduction['amount'],
                    'description' => $deduction['description']
                ]);
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Draft saved successfully',
            'payroll_id' => $payrollId
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log error
    error_log("Save draft error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 