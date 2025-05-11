<?php
class NLPProcessor {
    private $config;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->config = require __DIR__ . '/../../config/chat.php';
    }
    
    public function process($query) {
        try {
            // 1. Tiền xử lý câu hỏi
            $tokens = $this->preprocess($query);
            
            // 2. Phân tích cú pháp
            $syntax = $this->analyzeSyntax($tokens);
            
            // 3. Xác định intent
            $intent = $this->detectIntent($tokens, $syntax);
            
            // 4. Trích xuất entities
            $entities = $this->extractEntities($tokens, $syntax, $intent);
            
            return [
                'intent' => $intent,
                'entities' => $entities,
                'tokens' => $tokens,
                'syntax' => $syntax
            ];
        } catch (Exception $e) {
            error_log("Error in NLPProcessor: " . $e->getMessage());
            return [
                'intent' => null,
                'entities' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function preprocess($query) {
        // Chuyển về chữ thường
        $query = mb_strtolower($query, 'UTF-8');
        
        // Loại bỏ dấu câu
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        
        // Loại bỏ khoảng trắng thừa
        $query = trim(preg_replace('/\s+/', ' ', $query));
        
        // Tách thành các từ
        return explode(' ', $query);
    }
    
    private function analyzeSyntax($tokens) {
        $syntax = [];
        
        // Phân tích cấu trúc câu
        foreach ($tokens as $token) {
            // Xác định loại từ
            $type = $this->determineWordType($token);
            
            // Xác định vai trò trong câu
            $role = $this->determineWordRole($token, $tokens);
            
            $syntax[] = [
                'word' => $token,
                'type' => $type,
                'role' => $role
            ];
        }
        
        return $syntax;
    }
    
    private function detectIntent($tokens, $syntax) {
        // Các từ khóa cho intent thông tin nhân viên
        $employeeInfoKeywords = ['thông tin', 'nhân viên', 'nhân sự', 'thông tin nhân viên'];
        
        // Các từ khóa cho intent lương
        $salaryKeywords = ['lương', 'thu nhập', 'lương tháng', 'lương cơ bản'];
        
        // Các từ khóa cho intent phòng ban
        $departmentKeywords = ['phòng ban', 'phòng', 'bộ phận', 'phòng làm việc'];
        
        // Kiểm tra intent thông tin nhân viên
        foreach ($employeeInfoKeywords as $keyword) {
            if (in_array($keyword, $tokens)) {
                return 'employee_info';
            }
        }
        
        // Kiểm tra intent lương
        foreach ($salaryKeywords as $keyword) {
            if (in_array($keyword, $tokens)) {
                return 'salary_info';
            }
        }
        
        // Kiểm tra intent phòng ban
        foreach ($departmentKeywords as $keyword) {
            if (in_array($keyword, $tokens)) {
                return 'department_info';
            }
        }
        
        // Nếu không tìm thấy intent cụ thể, kiểm tra ngữ cảnh
        foreach ($syntax as $word) {
            if ($word['type'] === 'proper_noun' && $this->isEmployeeName($word['word'])) {
                return 'employee_info';
            }
        }
        
        return null;
    }
    
    private function extractEntities($tokens, $syntax, $intent) {
        $entities = [];
        
        if ($intent === 'employee_info') {
            // Tìm tên nhân viên
            foreach ($syntax as $word) {
                if ($word['type'] === 'proper_noun') {
                    $entities['employee_name'] = $word['word'];
                    break;
                }
            }
        }
        
        return $entities;
    }
    
    private function determineWordType($word) {
        // Kiểm tra số
        if (is_numeric($word)) {
            return 'number';
        }
        
        // Kiểm tra tên riêng (viết hoa chữ cái đầu)
        if (mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8') === mb_substr($word, 0, 1, 'UTF-8')) {
            return 'proper_noun';
        }
        
        // Kiểm tra động từ
        if ($this->isVerb($word)) {
            return 'verb';
        }
        
        return 'noun';
    }
    
    private function determineWordRole($word, $context) {
        // Xác định vai trò của từ trong câu
        if ($this->isSubject($word, $context)) {
            return 'subject';
        }
        
        if ($this->isObject($word, $context)) {
            return 'object';
        }
        
        if ($this->isPredicate($word, $context)) {
            return 'predicate';
        }
        
        return 'unknown';
    }
    
    private function calculateSimilarity($tokens1, $tokens2) {
        $intersection = array_intersect($tokens1, $tokens2);
        $union = array_unique(array_merge($tokens1, $tokens2));
        
        return count($intersection) / count($union);
    }
    
    private function findEntityValue($tokens, $entityType) {
        switch ($entityType) {
            case 'employee_name':
                return $this->findEmployeeName($tokens);
            case 'department_name':
                return $this->findDepartmentName($tokens);
            case 'month':
                return $this->findMonth($tokens);
            case 'year':
                return $this->findYear($tokens);
            default:
                return null;
        }
    }
    
    private function isProperNoun($word) {
        // Kiểm tra xem từ có phải là tên riêng không
        $sql = "SELECT COUNT(*) as count FROM employees WHERE name LIKE ?";
        $stmt = $this->conn->prepare($sql);
        $pattern = "%$word%";
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    private function isVerb($word) {
        // Danh sách các động từ phổ biến
        $commonVerbs = ['tìm', 'xem', 'cho', 'biết', 'hiển', 'thị', 'tính', 'tổng', 'số'];
        return in_array($word, $commonVerbs);
    }
    
    private function isSubject($word, $context) {
        // Logic xác định chủ ngữ
        return $this->isProperNoun($word) || $this->isNoun($word);
    }
    
    private function isObject($word, $context) {
        // Logic xác định tân ngữ
        return $this->isProperNoun($word) || $this->isNoun($word);
    }
    
    private function isPredicate($word, $context) {
        // Logic xác định vị ngữ
        return $this->isVerb($word);
    }
    
    private function isNoun($word) {
        // Danh sách các danh từ phổ biến
        $commonNouns = ['nhân viên', 'phòng ban', 'lương', 'thông tin', 'danh sách'];
        return in_array($word, $commonNouns);
    }
    
    private function findEmployeeName($tokens) {
        // Tìm tên nhân viên trong câu
        foreach ($tokens as $token) {
            if ($this->isProperNoun($token)) {
                return $token;
            }
        }
        return null;
    }
    
    private function findDepartmentName($tokens) {
        // Tìm tên phòng ban trong câu
        $sql = "SELECT name FROM departments";
        $result = $this->conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            foreach ($tokens as $token) {
                if (stripos($row['name'], $token) !== false) {
                    return $row['name'];
                }
            }
        }
        return null;
    }
    
    private function findMonth($tokens) {
        // Tìm tháng trong câu
        $months = [
            'tháng 1' => 1, 'tháng 2' => 2, 'tháng 3' => 3,
            'tháng 4' => 4, 'tháng 5' => 5, 'tháng 6' => 6,
            'tháng 7' => 7, 'tháng 8' => 8, 'tháng 9' => 9,
            'tháng 10' => 10, 'tháng 11' => 11, 'tháng 12' => 12
        ];
        
        foreach ($tokens as $token) {
            if (isset($months[$token])) {
                return $months[$token];
            }
        }
        return null;
    }
    
    private function findYear($tokens) {
        // Tìm năm trong câu
        foreach ($tokens as $token) {
            if (preg_match('/^\d{4}$/', $token)) {
                return $token;
            }
        }
        return date('Y'); // Mặc định là năm hiện tại
    }
    
    private function isEmployeeName($word) {
        // Kiểm tra trong database
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM employees WHERE name LIKE ?");
        $searchTerm = "%$word%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
} 