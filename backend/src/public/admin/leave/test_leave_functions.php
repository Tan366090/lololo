<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Khởi tạo session
session_start();
$_SESSION['user_id'] = 1; // Set user_id mặc định để test

// Hàm helper để test API
function testAPI($method, $endpoint, $data = null) {
    $url = "http://localhost/qlnhansu_V3/backend/src/public/admin/api/" . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    } else if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'status' => $httpCode,
            'success' => false,
            'error' => "CURL Error: " . $error
        ];
    }
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Hàm in kết quả test
function printTestResult($testName, $result) {
    $isSuccess = false;
    $errorMessage = '';
    
    if (isset($result['error'])) {
        $isSuccess = false;
        $errorMessage = $result['error'];
    } else if (isset($result['data']['success'])) {
        $isSuccess = $result['data']['success'];
        if (!$isSuccess && isset($result['data']['message'])) {
            $errorMessage = $result['data']['message'];
        }
    } else if ($result['status'] >= 200 && $result['status'] < 300) {
        $isSuccess = true;
    }
    
    $boxStyle = 'margin: 10px 0; padding: 15px; border-radius: 5px; border: 1px solid ' . 
                ($isSuccess ? '#28a745' : '#dc3545') . ';';
    $headerStyle = 'color: ' . ($isSuccess ? '#28a745' : '#dc3545') . '; margin: 0 0 10px 0;';
    $statusStyle = 'display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.9em; ' .
                   'background-color: ' . ($isSuccess ? '#d4edda' : '#f8d7da') . '; ' .
                   'color: ' . ($isSuccess ? '#155724' : '#721c24') . ';';
    
    echo "<div style='$boxStyle'>";
    echo "<h3 style='$headerStyle'>$testName</h3>";
    echo "<div style='margin-bottom: 10px;'>";
    echo "<span style='$statusStyle'>" . ($isSuccess ? "✓ Thành công" : "✗ Thất bại") . "</span>";
    echo "</div>";
    
    if (!$isSuccess && $errorMessage) {
        echo "<div style='color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 3px; margin-bottom: 10px;'>";
        echo "<strong>Chi tiết lỗi:</strong><br>";
        echo $errorMessage;
        echo "</div>";
    }
    
    echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 3px;'>";
    echo "<strong>Response:</strong><br>";
    echo "<pre style='margin: 0;'>";
    print_r($result);
    echo "</pre>";
    echo "</div>";
    echo "</div>";
}

// Biến lưu ID của đơn nghỉ phép
$leaveId = null;

// Tạo đơn nghỉ phép mới để test các chức năng khác
$newLeave = [
    'employee_id' => 1,
    'leave_type' => 'Annual',
    'start_date' => '2024-05-01',
    'end_date' => '2024-05-03',
    'reason' => 'Nghỉ phép năm',
    'type' => 'Full Day'
];
$result = testAPI('POST', 'leaves.php', $newLeave);
if (isset($result['data']['success']) && $result['data']['success']) {
    $leaveId = $result['data']['id'];
    echo "<div style='color: #28a745; background-color: #d4edda; padding: 10px; border-radius: 3px; margin-bottom: 20px;'>";
    echo "Đã tạo đơn nghỉ phép mới với ID: " . $leaveId;
    echo "</div>";
}

// 7. Test Button "Sửa" - Sửa đơn nghỉ phép
echo "<h2>Test Case 7: Button Sửa - Sửa đơn nghỉ phép</h2>";

// Test case 7.1: Sửa đơn nghỉ phép thành công
echo "<h3>Test Case 7.1: Sửa đơn nghỉ phép thành công</h3>";
if ($leaveId) {
    $updateData = [
        'employee_id' => 1,
        'leave_type' => 'Sick',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-03',
        'reason' => 'Nghỉ ốm - Đã sửa',
        'type' => 'Half Day'
    ];
    $result = testAPI('PUT', "leaves.php?id=$leaveId", $updateData);
    printTestResult('Sửa đơn nghỉ phép thành công', $result);
} else {
    echo "<div style='color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 3px;'>";
    echo "Không thể thực hiện test này vì chưa có ID đơn nghỉ phép";
    echo "</div>";
}

