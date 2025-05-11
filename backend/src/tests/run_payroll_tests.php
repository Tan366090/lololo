<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/test_payroll_api.php';

class PayrollTestRunner {
    private $logFile;
    private $tester;

    public function __construct() {
        $this->logFile = __DIR__ . '/test_results.txt';
        $this->tester = new PayrollAPITest();
        
        // Clear previous test results
        file_put_contents($this->logFile, '');
    }

    private function logResult($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    private function formatResponse($result) {
        $output = "Status Code: " . $result['code'] . "\n";
        
        if (isset($result['error'])) {
            $output .= "Error: " . $result['error'] . "\n";
        }
        
        if (isset($result['verbose'])) {
            $output .= "Debug Info:\n" . $result['verbose'] . "\n";
        }
        
        if (isset($result['raw_response'])) {
            $output .= "Raw Response:\n" . $result['raw_response'] . "\n";
        }
        
        if (isset($result['response'])) {
            $output .= "Response:\n" . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
        }
        
        return $output;
    }

    public function runTests() {
        $this->logResult("Starting Payroll API Tests");
        $this->logResult("========================================\n");

        // Test Get Payrolls
        $this->logResult("Testing GET /payroll.php");
        $result = $this->tester->testGetPayrolls();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Get Payroll By ID
        $this->logResult("Testing GET /payroll.php?action=getById&id=1");
        $result = $this->tester->testGetPayrollById();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Get Payroll Years
        $this->logResult("Testing GET /payroll.php?action=years");
        $result = $this->tester->testGetPayrollYears();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Get Salary Components
        $this->logResult("Testing GET /payroll.php?action=components");
        $result = $this->tester->testGetSalaryComponents();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Get Approval History
        $this->logResult("Testing GET /payroll.php?action=approval-history&id=1");
        $result = $this->tester->testGetApprovalHistory();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Calculate Salary
        $this->logResult("Testing GET /payroll.php?action=calculate");
        $result = $this->tester->testCalculateSalary();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Get Department Report
        $this->logResult("Testing GET /payroll.php?action=department-report");
        $result = $this->tester->testGetDepartmentReport();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Get Employee Payroll
        $this->logResult("Testing GET /payroll.php?action=getEmployeePayroll");
        $result = $this->tester->testGetEmployeePayroll();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Search Employee
        $this->logResult("Testing GET /payroll.php?action=searchEmployee");
        $result = $this->tester->testSearchEmployee();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Create Payroll
        $this->logResult("Testing POST /payroll.php");
        $result = $this->tester->testCreatePayroll();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Approve Payroll
        $this->logResult("Testing POST /payroll.php?action=approve");
        $result = $this->tester->testApprovePayroll();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Export Payroll
        $this->logResult("Testing POST /payroll.php?action=export");
        $result = $this->tester->testExportPayroll();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Update Payroll
        $this->logResult("Testing PUT /payroll.php?id=1");
        $result = $this->tester->testUpdatePayroll();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        // Test Delete Payroll
        $this->logResult("Testing DELETE /payroll.php?id=1");
        $result = $this->tester->testDeletePayroll();
        $this->logResult($this->formatResponse($result));
        $this->logResult("----------------------------------------\n");

        $this->logResult("\nAll tests completed!");
        
        // Return the log file path
        return $this->logFile;
    }
}

// Create HTML interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payroll API Tests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .button {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payroll API Tests</h1>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $runner = new PayrollTestRunner();
            $logFile = $runner->runTests();
            echo "<div class='result'>";
            echo "<h3>Test Results:</h3>";
            echo "<p>Tests completed. Results have been saved to: " . $logFile . "</p>";
            echo "<p>Click the button below to view the results:</p>";
            echo "<a href='test_results.txt' class='button' target='_blank'>View Results</a>";
            echo "<h4>Latest Results:</h4>";
            echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
            echo "</div>";
        }
        ?>
        <form method="post">
            <button type="submit" class="button">Run Tests</button>
        </form>
    </div>
</body>
</html> 