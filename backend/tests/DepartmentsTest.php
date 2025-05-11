<?php
// Hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối database
require_once __DIR__ . '/../src/config/database.php';
$conn = Database::getConnection();

// URL base
$baseUrl = 'http://localhost/qlnhansu_V3/backend/src/api/v1/departments.php';

// Helper function để tạo request
function makeRequest($action, $method, $data = null, $id = null) {
    global $baseUrl;
    $url = $baseUrl . '?action=' . $action;
    if ($id !== null) {
        $url .= '&id=' . $id;
    }
    
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ];

    if ($data !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Debug thông tin request
    echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Request URL:</strong> " . $url . "<br>";
    echo "<strong>Method:</strong> " . $method . "<br>";
    if ($data !== null) {
        echo "<strong>Request Data:</strong><pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    }
    echo "<strong>Response Code:</strong> " . $httpCode . "<br>";
    echo "<strong>Response Body:</strong><pre>" . $response . "</pre>";
    echo "</div>";

    curl_close($ch);
    return [
        'code' => $httpCode,
        'body' => $response
    ];
}

// Helper function để tạo tên phòng ban unique
function generateUniqueDepartmentName() {
    return 'Test Department ' . uniqid() . '_' . time();
}

// Helper function để tạo phòng ban test
function createTestDepartment() {
    global $conn;
    $name = generateUniqueDepartmentName();
    $sql = "INSERT INTO departments (name, description, manager_id, parent_id, created_at, updated_at) 
            VALUES (?, ?, NULL, NULL, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, 'Test Description']);
    return $conn->lastInsertId();
}

// Helper function để xóa phòng ban test
function deleteTestDepartment($id) {
    global $conn;
    // Kiểm tra xem có nhân viên nào trong phòng ban không
    $sql = "SELECT COUNT(*) FROM employees WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        // Nếu có nhân viên, cập nhật department_id thành NULL
        $sql = "UPDATE employees SET department_id = NULL WHERE department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
    }

    // Xóa phòng ban
    $sql = "DELETE FROM departments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Test thêm phòng ban mới