// Test case 7.2: Sửa đơn với dữ liệu không hợp lệ
echo "<h3>Test Case 7.2: Sửa đơn với dữ liệu không hợp lệ</h3>";
if ($leaveId) {
    $invalidData = [
        'employee_id' => 1,
        'leave_type' => 'Invalid Type', // Loại nghỉ phép không hợp lệ
        'start_date' => '2024-05-01',
        'end_date' => '2024-04-01', // Ngày kết thúc trước ngày bắt đầu
        'reason' => ''
    ];
    $result = testAPI('PUT', "leaves.php?id=$leaveId", $invalidData);
    printTestResult('Sửa đơn với dữ liệu không hợp lệ', $result);
}

// 8. Test Button "Duyệt" - Duyệt đơn nghỉ phép
echo "<h2>Test Case 8: Button Duyệt - Duyệt đơn nghỉ phép</h2>";

// Test case 8.1: Duyệt đơn nghỉ phép thành công
echo "<h3>Test Case 8.1: Duyệt đơn nghỉ phép thành công</h3>";
if ($leaveId) {
    $approveData = [
        'employee_id' => 1,
        'leave_type' => 'Sick',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-03',
        'reason' => 'Nghỉ ốm',
        'status' => 'approved',
        'approver_comments' => 'Đơn được duyệt',
        'approved_by_user_id' => 1
    ];
    $result = testAPI('PUT', "leaves.php?id=$leaveId", $approveData);
    printTestResult('Duyệt đơn nghỉ phép thành công', $result);
}

// Test case 8.2: Duyệt đơn đã được duyệt
echo "<h3>Test Case 8.2: Duyệt đơn đã được duyệt</h3>";
if ($leaveId) {
    $approveData = [
        'employee_id' => 1,
        'leave_type' => 'Sick',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-03',
        'reason' => 'Nghỉ ốm',
        'status' => 'approved',
        'approver_comments' => 'Đơn được duyệt lần 2',
        'approved_by_user_id' => 1
    ];
    $result = testAPI('PUT', "leaves.php?id=$leaveId", $approveData);
    printTestResult('Duyệt đơn đã được duyệt (nên thất bại)', $result);
}

// 9. Test Button "Từ chối" - Từ chối đơn nghỉ phép
echo "<h2>Test Case 9: Button Từ chối - Từ chối đơn nghỉ phép</h2>";

// Test case 9.1: Từ chối đơn nghỉ phép thành công
echo "<h3>Test Case 9.1: Từ chối đơn nghỉ phép thành công</h3>";
if ($leaveId) {
    $rejectData = [
        'employee_id' => 1,
        'leave_type' => 'Sick',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-03',
        'reason' => 'Nghỉ ốm',
        'status' => 'rejected',
        'approver_comments' => 'Đơn bị từ chối do không đủ điều kiện',
        'approved_by_user_id' => 1
    ];
    $result = testAPI('PUT', "leaves.php?id=$leaveId", $rejectData);
    printTestResult('Từ chối đơn nghỉ phép thành công', $result);
}

// Test case 9.2: Từ chối đơn không có lý do
echo "<h3>Test Case 9.2: Từ chối đơn không có lý do</h3>";
if ($leaveId) {
    $rejectData = [
        'employee_id' => 1,
        'leave_type' => 'Sick',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-03',
        'reason' => 'Nghỉ ốm',
        'status' => 'rejected',
        'approver_comments' => '', // Không có lý do từ chối
        'approved_by_user_id' => 1
    ];
    $result = testAPI('PUT', "leaves.php?id=$leaveId", $rejectData);
    printTestResult('Từ chối đơn không có lý do (nên thất bại)', $result);
}

// Test case 9.3: Từ chối đơn đã bị từ chối
echo "<h3>Test Case 9.3: Từ chối đơn đã bị từ chối</h3>";
if ($leaveId) {
    $rejectData = [
        'employee_id' => 1,
        'leave_type' => 'Sick',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-03',
        'reason' => 'Nghỉ ốm',
        'status' => 'rejected',
        'approver_comments' => 'Đơn bị từ chối lần 2',
        'approved_by_user_id' => 1
    ];
    $result = testAPI('PUT', "leaves.php?id=$leaveId", $rejectData);
    printTestResult('Từ chối đơn đã bị từ chối (nên thất bại)', $result);
}

// CSS để làm đẹp kết quả test
echo "<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #f5f5f5;
    }
    h2 {
        color: #333;
        border-bottom: 2px solid #333;
        padding-bottom: 5px;
        margin-top: 30px;
    }
    h3 {
        color: #666;
        margin: 15px 0 10px 0;
        font-size: 1.1em;
    }
    pre {
        background-color: #fff;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .success {
        color: #28a745;
    }
    .error {
        color: #dc3545;
    }
</style>";
?> 