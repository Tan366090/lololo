<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    // Validate file type
    $allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel' // .xls
    ];
    
    if (!in_array($_FILES['file']['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Please upload an Excel file (.xlsx or .xls)');
    }

    // Load the Excel file
    $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Validate header row
    $requiredHeaders = [
        'Employee Code',
        'Base Salary',
        'Allowances',
        'Bonuses',
        'Deductions',
        'Pay Period Start',
        'Pay Period End',
        'Notes'
    ];

    $headers = array_map('trim', $rows[0]);
    $missingHeaders = array_diff($requiredHeaders, $headers);
    
    if (!empty($missingHeaders)) {
        throw new Exception('Missing required columns: ' . implode(', ', $missingHeaders));
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        $successCount = 0;
        $errorRows = [];

        // Process each row
        for ($i = 1; $i < count($rows); $i++) {
            $row = array_combine($headers, $rows[$i]);
            
            try {
                // Get employee ID from code
                $stmt = $pdo->prepare("
                    SELECT id 
                    FROM employees 
                    WHERE employee_code = :code
                ");
                $stmt->execute(['code' => $row['Employee Code']]);
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$employee) {
                    throw new Exception("Employee not found: {$row['Employee Code']}");
                }

                // Calculate net salary
                $netSalary = $row['Base Salary'] + $row['Allowances'] + $row['Bonuses'] - $row['Deductions'];

                // Insert payroll record
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
                    'employee_id' => $employee['id'],
                    'base_salary' => $row['Base Salary'],
                    'allowances' => $row['Allowances'],
                    'bonuses' => $row['Bonuses'],
                    'deductions' => $row['Deductions'],
                    'net_salary' => $netSalary,
                    'pay_period_start' => $row['Pay Period Start'],
                    'pay_period_end' => $row['Pay Period End'],
                    'notes' => $row['Notes'] ?? null,
                    'user_id' => $_SESSION['user_id'] ?? null
                ]);

                $successCount++;

            } catch (Exception $e) {
                $errorRows[] = [
                    'row' => $i + 1,
                    'error' => $e->getMessage()
                ];
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Import completed. Successfully imported $successCount records.",
            'errors' => $errorRows
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log error
    error_log("Import template error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 