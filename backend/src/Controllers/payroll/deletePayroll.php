<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if payroll exists and is not already processed
    $stmt = $pdo->prepare("
        SELECT status 
        FROM payrolls 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $data['id']]);
    $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payroll) {
        throw new Exception('Payroll not found');
    }

    if ($payroll['status'] === 'processed') {
        throw new Exception('Cannot delete processed payroll');
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Delete payroll
        $stmt = $pdo->prepare("
            DELETE FROM payrolls 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $data['id']]);

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payroll deleted successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 