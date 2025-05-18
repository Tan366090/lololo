<?php
class Lock {
    private $conn;
    private $table_name = "resource_locks";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Khóa tài nguyên
    public function acquire($resource_id) {
        try {
            // Kiểm tra xem tài nguyên đã bị khóa chưa
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE resource_id = :resource_id 
                     AND expires_at > NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $resource_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return false; // Tài nguyên đã bị khóa
            }
            
            // Xóa các khóa hết hạn
            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE resource_id = :resource_id 
                     AND expires_at <= NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $resource_id);
            $stmt->execute();
            
            // Tạo khóa mới
            $query = "INSERT INTO " . $this->table_name . " 
                     (resource_id, locked_at, expires_at) 
                     VALUES (:resource_id, NOW(), DATE_ADD(NOW(), INTERVAL 5 MINUTE))";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $resource_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error in Lock::acquire: " . $e->getMessage());
            return false;
        }
    }

    // Mở khóa tài nguyên
    public function release($resource_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE resource_id = :resource_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $resource_id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error in Lock::release: " . $e->getMessage());
            return false;
        }
    }
} 