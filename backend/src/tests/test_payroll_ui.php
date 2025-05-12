<?php
namespace Tests;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class PayrollUITest extends TestCase
{
    protected $pdo;
    protected $baseUrl = 'http://localhost/qlnhansu_V3/backend/src/public/admin/payroll/payroll_List.html';

    protected function setUp(): void
    {
        $this->pdo = new \PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Test Dashboard Statistics Display
     */
    public function testDashboardStatistics()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra các phần tử thống kê
        $this->assertStringContainsString('id="totalSalary"', $html, 'Total salary element not found');
        $this->assertStringContainsString('id="totalBonus"', $html, 'Total bonus element not found');
        $this->assertStringContainsString('id="averageSalary"', $html, 'Average salary element not found');
        $this->assertStringContainsString('id="totalPayrolls"', $html, 'Total payrolls element not found');
    }

    /**
     * Test Charts Display
     */
    public function testChartsDisplay()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra các biểu đồ
        $this->assertStringContainsString('id="monthlySalaryChart"', $html, 'Monthly salary chart not found');
        $this->assertStringContainsString('id="departmentSalaryChart"', $html, 'Department salary chart not found');
        $this->assertStringContainsString('id="salaryComparisonChart"', $html, 'Salary comparison chart not found');
        $this->assertStringContainsString('id="costAnalysisChart"', $html, 'Cost analysis chart not found');
    }

    /**
     * Test Search and Filter Functionality
     */
    public function testSearchAndFilter()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra các phần tử tìm kiếm và lọc
        $this->assertStringContainsString('id="searchInput"', $html, 'Search input not found');
        $this->assertStringContainsString('id="departmentFilter"', $html, 'Department filter not found');
        $this->assertStringContainsString('id="monthFilter"', $html, 'Month filter not found');
        $this->assertStringContainsString('id="yearFilter"', $html, 'Year filter not found');
    }

    /**
     * Test Payroll Table Display
     */
    public function testPayrollTable()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra bảng lương
        $this->assertStringContainsString('id="payrollTable"', $html, 'Payroll table not found');
        $this->assertStringContainsString('class="table"', $html, 'Table class not found');
        
        // Kiểm tra các cột trong bảng
        $expectedColumns = [
            'Tên nhân viên',
            'Phòng ban',
            'Lương cơ bản',
            'Phụ cấp',
            'Thưởng',
            'Khấu trừ',
            'Thực lĩnh',
            'Kỳ lương',
            'Thao tác'
        ];
        
        foreach ($expectedColumns as $column) {
            $this->assertStringContainsString($column, $html, "Column '$column' not found in table");
        }
    }

    /**
     * Test Modal Forms
     */
    public function testModalForms()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra các modal
        $this->assertStringContainsString('id="addPayrollModal"', $html, 'Add payroll modal not found');
        $this->assertStringContainsString('id="editPayrollModal"', $html, 'Edit payroll modal not found');
        $this->assertStringContainsString('id="viewPayrollModal"', $html, 'View payroll modal not found');
        
        // Kiểm tra các form trong modal
        $this->assertStringContainsString('id="addPayrollForm"', $html, 'Add payroll form not found');
        $this->assertStringContainsString('id="editPayrollForm"', $html, 'Edit payroll form not found');
    }

    /**
     * Test Advanced Reports Tab
     */
    public function testAdvancedReports()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra tab báo cáo nâng cao
        $this->assertStringContainsString('id="reports"', $html, 'Reports tab not found');
        $this->assertStringContainsString('id="salaryComparisonChart"', $html, 'Salary comparison chart not found');
        $this->assertStringContainsString('id="costAnalysisChart"', $html, 'Cost analysis chart not found');
    }

    /**
     * Test Budget Management Tab
     */
    public function testBudgetManagement()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra tab quản lý ngân sách
        $this->assertStringContainsString('id="budget"', $html, 'Budget tab not found');
        $this->assertStringContainsString('id="budgetChart"', $html, 'Budget chart not found');
        $this->assertStringContainsString('id="spendingChart"', $html, 'Spending chart not found');
    }

    /**
     * Test Compliance Tab
     */
    public function testCompliance()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra tab tuân thủ
        $this->assertStringContainsString('id="compliance"', $html, 'Compliance tab not found');
        $this->assertStringContainsString('id="taxComplianceReport"', $html, 'Tax compliance report not found');
        $this->assertStringContainsString('id="insuranceComplianceReport"', $html, 'Insurance compliance report not found');
    }

    /**
     * Test Forecasting Tab
     */
    public function testForecasting()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra tab dự báo
        $this->assertStringContainsString('id="forecast"', $html, 'Forecast tab not found');
        $this->assertStringContainsString('id="forecastChart"', $html, 'Forecast chart not found');
        $this->assertStringContainsString('id="trendAnalysisChart"', $html, 'Trend analysis chart not found');
    }

    /**
     * Test Workflow Process
     */
    public function testWorkflowProcess()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test approval process
        $this->assertStringContainsString('data-approval-process', $html, 'Approval process not found');
        
        // Test history tracking
        $this->assertStringContainsString('data-history-track', $html, 'History tracking not found');
    }

    /**
     * Test Notifications
     */
    public function testNotifications()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra các thông báo
        $this->assertStringContainsString('class="notification"', $html, 'Notification class not found');
        $this->assertStringContainsString('class="notification success"', $html, 'Success notification not found');
        $this->assertStringContainsString('class="notification error"', $html, 'Error notification not found');
        $this->assertStringContainsString('class="notification warning"', $html, 'Warning notification not found');
    }

    /**
     * Test Loading States
     */
    public function testLoadingStates()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra trạng thái loading
        $this->assertStringContainsString('class="loading-overlay"', $html, 'Loading overlay not found');
        $this->assertStringContainsString('class="spinner"', $html, 'Spinner not found');
    }

    /**
     * Test Responsive Design
     */
    public function testResponsiveDesign()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra responsive design
        $this->assertStringContainsString('class="table-responsive"', $html, 'Table responsive class not found');
        $this->assertStringContainsString('class="container-fluid"', $html, 'Container fluid class not found');
    }

    /**
     * Test Security Features
     */
    public function testSecurityFeatures()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra các tính năng bảo mật
        $this->assertStringContainsString('meta name="csrf-token"', $html, 'CSRF token meta tag not found');
        $this->assertStringContainsString('data-requires-permission', $html, 'Permission attributes not found');
    }

    /**
     * Test Export Functionality
     */
    public function testExportFunctionality()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra nút xuất
        $this->assertStringContainsString('id="exportBtn"', $html, 'Export button not found');
        $this->assertStringContainsString('onclick="exportToExcel()"', $html, 'Export function not found');
    }

    /**
     * Test Form Validation
     */
    public function testFormValidation()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra validation
        $this->assertStringContainsString('required', $html, 'Required attributes not found');
        $this->assertStringContainsString('pattern', $html, 'Pattern attributes not found');
        $this->assertStringContainsString('min', $html, 'Min attributes not found');
        $this->assertStringContainsString('max', $html, 'Max attributes not found');
    }

    /**
     * Test Error Handling
     */
    public function testErrorHandling()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Kiểm tra xử lý lỗi
        $this->assertStringContainsString('class="error-message"', $html, 'Error message class not found');
        $this->assertStringContainsString('class="alert alert-danger"', $html, 'Alert danger class not found');
    }

    /**
     * Test Accessibility
     */
    public function testAccessibility()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test basic accessibility
        $this->assertStringContainsString('aria-label', $html, 'ARIA labels not found');
        $this->assertStringContainsString('role="button"', $html, 'Button roles not found');
        $this->assertStringContainsString('alt=', $html, 'Alt attributes not found');
        
        // Test keyboard navigation
        $this->assertStringContainsString('data-keyboard-nav', $html, 'Keyboard navigation not found');
        
        // Test screen reader
        $this->assertStringContainsString('data-screen-reader', $html, 'Screen reader support not found');
        
        // Test ARIA attributes
        $this->assertStringContainsString('aria-', $html, 'ARIA attributes not found');
    }

    /**
     * Test Data Validation for Dashboard Statistics
     */
    public function testDashboardDataValidation()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test number format validation
        $this->assertStringContainsString('data-format="currency"', $html, 'Currency format not found');
        $this->assertStringContainsString('data-decimal-places="2"', $html, 'Decimal places not found');
        
        // Test real-time update
        $this->assertStringContainsString('data-update-interval', $html, 'Update interval not found');
        
        // Test data accuracy
        $this->assertStringContainsString('data-validate="true"', $html, 'Data validation not found');
    }

    /**
     * Test Chart Functionality
     */
    public function testChartFunctionality()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test chart configuration
        $this->assertStringContainsString('data-chart-config', $html, 'Chart configuration not found');
        
        // Test animations
        $this->assertStringContainsString('data-animation="true"', $html, 'Chart animation not found');
        
        // Test responsiveness
        $this->assertStringContainsString('data-responsive="true"', $html, 'Chart responsiveness not found');
    }

    /**
     * Test Search and Filter Functionality
     */
    public function testSearchAndFilterValidation()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test debounce
        $this->assertStringContainsString('data-debounce="300"', $html, 'Search debounce not found');
        
        // Test filter processing
        $this->assertStringContainsString('data-filter-handler', $html, 'Filter handler not found');
        
        // Test filter reset
        $this->assertStringContainsString('data-reset-filter', $html, 'Filter reset not found');
    }

    /**
     * Test Table Functionality
     */
    public function testTableFunctionality()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test pagination
        $this->assertStringContainsString('data-pagination="true"', $html, 'Pagination not found');
        
        // Test sorting
        $this->assertStringContainsString('data-sortable="true"', $html, 'Sorting not found');
        
        // Test export
        $this->assertStringContainsString('data-export-format', $html, 'Export format not found');
    }

    /**
     * Test Form Validation Details
     */
    public function testFormValidationDetails()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test custom validation
        $this->assertStringContainsString('data-custom-validation', $html, 'Custom validation not found');
        
        // Test error messages
        $this->assertStringContainsString('data-error-message', $html, 'Error message not found');
        
        // Test field validation
        $this->assertStringContainsString('data-field-validation', $html, 'Field validation not found');
    }

    /**
     * Test Advanced Features
     */
    public function testAdvancedFeatures()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test detailed reporting
        $this->assertStringContainsString('data-report-detail', $html, 'Report detail not found');
        
        // Test budget tracking
        $this->assertStringContainsString('data-budget-track', $html, 'Budget tracking not found');
        
        // Test compliance monitoring
        $this->assertStringContainsString('data-compliance-monitor', $html, 'Compliance monitoring not found');
        
        // Test forecasting
        $this->assertStringContainsString('data-forecast-accuracy', $html, 'Forecast accuracy not found');
    }

    /**
     * Test Loading and Error Handling
     */
    public function testLoadingAndErrorHandling()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test timeout handling
        $this->assertStringContainsString('data-timeout-handler', $html, 'Timeout handler not found');
        
        // Test retry mechanism
        $this->assertStringContainsString('data-retry-mechanism', $html, 'Retry mechanism not found');
        
        // Test error recovery
        $this->assertStringContainsString('data-error-recovery', $html, 'Error recovery not found');
    }

    /**
     * Test Header Navigation
     */
    public function testHeaderNavigation()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test menu navigation
        $this->assertStringContainsString('id="mainMenu"', $html, 'Main menu not found');
        $this->assertStringContainsString('class="breadcrumb"', $html, 'Breadcrumbs not found');
        $this->assertStringContainsString('id="userProfile"', $html, 'User profile not found');
        $this->assertStringContainsString('id="languageSwitcher"', $html, 'Language switcher not found');
        $this->assertStringContainsString('id="themeSwitcher"', $html, 'Theme switcher not found');
    }

    /**
     * Test Footer Elements
     */
    public function testFooterElements()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test footer elements
        $this->assertStringContainsString('id="footer"', $html, 'Footer not found');
        $this->assertStringContainsString('class="copyright"', $html, 'Copyright not found');
        $this->assertStringContainsString('class="social-links"', $html, 'Social links not found');
        $this->assertStringContainsString('class="contact-info"', $html, 'Contact information not found');
    }

    /**
     * Test Settings and Preferences
     */
    public function testSettingsAndPreferences()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test settings elements
        $this->assertStringContainsString('id="userPreferences"', $html, 'User preferences not found');
        $this->assertStringContainsString('id="displaySettings"', $html, 'Display settings not found');
        $this->assertStringContainsString('id="notificationSettings"', $html, 'Notification settings not found');
        $this->assertStringContainsString('id="reportCustomization"', $html, 'Report customization not found');
    }

    /**
     * Test Help and Support
     */
    public function testHelpAndSupport()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test help elements
        $this->assertStringContainsString('id="helpDocumentation"', $html, 'Help documentation not found');
        $this->assertStringContainsString('id="faqSection"', $html, 'FAQ section not found');
        $this->assertStringContainsString('id="supportContact"', $html, 'Support contact not found');
        $this->assertStringContainsString('id="tutorialGuide"', $html, 'Tutorial guide not found');
    }

    /**
     * Test Print Preview
     */
    public function testPrintPreview()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test print preview elements
        $this->assertStringContainsString('id="printLayout"', $html, 'Print layout not found');
        $this->assertStringContainsString('id="printFormatting"', $html, 'Print formatting not found');
        $this->assertStringContainsString('id="printTemplates"', $html, 'Print templates not found');
    }

    /**
     * Test Data Import
     */
    public function testDataImport()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test import elements
        $this->assertStringContainsString('id="importInterface"', $html, 'Import interface not found');
        $this->assertStringContainsString('id="fileUpload"', $html, 'File upload not found');
        $this->assertStringContainsString('id="importTemplate"', $html, 'Import template not found');
        $this->assertStringContainsString('id="importValidation"', $html, 'Import validation not found');
    }

    /**
     * Test Batch Operations
     */
    public function testBatchOperations()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test batch operation elements
        $this->assertStringContainsString('id="bulkEdit"', $html, 'Bulk edit not found');
        $this->assertStringContainsString('id="massUpdate"', $html, 'Mass update not found');
        $this->assertStringContainsString('id="batchProcessing"', $html, 'Batch processing not found');
    }

    /**
     * Test Calendar and Date Picker
     */
    public function testCalendarAndDatePicker()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test calendar elements
        $this->assertStringContainsString('id="dateSelection"', $html, 'Date selection not found');
        $this->assertStringContainsString('id="calendarView"', $html, 'Calendar view not found');
        $this->assertStringContainsString('id="dateRange"', $html, 'Date range not found');
    }

    /**
     * Test Advanced Filtering
     */
    public function testAdvancedFiltering()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test advanced filter elements
        $this->assertStringContainsString('id="customFilter"', $html, 'Custom filter not found');
        $this->assertStringContainsString('id="savedFilters"', $html, 'Saved filters not found');
        $this->assertStringContainsString('id="filterPresets"', $html, 'Filter presets not found');
    }

    /**
     * Test Data Visualization
     */
    public function testDataVisualization()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test visualization elements
        $this->assertStringContainsString('id="chartCustomization"', $html, 'Chart customization not found');
        $this->assertStringContainsString('id="graphType"', $html, 'Graph type not found');
        $this->assertStringContainsString('id="dataPointDetails"', $html, 'Data point details not found');
        $this->assertStringContainsString('id="legendInteraction"', $html, 'Legend interaction not found');
    }

    /**
     * Test Mobile Responsiveness
     */
    public function testMobileResponsiveness()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test mobile elements
        $this->assertStringContainsString('id="mobileMenu"', $html, 'Mobile menu not found');
        $this->assertStringContainsString('data-touch-interaction', $html, 'Touch interaction not found');
        $this->assertStringContainsString('class="mobile-layout"', $html, 'Mobile layout not found');
        $this->assertStringContainsString('data-responsive-breakpoint', $html, 'Responsive breakpoint not found');
    }

    /**
     * Test Print and Export Options
     */
    public function testPrintAndExportOptions()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test print and export elements
        $this->assertStringContainsString('id="exportFormat"', $html, 'Export format not found');
        $this->assertStringContainsString('id="printLayout"', $html, 'Print layout not found');
        $this->assertStringContainsString('id="fileNaming"', $html, 'File naming not found');
        $this->assertStringContainsString('id="exportScheduling"', $html, 'Export scheduling not found');
    }

    /**
     * Test Search Results
     */
    public function testSearchResults()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test search result elements
        $this->assertStringContainsString('id="searchResults"', $html, 'Search results not found');
        $this->assertStringContainsString('id="searchSuggestions"', $html, 'Search suggestions not found');
        $this->assertStringContainsString('id="searchHistory"', $html, 'Search history not found');
        $this->assertStringContainsString('id="advancedSearch"', $html, 'Advanced search not found');
    }

    /**
     * Test User Feedback
     */
    public function testUserFeedback()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test feedback elements
        $this->assertStringContainsString('id="ratingSystem"', $html, 'Rating system not found');
        $this->assertStringContainsString('id="feedbackForm"', $html, 'Feedback form not found');
        $this->assertStringContainsString('id="surveyInterface"', $html, 'Survey interface not found');
        $this->assertStringContainsString('id="commentSection"', $html, 'Comment section not found');
    }

    /**
     * Test System Status
     */
    public function testSystemStatus()
    {
        $html = file_get_contents($this->baseUrl);
        
        // Test system status elements
        $this->assertStringContainsString('id="systemHealth"', $html, 'System health not found');
        $this->assertStringContainsString('id="performanceMetrics"', $html, 'Performance metrics not found');
        $this->assertStringContainsString('id="errorLogs"', $html, 'Error logs not found');
        $this->assertStringContainsString('id="maintenanceStatus"', $html, 'Maintenance status not found');
    }
} 