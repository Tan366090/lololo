<?php
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../utils/security.php';

header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Check user permissions
    if (!hasPermission('view_bank_config')) {
        throw new Exception('Unauthorized access');
    }

    // Get bank configuration
    $stmt = $pdo->prepare("
        SELECT 
            id,
            bank_name,
            api_url,
            api_key,
            merchant_id,
            account_number,
            account_name,
            branch_code,
            is_active,
            last_sync,
            created_at,
            updated_at
        FROM bank_integration
        WHERE is_active = 1
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        throw new Exception('No active bank configuration found');
    }

    // Get bank account mappings
    $stmt = $pdo->prepare("
        SELECT 
            e.id as employee_id,
            e.employee_code,
            e.name as employee_name,
            bm.bank_account_number,
            bm.bank_account_name,
            bm.bank_name,
            bm.branch_name
        FROM bank_account_mapping bm
        JOIN employees e ON bm.employee_id = e.id
        WHERE bm.is_active = 1
        ORDER BY e.employee_code
    ");
    $stmt->execute();
    $accountMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payment templates
    $stmt = $pdo->prepare("
        SELECT 
            id,
            template_name,
            template_code,
            description,
            is_active
        FROM bank_payment_templates
        WHERE is_active = 1
        ORDER BY template_name
    ");
    $stmt->execute();
    $paymentTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get transfer history
    $stmt = $pdo->prepare("
        SELECT 
            id,
            transfer_type,
            status,
            total_amount,
            total_records,
            error_message,
            started_at,
            completed_at
        FROM bank_transfer_history
        WHERE integration_id = :integration_id
        ORDER BY started_at DESC
        LIMIT 10
    ");
    $stmt->execute(['integration_id' => $config['id']]);
    $transferHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response data
    $response = [
        'config' => [
            'bank_name' => $config['bank_name'],
            'api_url' => $config['api_url'],
            'merchant_id' => $config['merchant_id'],
            'account_number' => $config['account_number'],
            'account_name' => $config['account_name'],
            'branch_code' => $config['branch_code'],
            'is_active' => $config['is_active'],
            'last_sync' => $config['last_sync']
        ],
        'account_mappings' => $accountMappings,
        'payment_templates' => $paymentTemplates,
        'transfer_history' => $transferHistory
    ];

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Get bank config error: " . $e->getMessage());
    
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