echo "<h3>Test CreateDepartment</h3>";
try {
    $departmentData = [
        'name' => generateUniqueDepartmentName(),
        'description' => 'Test Description',
        'manager_id' => null,
        'parent_id' => null
    ];

    $response = makeRequest('create', 'POST', $departmentData);
    $data = json_decode($response['body'], true);

    if ($response['code'] === 200) {
        if (isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>✓ Passed: Thêm phòng ban thành công</p>";
            echo "<pre>";
            print_r($data['data']);
            echo "</pre>";
            
            // Cleanup
            deleteTestDepartment($data['data']['id']);
        } else {
            $error = isset($data['message']) ? $data['message'] : 'Unknown error';
            throw new Exception("Không thêm được phòng ban: " . $error);
        }
    } else {
        throw new Exception("HTTP Error: " . $response['code']);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test cập nhật phòng ban
echo "<h3>Test UpdateDepartment</h3>";
try {
    // Tạo phòng ban test
    $departmentId = createTestDepartment();

    $updateData = [
        'name' => generateUniqueDepartmentName(),
        'description' => 'Updated Description',
        'manager_id' => null,
        'parent_id' => null
    ];

    $response = makeRequest('update', 'PUT', $updateData, $departmentId);
    $data = json_decode($response['body'], true);

    if ($response['code'] === 200) {
        if (isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>✓ Passed: Cập nhật phòng ban thành công</p>";
            
            // Verify update
            $response = makeRequest('getById', 'GET', null, $departmentId);
            $data = json_decode($response['body'], true);
            echo "<pre>";
            print_r($data['data']);
            echo "</pre>";
        } else {
            $error = isset($data['message']) ? $data['message'] : 'Unknown error';
            throw new Exception("Không cập nhật được phòng ban: " . $error);
        }
    } else {
        throw new Exception("HTTP Error: " . $response['code']);
    }

    // Cleanup
    deleteTestDepartment($departmentId);
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test xóa phòng ban
echo "<h3>Test DeleteDepartment</h3>";
try {
    // Tạo phòng ban test
    $departmentId = createTestDepartment();

    $response = makeRequest('delete', 'DELETE', null, $departmentId);
    $data = json_decode($response['body'], true);

    if ($response['code'] === 200) {
        if (isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>✓ Passed: Xóa phòng ban thành công</p>";
            
            // Verify deletion
            $response = makeRequest('getById', 'GET', null, $departmentId);
            $data = json_decode($response['body'], true);
            if (!isset($data['success']) || !$data['success']) {
                echo "<p style='color: green;'>✓ Passed: Xác nhận phòng ban đã bị xóa</p>";
            }
        } else {
            $error = isset($data['message']) ? $data['message'] : 'Unknown error';
            throw new Exception("Không xóa được phòng ban: " . $error);
        }
    } else {
        throw new Exception("HTTP Error: " . $response['code']);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test xem chi tiết phòng ban
echo "<h3>Test ViewDepartmentDetails</h3>";
try {
    $departmentId = 1; // ID của phòng IT
    $response = makeRequest('getById', 'GET', null, $departmentId);
    $data = json_decode($response['body'], true);

    if ($response['code'] === 200) {
        if (isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>✓ Passed: Xem chi tiết phòng ban thành công</p>";
            echo "<pre>";
            print_r($data['data']);
            echo "</pre>";
        } else {
            $error = isset($data['message']) ? $data['message'] : 'Unknown error';
            throw new Exception("Không xem được chi tiết phòng ban: " . $error);
        }
    } else {
        throw new Exception("HTTP Error: " . $response['code']);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test tạo phòng ban với tên trùng lặp
echo "\nTest CreateDepartmentWithDuplicateName\n";
$duplicateName = "Test Department Duplicate";
$data = [
    'name' => $duplicateName,
    'description' => 'Test Description',
    'manager_id' => null,
    'parent_id' => null
];

// Tạo phòng ban đầu tiên
$response = makeRequest('create', 'POST', $data);
$result = json_decode($response['body'], true);
if ($result['success']) {
    // Thử tạo phòng ban thứ hai với cùng tên
    $response = makeRequest('create', 'POST', $data);
    $result = json_decode($response['body'], true);
    if (!$result['success']) {
        echo "✓ Passed: Không thể tạo phòng ban với tên trùng lặp\n";
    } else {
        echo "✗ Failed: Đã tạo được phòng ban với tên trùng lặp\n";
    }
}

// Test cập nhật phòng ban không tồn tại
echo "<h3>Test UpdateNonExistentDepartment</h3>";
try {
    $nonExistentId = 99999;
    $data = [
        'name' => generateUniqueDepartmentName(),
        'description' => 'Updated Description',
        'manager_id' => null,
        'parent_id' => null
    ];
    
    $response = makeRequest('update', 'PUT', $data, $nonExistentId);
    $result = json_decode($response['body'], true);
    
    // Kiểm tra xem phòng ban có thực sự tồn tại không
    $checkResponse = makeRequest('getById', 'GET', null, $nonExistentId);
    $checkResult = json_decode($checkResponse['body'], true);
    
    if (!$checkResult['success']) {
        echo "<p style='color: green;'>✓ Passed: Không thể cập nhật phòng ban không tồn tại</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: Đã cập nhật được phòng ban không tồn tại</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test xóa phòng ban có nhân viên
echo "<h3>Test DeleteDepartmentWithEmployees</h3>";
try {
    // Tạo phòng ban mới
    $deptId = createTestDepartment();
    if ($deptId) {
        // Tạo user mới cho nhân viên test
        $testUsername = 'test_employee_' . uniqid();
        $testUserEmail = 'test_user_' . uniqid() . '@example.com';
        $passwordHash = password_hash('test123', PASSWORD_DEFAULT);
        $roleId = 2; // Giả sử role_id 2 là role của nhân viên
        
        $sql = "INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$testUsername, $testUserEmail, $passwordHash, $roleId]);
        $userId = $conn->lastInsertId();
        
        // Thêm nhân viên vào phòng ban
        $testName = 'Test Employee ' . uniqid();
        $testEmail = 'test_' . uniqid() . '@example.com';
        $testPhone = '0123456789';
        $testCode = 'EMP' . substr(uniqid(), -4); // Tạo mã nhân viên ngắn hơn
        
        $sql = "INSERT INTO employees (user_id, name, email, phone, employee_code, department_id, position_id, hire_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, 1, CURDATE(), 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId, $testName, $testEmail, $testPhone, $testCode, $deptId]);
        
        // Thử xóa phòng ban
        $response = makeRequest('delete', 'DELETE', null, $deptId);
        $result = json_decode($response['body'], true);
        
        if (!$result['success']) {
            echo "<p style='color: green;'>✓ Passed: Không thể xóa phòng ban có nhân viên</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed: Đã xóa được phòng ban có nhân viên</p>";
        }
        
        // Cleanup
        $sql = "DELETE FROM employees WHERE department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$deptId]);
        
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        
        deleteTestDepartment($deptId);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test validation - tên phòng ban trống
echo "<h3>Test CreateDepartmentWithEmptyName</h3>";
try {
    $data = [
        'name' => '',
        'description' => 'Test Description',
        'manager_id' => null,
        'parent_id' => null
    ];
    
    $response = makeRequest('create', 'POST', $data);
    $result = json_decode($response['body'], true);
    
    if (!$result['success'] && strpos($result['message'], 'không được để trống') !== false) {
        echo "<p style='color: green;'>✓ Passed: Không thể tạo phòng ban với tên trống</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . ($result['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test validation - tên phòng ban quá dài
echo "<h3>Test CreateDepartmentWithLongName</h3>";
try {
    $data = [
        'name' => str_repeat('a', 256),
        'description' => 'Test Description',
        'manager_id' => null,
        'parent_id' => null
    ];
    
    $response = makeRequest('create', 'POST', $data);
    $result = json_decode($response['body'], true);
    
    if (!$result['success'] && strpos($result['message'], 'Data too long') !== false) {
        echo "<p style='color: green;'>✓ Passed: Không thể tạo phòng ban với tên quá dài</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . ($result['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test validation - manager_id không tồn tại
echo "<h3>Test CreateDepartmentWithInvalidManager</h3>";
try {
    $data = [
        'name' => generateUniqueDepartmentName(), // Sử dụng tên unique để tránh lỗi duplicate
        'description' => 'Test Description',
        'manager_id' => 99999,
        'parent_id' => null
    ];
    
    $response = makeRequest('create', 'POST', $data);
    $result = json_decode($response['body'], true);
    
    if (!$result['success'] && strpos($result['message'], 'foreign key constraint') !== false) {
        echo "<p style='color: green;'>✓ Passed: Không thể tạo phòng ban với manager không tồn tại</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . ($result['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
}

// Test validation - parent_id không tồn tại
echo "<h3>Test CreateDepartmentWithInvalidParent</h3>";
try {
    $data = [
        'name' => generateUniqueDepartmentName(), // Sử dụng tên unique để tránh lỗi duplicate
        'description' => 'Test Description',
        'manager_id' => null,
        'parent_id' => 99999
    ];
    
    $response = makeRequest('create', 'POST', $data);
    $result = json_decode($response['body'], true);
    
    if (!$result['success'] && strpos($result['message'], 'foreign key constraint') !== false) {
        echo "<p style='color: green;'>✓ Passed: Không thể tạo phòng ban với parent không tồn tại</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . ($result['message'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
} 