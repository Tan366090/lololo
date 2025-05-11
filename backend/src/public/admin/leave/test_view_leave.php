<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Test case 1: View leave with ID 661
function testViewLeave($leaveId) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Query to get leave details with correct column names based on DB structure
        $query = "SELECT l.*, e.name as employee_name, u.username as approver_name 
                 FROM leaves l 
                 LEFT JOIN employees e ON l.employee_id = e.id 
                 LEFT JOIN users u ON l.approved_by_user_id = u.user_id 
                 WHERE l.id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $leaveId);
        $stmt->execute();
        
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($leave) {
            echo "<h2>Test Case: View Leave Details</h2>";
            echo "<h3>Leave ID: " . $leaveId . "</h3>";
            echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
            echo "<h4>Leave Information:</h4>";
            echo "<ul>";
            echo "<li><strong>Leave Code:</strong> " . ($leave['leave_code'] ?? 'N/A') . "</li>";
            echo "<li><strong>Employee:</strong> " . ($leave['employee_name'] ?? 'N/A') . "</li>";
            echo "<li><strong>Leave Type:</strong> " . ($leave['leave_type'] ?? 'N/A') . "</li>";
            echo "<li><strong>Type:</strong> " . ($leave['type'] ?? 'N/A') . "</li>";
            echo "<li><strong>Start Date:</strong> " . date('d/m/Y H:i', strtotime($leave['start_date'])) . "</li>";
            echo "<li><strong>End Date:</strong> " . date('d/m/Y H:i', strtotime($leave['end_date'])) . "</li>";
            echo "<li><strong>Duration:</strong> " . ($leave['leave_duration_days'] ?? 'N/A') . " days</li>";
            echo "<li><strong>Reason:</strong> " . ($leave['reason'] ?? 'N/A') . "</li>";
            echo "<li><strong>Status:</strong> " . ($leave['status'] ?? 'N/A') . "</li>";
            echo "<li><strong>Approver:</strong> " . ($leave['approver_name'] ?? 'N/A') . "</li>";
            echo "<li><strong>Approver Comments:</strong> " . ($leave['approver_comments'] ?? 'N/A') . "</li>";
            echo "<li><strong>Attachment:</strong> " . ($leave['attachment_url'] ?? 'N/A') . "</li>";
            echo "<li><strong>Created At:</strong> " . date('d/m/Y H:i', strtotime($leave['created_at'])) . "</li>";
            echo "<li><strong>Updated At:</strong> " . date('d/m/Y H:i', strtotime($leave['updated_at'])) . "</li>";
            echo "</ul>";
            echo "</div>";
            
            // Test case result
            echo "<div style='margin-top: 20px; padding: 10px; background: #d4edda; color: #155724; border-radius: 4px;'>";
            echo "✅ Test Case Passed: Successfully retrieved leave details";
            echo "</div>";
        } else {
            echo "<div style='margin-top: 20px; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;'>";
            echo "❌ Test Case Failed: Leave not found with ID " . $leaveId;
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div style='margin-top: 20px; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;'>";
        echo "❌ Test Case Failed: Database Error - " . $e->getMessage();
        echo "</div>";
    }
}

// Run the test
testViewLeave(661);
?> 