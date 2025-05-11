<?php
class QueryBuilder {
    private $config;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->config = require __DIR__ . '/../../config/chat.php';
    }
    
    public function buildQuery($intent, $entities) {
        if (!isset($this->config['intents'][$intent])) {
            throw new Exception("Unknown intent: $intent");
        }
        
        $intentConfig = $this->config['intents'][$intent];
        $tables = $intentConfig['required_tables'];
        
        // Xây dựng câu truy vấn cơ bản
        $query = $this->buildBaseQuery($tables);
        
        // Thêm điều kiện
        $query = $this->addConditions($query, $entities);
        
        // Thêm sắp xếp
        $query = $this->addOrdering($query, $intent);
        
        return $query;
    }
    
    private function buildBaseQuery($tables) {
        $select = [];
        $from = [];
        $joins = [];
        
        // Xác định các cột cần select
        foreach ($tables as $table) {
            $tableConfig = $this->config['database']['tables'][$table];
            foreach ($tableConfig['columns'] as $column) {
                $select[] = "$table.$column";
            }
            
            // Thêm vào FROM hoặc JOIN
            if (empty($from)) {
                $from[] = $table;
            } else {
                // Tìm relation
                foreach ($tableConfig['relations'] as $relatedTable => $relation) {
                    if (in_array($relatedTable, $tables)) {
                        $joins[] = $this->buildJoin($table, $relatedTable, $relation);
                    }
                }
            }
        }
        
        // Xây dựng câu truy vấn
        $query = "SELECT " . implode(", ", $select);
        $query .= " FROM " . implode(", ", $from);
        
        if (!empty($joins)) {
            $query .= " " . implode(" ", $joins);
        }
        
        return $query;
    }
    
    private function buildJoin($table, $relatedTable, $relation) {
        $type = strtoupper($relation['type']);
        $foreignKey = $relation['foreign_key'];
        
        return "$type JOIN $relatedTable ON $table.$foreignKey = $relatedTable.id";
    }
    
    private function addConditions($query, $entities) {
        $conditions = [];
        
        foreach ($entities as $entity => $value) {
            switch ($entity) {
                case 'employee_name':
                    $conditions[] = "employees.name LIKE '%" . $this->conn->real_escape_string($value) . "%'";
                    break;
                    
                case 'department_name':
                    $conditions[] = "departments.name LIKE '%" . $this->conn->real_escape_string($value) . "%'";
                    break;
                    
                case 'month':
                    $conditions[] = "MONTH(salaries.effective_date) = " . intval($value);
                    break;
                    
                case 'year':
                    $conditions[] = "YEAR(salaries.effective_date) = " . intval($value);
                    break;
            }
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        return $query;
    }
    
    private function addOrdering($query, $intent) {
        switch ($intent) {
            case 'employee_info':
                $query .= " ORDER BY employees.name ASC";
                break;
                
            case 'salary_info':
                $query .= " ORDER BY salaries.effective_date DESC";
                break;
                
            case 'department_info':
                $query .= " ORDER BY departments.name ASC";
                break;
        }
        
        return $query;
    }
    
    public function executeQuery($query) {
        $result = $this->conn->query($query);
        
        if (!$result) {
            throw new Exception("Query execution failed: " . $this->conn->error);
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    public function formatResult($data, $intent) {
        switch ($intent) {
            case 'employee_info':
                return $this->formatEmployeeInfo($data);
                
            case 'salary_info':
                return $this->formatSalaryInfo($data);
                
            case 'department_info':
                return $this->formatDepartmentInfo($data);
                
            default:
                return $data;
        }
    }
    
    private function formatEmployeeInfo($data) {
        $formatted = [];
        foreach ($data as $row) {
            $formatted[] = [
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'department' => $row['department_name'],
                'position' => $row['position_name'],
                'status' => $row['status']
            ];
        }
        return $formatted;
    }
    
    private function formatSalaryInfo($data) {
        $formatted = [];
        foreach ($data as $row) {
            $formatted[] = [
                'employee_name' => $row['name'],
                'amount' => number_format($row['amount']),
                'effective_date' => date('d/m/Y', strtotime($row['effective_date'])),
                'type' => $row['type']
            ];
        }
        return $formatted;
    }
    
    private function formatDepartmentInfo($data) {
        $formatted = [];
        foreach ($data as $row) {
            $formatted[] = [
                'name' => $row['name'],
                'description' => $row['description'],
                'manager' => $row['manager_name'],
                'employee_count' => $row['employee_count']
            ];
        }
        return $formatted;
    }
} 