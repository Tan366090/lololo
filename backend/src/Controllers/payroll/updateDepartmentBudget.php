<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Get and validate input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['departmentId']) || !is_numeric($data['departmentId'])) {
        throw new Exception('Invalid department ID');
    }

    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] < 0) {
        throw new Exception('Invalid amount');
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Check if department exists
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM departments 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $data['departmentId']]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$department) {
            throw new Exception('Department not found');
        }

        // Get current budget
        $stmt = $pdo->prepare("
            SELECT 
                id,
                amount,
                fiscal_year,
                fiscal_period
            FROM department_budgets
            WHERE department_id = :department_id
            AND fiscal_year = :fiscal_year
            AND fiscal_period = :fiscal_period
        ");

        $currentYear = date('Y');
        $currentMonth = date('m');
        $fiscalPeriod = ceil($currentMonth / 3); // Convert month to quarter

        $stmt->execute([
            'department_id' => $data['departmentId'],
            'fiscal_year' => $currentYear,
            'fiscal_period' => $fiscalPeriod
        ]);

        $currentBudget = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($currentBudget) {
            // Update existing budget
            $stmt = $pdo->prepare("
                UPDATE department_budgets
                SET 
                    amount = :amount,
                    updated_at = NOW(),
                    updated_by = :user_id
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $currentBudget['id'],
                'amount' => $data['amount'],
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
        } else {
            // Create new budget
            $stmt = $pdo->prepare("
                INSERT INTO department_budgets (
                    department_id,
                    amount,
                    fiscal_year,
                    fiscal_period,
                    created_by,
                    created_at
                ) VALUES (
                    :department_id,
                    :amount,
                    :fiscal_year,
                    :fiscal_period,
                    :user_id,
                    NOW()
                )
            ");

            $stmt->execute([
                'department_id' => $data['departmentId'],
                'amount' => $data['amount'],
                'fiscal_year' => $currentYear,
                'fiscal_period' => $fiscalPeriod,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
        }

        // Log the budget update
        $stmt = $pdo->prepare("
            INSERT INTO budget_history (
                department_id,
                old_amount,
                new_amount,
                fiscal_year,
                fiscal_period,
                changed_by,
                changed_at
            ) VALUES (
                :department_id,
                :old_amount,
                :new_amount,
                :fiscal_year,
                :fiscal_period,
                :user_id,
                NOW()
            )
        ");

        $stmt->execute([
            'department_id' => $data['departmentId'],
            'old_amount' => $currentBudget['amount'] ?? 0,
            'new_amount' => $data['amount'],
            'fiscal_year' => $currentYear,
            'fiscal_period' => $fiscalPeriod,
            'user_id' => $_SESSION['user_id'] ?? null
        ]);

        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => "Budget updated successfully for {$department['name']}",
            'data' => [
                'department_id' => $data['departmentId'],
                'amount' => $data['amount'],
                'fiscal_year' => $currentYear,
                'fiscal_period' => $fiscalPeriod
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log error
    error_log("Update department budget error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to check user permissions
function hasPermission($permission) {
    // Implement your permission checking logic here
    // This is a placeholder implementation
    return isset($_SESSION['user_permissions']) && 
           in_array($permission, $_SESSION['user_permissions']);
} 