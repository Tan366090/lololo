<?php
// Include the test file
require_once __DIR__ . '/test_payroll.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Add some basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Kết quả kiểm tra quản lý lương</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">';

// Run the test
$payrollTest = new PayrollTest();
$payrollTest->runAllTests();

echo '</div></body></html>';
?> 