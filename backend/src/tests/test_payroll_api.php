<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

class PayrollAPITest {
    private $baseUrl;
    private $testData;
    private $conn;

    public function __construct() {
        $this->baseUrl = 'http://localhost/qlnhansu_V3/backend/src/public/admin/api/payroll.php';
        $this->conn = Database::getConnection();
        $this->testData = [
            'employee_id' => 1,
            'pay_period_start' => date('Y-m-d'),
            'pay_period_end' => date('Y-m-d', strtotime('+1 month')),
            'basic_salary' => 10000000
        ];
    }

    // Helper function to make API calls
    private function callAPI($method, $url, $data = null) {
        $curl = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_VERBOSE => true
        ];

        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
            $options[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
        }

        curl_setopt_array($curl, $options);
        
        // Get verbose debug information
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($curl, CURLOPT_STDERR, $verbose);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Get debug information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            return [
                'code' => $httpCode,
                'error' => $error,
                'verbose' => $verboseLog,
                'response' => null
            ];
        }

        curl_close($curl);
        
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'code' => $httpCode,
                'error' => 'JSON decode error: ' . json_last_error_msg(),
                'verbose' => $verboseLog,
                'raw_response' => $response,
                'response' => null
            ];
        }

        return [
            'code' => $httpCode,
            'verbose' => $verboseLog,
            'response' => $decodedResponse
        ];
    }

    // Test functions
    public function testGetPayrolls() {
        echo "\nTesting GET /payroll.php\n";
        $result = $this->callAPI('GET', $this->baseUrl);
        return $result;
    }

    public function testGetPayrollById() {
        echo "\nTesting GET /payroll.php?action=getById&id=1\n";
        $result = $this->callAPI('GET', $this->baseUrl . '?action=getById&id=1');
        return $result;
    }

    public function testGetPayrollYears() {
        echo "\nTesting GET /payroll.php?action=years\n";
        $result = $this->callAPI('GET', $this->baseUrl . '?action=years');
        return $result;
    }

    public function testGetSalaryComponents() {
        echo "\nTesting GET /payroll.php?action=components\n";
        $result = $this->callAPI('GET', $this->baseUrl . '?action=components');
        return $result;
    }

    public function testGetApprovalHistory() {
        echo "\nTesting GET /payroll.php?action=approval-history&id=1\n";
        $result = $this->callAPI('GET', $this->baseUrl . '?action=approval-history&id=1');
        return $result;
    }

    public function testCalculateSalary() {
        echo "\nTesting GET /payroll.php?action=calculate\n";
        $params = http_build_query([
            'employee_id' => 1,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 month'))
        ]);
        $result = $this->callAPI('GET', $this->baseUrl . '?action=calculate&' . $params);
        return $result;
    }

    public function testGetDepartmentReport() {
        echo "\nTesting GET /payroll.php?action=department-report\n";
        $params = http_build_query([
            'department_id' => 1,
            'month' => date('m'),
            'year' => date('Y')
        ]);
        $result = $this->callAPI('GET', $this->baseUrl . '?action=department-report&' . $params);
        return $result;
    }

    public function testGetEmployeePayroll() {
        echo "\nTesting GET /payroll.php?action=getEmployeePayroll\n";
        $result = $this->callAPI('GET', $this->baseUrl . '?action=getEmployeePayroll&employeeCode=NV001');
        return $result;
    }

    public function testSearchEmployee() {
        echo "\nTesting GET /payroll.php?action=searchEmployee\n";
        $result = $this->callAPI('GET', $this->baseUrl . '?action=searchEmployee&employeeCode=NV001');
        return $result;
    }

    public function testCreatePayroll() {
        echo "\nTesting POST /payroll.php\n";
        $result = $this->callAPI('POST', $this->baseUrl, $this->testData);
        return $result;
    }

    public function testApprovePayroll() {
        echo "\nTesting POST /payroll.php?action=approve\n";
        $data = [
            'approver_id' => 1,
            'action' => 'APPROVED',
            'comments' => 'Test approval'
        ];
        $result = $this->callAPI('POST', $this->baseUrl . '?action=approve&id=1', $data);
        return $result;
    }

    public function testExportPayroll() {
        echo "\nTesting POST /payroll.php?action=export\n";
        $data = [
            'search' => '',
            'department' => '',
            'month' => date('m'),
            'year' => date('Y')
        ];
        $result = $this->callAPI('POST', $this->baseUrl . '?action=export', $data);
        return $result;
    }

    public function testUpdatePayroll() {
        echo "\nTesting PUT /payroll.php?id=1\n";
        $data = [
            'basic_salary' => 12000000,
            'notes' => 'Updated test payroll'
        ];
        $result = $this->callAPI('PUT', $this->baseUrl . '?id=1', $data);
        return $result;
    }

    public function testDeletePayroll() {
        echo "\nTesting DELETE /payroll.php?id=1\n";
        $result = $this->callAPI('DELETE', $this->baseUrl . '?id=1');
        return $result;
    }
}
?> 