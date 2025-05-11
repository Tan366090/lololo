<?php
require_once __DIR__ . '/../src/config/database.php';

class DepartmentStatusTest {
    private $conn;
    private $testDepartmentId;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=localhost;dbname=qlnhansu",
                "root",
                "",
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch(PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }

    public function runTests() {
        echo "=== Bắt đầu kiểm tra trạng thái phòng ban ===\n\n";

        // Test tạo phòng ban mới với trạng thái active
        $this->testCreateDepartmentWithActiveStatus();
        
        // Test tạo phòng ban mới với trạng thái inactive
        $this->testCreateDepartmentWithInactiveStatus();
        
        // Test cập nhật trạng thái từ active sang inactive
        $this->testUpdateStatusFromActiveToInactive();
        
        // Test cập nhật trạng thái từ inactive sang active
        $this->testUpdateStatusFromInactiveToActive();
        
        // Test tạo phòng ban mới không có trạng thái (mặc định active)
        $this->testCreateDepartmentWithoutStatus();

        // Dọn dẹp dữ liệu test
        $this->cleanup();
    }

    private function testCreateDepartmentWithActiveStatus() {
        echo "Test 1: Tạo phòng ban mới với trạng thái active\n";
        
        $data = [
            'name' => 'Phòng Test Active',
            'description' => 'Test phòng ban với trạng thái active',
            'status' => 'active',
            'parent_id' => null,
            'manager_id' => null
        ];

        $sql = "INSERT INTO departments (name, description, status, parent_id, manager_id) 
                VALUES (:name, :description, :status, :parent_id, :manager_id)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($data);
            $this->testDepartmentId = $this->conn->lastInsertId();
            
            // Kiểm tra kết quả
            $checkSql = "SELECT status FROM departments WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute(['id' => $this->testDepartmentId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['status'] === 'active') {
                echo "✓ Thành công: Phòng ban được tạo với trạng thái active\n";
            } else {
                echo "✗ Thất bại: Phòng ban không được tạo với trạng thái active\n";
            }
        } catch (PDOException $e) {
            echo "✗ Lỗi: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testCreateDepartmentWithInactiveStatus() {
        echo "Test 2: Tạo phòng ban mới với trạng thái inactive\n";
        
        $data = [
            'name' => 'Phòng Test Inactive',
            'description' => 'Test phòng ban với trạng thái inactive',
            'status' => 'inactive',
            'parent_id' => null,
            'manager_id' => null
        ];

        $sql = "INSERT INTO departments (name, description, status, parent_id, manager_id) 
                VALUES (:name, :description, :status, :parent_id, :manager_id)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($data);
            $id = $this->conn->lastInsertId();
            
            // Kiểm tra kết quả
            $checkSql = "SELECT status FROM departments WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute(['id' => $id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['status'] === 'inactive') {
                echo "✓ Thành công: Phòng ban được tạo với trạng thái inactive\n";
            } else {
                echo "✗ Thất bại: Phòng ban không được tạo với trạng thái inactive\n";
            }
        } catch (PDOException $e) {
            echo "✗ Lỗi: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testUpdateStatusFromActiveToInactive() {
        echo "Test 3: Cập nhật trạng thái từ active sang inactive\n";
        
        if (!$this->testDepartmentId) {
            echo "✗ Thất bại: Không có phòng ban test để cập nhật\n";
            return;
        }

        $sql = "UPDATE departments SET status = 'inactive' WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $this->testDepartmentId]);
            
            // Kiểm tra kết quả
            $checkSql = "SELECT status FROM departments WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute(['id' => $this->testDepartmentId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['status'] === 'inactive') {
                echo "✓ Thành công: Trạng thái được cập nhật thành inactive\n";
            } else {
                echo "✗ Thất bại: Trạng thái không được cập nhật thành inactive\n";
            }
        } catch (PDOException $e) {
            echo "✗ Lỗi: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testUpdateStatusFromInactiveToActive() {
        echo "Test 4: Cập nhật trạng thái từ inactive sang active\n";
        
        if (!$this->testDepartmentId) {
            echo "✗ Thất bại: Không có phòng ban test để cập nhật\n";
            return;
        }

        $sql = "UPDATE departments SET status = 'active' WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $this->testDepartmentId]);
            
            // Kiểm tra kết quả
            $checkSql = "SELECT status FROM departments WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute(['id' => $this->testDepartmentId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['status'] === 'active') {
                echo "✓ Thành công: Trạng thái được cập nhật thành active\n";
            } else {
                echo "✗ Thất bại: Trạng thái không được cập nhật thành active\n";
            }
        } catch (PDOException $e) {
            echo "✗ Lỗi: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testCreateDepartmentWithoutStatus() {
        echo "Test 5: Tạo phòng ban mới không có trạng thái (mặc định active)\n";
        
        $data = [
            'name' => 'Phòng Test Default Status',
            'description' => 'Test phòng ban không có trạng thái',
            'parent_id' => null,
            'manager_id' => null
        ];

        $sql = "INSERT INTO departments (name, description, parent_id, manager_id) 
                VALUES (:name, :description, :parent_id, :manager_id)";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($data);
            $id = $this->conn->lastInsertId();
            
            // Kiểm tra kết quả
            $checkSql = "SELECT status FROM departments WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute(['id' => $id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['status'] === 'active') {
                echo "✓ Thành công: Phòng ban được tạo với trạng thái mặc định active\n";
            } else {
                echo "✗ Thất bại: Phòng ban không được tạo với trạng thái mặc định active\n";
            }
        } catch (PDOException $e) {
            echo "✗ Lỗi: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function cleanup() {
        echo "=== Dọn dẹp dữ liệu test ===\n";
        
        if ($this->testDepartmentId) {
            try {
                $sql = "DELETE FROM departments WHERE id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(['id' => $this->testDepartmentId]);
                echo "✓ Đã xóa phòng ban test\n";
            } catch (PDOException $e) {
                echo "✗ Lỗi khi xóa phòng ban test: " . $e->getMessage() . "\n";
            }
        }

        // Xóa các phòng ban test khác
        try {
            $sql = "DELETE FROM departments WHERE name LIKE 'Phòng Test%'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            echo "✓ Đã xóa các phòng ban test khác\n";
        } catch (PDOException $e) {
            echo "✗ Lỗi khi xóa các phòng ban test khác: " . $e->getMessage() . "\n";
        }
    }
}

// Chạy tests
$test = new DepartmentStatusTest();
$test->runTests(); 