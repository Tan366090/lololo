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
    if (!hasPermission('view_accounting_config')) {
        throw new Exception('Unauthorized access');
    }

    // Get accounting configuration
    $stmt = $pdo->prepare("
        SELECT 
            id,
            system_name,
            api_url,
            api_key,
            company_code,
            department_mapping,
            account_mapping,
            is_active,
            last_sync,
            created_at,
            updated_at
        FROM accounting_integration
        WHERE is_active = 1
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        throw new Exception('No active accounting configuration found');
    }

    // Get department mappings
    $stmt = $pdo->prepare("
        SELECT 
            d.id as department_id,
            d.name as department_name,
            am.account_code,
            am.account_name
        FROM department_account_mapping am
        JOIN departments d ON am.department_id = d.id
        WHERE am.is_active = 1
        ORDER BY d.name
    ");
    $stmt->execute();
    $departmentMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get account mappings
    $stmt = $pdo->prepare("
        SELECT 
            type,
            account_code,
            account_name,
            description
        FROM account_mapping
        WHERE is_active = 1
        ORDER BY type
    ");
    $stmt->execute();
    $accountMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sync history
    $stmt = $pdo->prepare("
        SELECT 
            id,
            sync_type,
            status,
            records_processed,
            error_message,
            started_at,
            completed_at
        FROM accounting_sync_history
        WHERE integration_id = :integration_id
        ORDER BY started_at DESC
        LIMIT 10
    ");
    $stmt->execute(['integration_id' => $config['id']]);
    $syncHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response data
    $response = [
        'config' => [
            'system_name' => $config['system_name'],
            'api_url' => $config['api_url'],
            'company_code' => $config['company_code'],
            'is_active' => $config['is_active'],
            'last_sync' => $config['last_sync']
        ],
        'department_mappings' => $departmentMappings,
        'account_mappings' => $accountMappings,
        'sync_history' => $syncHistory
    ];

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Get accounting config error: " . $e->getMessage());
    
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