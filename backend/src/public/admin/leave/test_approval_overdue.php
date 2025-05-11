<?php
require_once __DIR__ . '/../../../config/database.php';

// Hàm kiểm tra đơn quá hạn duyệt
function isLeaveOverdue($endDate) {
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Reset time to start of day
    
    $end = new DateTime($endDate);
    $end->setTime(0, 0, 0);
    
    return $end < $today;
}

// Hàm đếm số lượng đơn quá hạn duyệt
function countApprovalOverdueLeaves($conn) {
    try {
        // Lấy tất cả đơn nghỉ phép đang pending
        $query = "SELECT * FROM leaves WHERE status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $overdueCount = 0;
        foreach ($leaves as $leave) {
            if (isLeaveOverdue($leave['end_date'])) {
                $overdueCount++;
            }
        }
        
        return $overdueCount;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

// Hàm hiển thị chi tiết các đơn quá hạn
function displayOverdueLeaves($conn) {
    try {
        $query = "SELECT l.*, e.name as employee_name 
                 FROM leaves l 
                 LEFT JOIN employees e ON l.employee_id = e.id 
                 WHERE l.status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Danh sách đơn quá hạn duyệt</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>ID</th>
                <th>Nhân viên</th>
                <th>Loại nghỉ</th>
                <th>Ngày bắt đầu</th>
                <th>Ngày kết thúc</th>
                <th>Số ngày</th>
                <th>Lý do</th>
                <th>Trạng thái</th>
              </tr>";
        
        foreach ($leaves as $leave) {
            if (isLeaveOverdue($leave['end_date'])) {
                echo "<tr style='background-color: #ffebee;'>";
                echo "<td>{$leave['id']}</td>";
                echo "<td>{$leave['employee_name']}</td>";
                echo "<td>{$leave['leave_type']}</td>";
                echo "<td>{$leave['start_date']}</td>";
                echo "<td>{$leave['end_date']}</td>";
                echo "<td>{$leave['leave_duration_days']}</td>";
                echo "<td>{$leave['reason']}</td>";
                echo "<td>Quá hạn duyệt</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Kết nối database
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Đếm số lượng đơn quá hạn
    $overdueCount = countApprovalOverdueLeaves($conn);
    echo "<h1>Thống kê đơn quá hạn duyệt</h1>";
    echo "<p>Tổng số đơn quá hạn duyệt: <strong>{$overdueCount}</strong></p>";
    
    // Hiển thị chi tiết các đơn quá hạn
    displayOverdueLeaves($conn);
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?> 