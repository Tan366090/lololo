console.log('Payroll.js script loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    console.log('Template element:', document.getElementById('payrollRowTemplate'));
});

// Loading Overlay Implementation
const loadingOverlay = {
    overlay: null,
    init() {
        this.overlay = document.createElement('div');
        this.overlay.id = 'loadingOverlay';
        this.overlay.className = 'loading-overlay';
        this.overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text mt-2">Đang tải...</div>
            </div>
        `;
        document.body.appendChild(this.overlay);

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.8);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .loading-overlay.show {
                opacity: 1;
            }

            .loading-spinner {
                text-align: center;
            }

            .loading-text {
                color: #2c3e50;
                font-weight: 500;
                margin-top: 10px;
            }

            .spinner-border {
                width: 3rem;
                height: 3rem;
            }
        `;
        document.head.appendChild(style);
    },
    show() {
        if (!this.overlay) this.init();
        this.overlay.classList.add('show');
        this.overlay.style.display = 'flex';
    },
    hide() {
        if (!this.overlay) return;
        this.overlay.classList.remove('show');
        setTimeout(() => {
            this.overlay.style.display = 'none';
        }, 300);
    }
};

// Initialize loading overlay
loadingOverlay.init();

// Add Payroll Details Modal
const payrollDetailsModal = document.createElement('div');
payrollDetailsModal.id = 'payrollDetailsModal';
payrollDetailsModal.className = 'modal fade';
payrollDetailsModal.setAttribute('tabindex', '-1');
payrollDetailsModal.setAttribute('aria-labelledby', 'payrollDetailsModalLabel');
payrollDetailsModal.setAttribute('aria-hidden', 'true');
payrollDetailsModal.innerHTML = `
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payrollDetailsModalLabel">Chi tiết phiếu lương</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
`;
document.body.appendChild(payrollDetailsModal);

// Add styles for payroll details modal
const modalStyle = document.createElement('style');
modalStyle.textContent = `
    .modal-dialog-scrollable .modal-content {
        max-height: 90vh;
    }

    .modal-dialog-scrollable .modal-body {
        overflow-y: auto;
        padding: 1rem;
    }

    .payroll-details {
        padding: 15px;
    }

    .payroll-details .section {
        margin-bottom: 25px;
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .payroll-details .section h4 {
        color: #2c3e50;
        font-size: 1.2rem;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e9ecef;
    }

    .payroll-details .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .payroll-details .info-item {
        display: flex;
        flex-direction: column;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .payroll-details .info-item label {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .payroll-details .info-item span {
        color: #2c3e50;
        font-weight: 500;
    }

    .payroll-details .info-item.full-width {
        grid-column: 1 / -1;
    }

    .payroll-details .badge {
        font-size: 0.85em;
        padding: 5px 10px;
    }

    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }

    .btn-close {
        padding: 0.5rem;
        margin: -0.5rem -0.5rem -0.5rem auto;
    }

    .btn-close:hover {
        background-color: rgba(0,0,0,0.1);
    }

    @media (max-width: 768px) {
        .payroll-details .info-grid {
            grid-template-columns: 1fr;
        }
        
        .modal-dialog {
            margin: 0.5rem;
        }
    }
`;
document.head.appendChild(modalStyle);

// Global functions
function showLoading() {
    loadingOverlay.show();
}

function hideLoading() {
    loadingOverlay.hide();
}

// API Object
const api = {
    departments: {
        getAll: async () => {
            try {
                const response = await fetch('/qlnhansu_V3/backend/src/api/departments.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const text = await response.text();
                
                // Try to find valid JSON in the response
                let jsonData = null;
                try {
                    jsonData = JSON.parse(text);
                } catch (e) {
                    const jsonMatches = text.match(/\{[\s\S]*?\}/g);
                    if (jsonMatches && jsonMatches.length > 0) {
                        for (const match of jsonMatches) {
                            try {
                                jsonData = JSON.parse(match);
                                if (jsonData && typeof jsonData === 'object') {
                                    break;
                                }
                            } catch (parseError) {
                                continue;
                            }
                        }
                    }
                }
                
                if (!jsonData) {
                    throw new Error('No valid JSON found in response');
                }
                
                return jsonData;
            } catch (error) {
                console.error('Error fetching departments:', error);
                throw error;
            }
        }
    },
    payroll: {
        getYears: async () => {
            try {
                const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=years');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const text = await response.text();
                
                // Log the raw response for debugging
                console.log('Raw years response:', text);
                
                // Try to find valid JSON in the response
                let jsonData = null;
                try {
                    jsonData = JSON.parse(text);
                } catch (e) {
                    const jsonMatches = text.match(/\{[\s\S]*?\}/g);
                    if (jsonMatches && jsonMatches.length > 0) {
                        for (const match of jsonMatches) {
                            try {
                                jsonData = JSON.parse(match);
                                if (jsonData && typeof jsonData === 'object') {
                                    break;
                                }
                            } catch (parseError) {
                                continue;
                            }
                        }
                    }
                }
                
                if (!jsonData) {
                    throw new Error('No valid JSON found in response');
                }
                
                if (!jsonData.success) {
                    throw new Error(jsonData.message || 'Failed to get years');
                }

                return jsonData.data || [];
            } catch (error) {
                console.error('Error fetching years:', error);
                throw new Error('Failed to get years: ' + error.message);
            }
        },
        getList: async (params) => {
            try {
                const queryString = new URLSearchParams(params).toString();
                const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?${queryString}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get the response text first
                const text = await response.text();
                
                // Log the raw response for debugging
                console.log('Raw payroll list response:', text);
                
                // Try to find valid JSON in the response
                let jsonData = null;
                try {
                    jsonData = JSON.parse(text);
                } catch (e) {
                    const jsonMatches = text.match(/\{[\s\S]*?\}/g);
                    if (jsonMatches && jsonMatches.length > 0) {
                        for (const match of jsonMatches) {
                            try {
                                jsonData = JSON.parse(match);
                                if (jsonData && typeof jsonData === 'object') {
                                    break;
                                }
                            } catch (parseError) {
                                continue;
                            }
                        }
                    }
                }
                
                if (!jsonData) {
                    throw new Error('No valid JSON found in response');
                }
                
                // Check if the response contains error messages
                if (text.includes('<br />') || text.includes('<b>')) {
                    console.warn('Response contains HTML error messages:', text);
                }
                
                return jsonData;
            } catch (error) {
                console.error('Error fetching payroll list:', error);
                throw error;
            }
        },
        // Lấy danh sách phụ cấp
        getAllowances: async () => {
            try {
                const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=allowances');
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('Error fetching allowances:', error);
                throw error;
            }
        },

        // Lấy danh sách thưởng
        getBonuses: async () => {
            try {
                const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=bonuses');
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('Error fetching bonuses:', error);
                throw error;
            }
        },

        // Lấy danh sách khấu trừ
        getDeductions: async () => {
            try {
                const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=deductions');
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('Error fetching deductions:', error);
                throw error;
            }
        },

        // Lấy kỳ lương
        getPeriods: async () => {
            try {
                const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=periods');
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('Error fetching periods:', error);
                throw error;
            }
        },

        add: async (data) => {
            try {
                const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new TypeError("API response was not JSON");
                }
                return await response.json();
            } catch (error) {
                console.error('Error adding payroll:', error);
                throw error;
            }
        },
        update: async (id, data) => {
            try {
                const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?id=${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new TypeError("API response was not JSON");
                }
                return await response.json();
            } catch (error) {
                console.error('Error updating payroll:', error);
                throw error;
            }
        },
        // Xóa phiếu lương
        delete: async (id) => {
            try {
                const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=delete&id=${id}`, {
                    method: 'DELETE'
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('Error deleting payroll:', error);
                throw error;
            }
        }
    }
};

// DOM Elements
const searchInput = document.getElementById('searchInput');
const departmentFilter = document.getElementById('departmentFilter');
const monthFilter = document.getElementById('monthFilter');
const yearFilter = document.getElementById('yearFilter');
const payrollTableBody = document.getElementById('payrollTableBody');
const pagination = document.getElementById('pagination');
const addPayrollBtn = document.getElementById('addPayrollBtn');
const calculatePayrollBtn = document.getElementById('calculatePayrollBtn');
const exportBtn = document.getElementById('exportBtn');
const reloadBtn = document.getElementById('reloadBtn');
const addPayrollModal = document.getElementById('addPayrollModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const addPayrollForm = document.getElementById('addPayrollForm');
const cancelBtn = document.getElementById('cancelBtn');
const searchEmployeeBtn = document.getElementById('searchEmployeeBtn');
const employeeCodeInput = document.getElementById('employeeCode');
const employeeNameInput = document.getElementById('employeeName');
const departmentInput = document.getElementById('department');
const positionInput = document.getElementById('position');

// State variables
let currentPage = 1;
let itemsPerPage = 10;
let totalPages = 1;
let currentPayrollId = null;
let payrollData = [];
let monthlySalaryChart = null;
let departmentSalaryChart = null;
let salaryTrendChart = null;
let salaryComponentChart = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initializeElements();
    loadDepartments();
    loadPayrollData();
    loadFilters();
    initializeCharts();
});

// Initialize DOM elements and event listeners
function initializeElements() {
    // Initialize search and filter elements
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }
    if (departmentFilter) {
        departmentFilter.addEventListener('change', handleFilter);
    }
    if (monthFilter) {
        monthFilter.addEventListener('change', handleFilter);
    }
    if (yearFilter) {
        yearFilter.addEventListener('change', handleFilter);
    }

    // Initialize button elements
    if (addPayrollBtn) {
        addPayrollBtn.addEventListener('click', () => {
            showAddPayrollModal();
            handleSalaryCalculations();
        });
    }
    if (calculatePayrollBtn) {
        calculatePayrollBtn.addEventListener('click', handleCalculatePayroll);
    }
    if (exportBtn) {
        exportBtn.addEventListener('click', handleExport);
    }
    if (reloadBtn) {
        reloadBtn.addEventListener('click', handleReload);
    }
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', hideAddPayrollModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideAddPayrollModal);
    }

    // Initialize form elements
    if (addPayrollForm) {
        addPayrollForm.addEventListener('submit', handleAddPayroll);
    }

    // Initialize modal close buttons
    const closeButtons = document.querySelectorAll('.close');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    if (searchEmployeeBtn) {
        searchEmployeeBtn.addEventListener('click', handleSearchEmployee);
    }

    // Add event listener for employee code input
    if (employeeCodeInput) {
        employeeCodeInput.addEventListener('input', debounce(handleSearchEmployee, 500));
    }
}

// Load payroll data with error handling
async function loadPayrollData() {
    try {
        showLoading();
        const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load payroll data');
        }

        // Extract payroll data and pagination info
        const payrollData = data.data.items || [];
        const totalPages = data.data.pagination?.total_pages || 1;
        const totalItems = data.data.pagination?.total || 0;

        // Render the table with the data
        renderPayrollTable(payrollData);
        
        // Update pagination
        renderPagination(totalItems);

        // Update dashboard cards
        updateDashboardCards(payrollData);

        // Update all charts
        createMonthlySalaryChart(payrollData);
        createDepartmentSalaryChart(payrollData);
        createSalaryTrendChart(payrollData);
        createSalaryComponentChart(payrollData);

    } catch (error) {
        console.error('Error loading payroll data:', error);
        showError('Failed to load payroll data: ' + error.message);
    } finally {
        hideLoading();
    }
}

// Table Rendering
function renderPayrollTable(payroll) {
    console.log('Rendering Payroll Table with data:', payroll);
    
    const tableBody = document.getElementById('payrollTableBody');
    const template = document.getElementById('payrollRowTemplate');
    
    console.log('Template element:', template);
    console.log('Template content:', template?.content);
    
    if (!tableBody || !template) {
        console.error('Required elements not found:', { tableBody, template });
        return;
    }

    // Clear existing rows
    tableBody.innerHTML = '';
    
    if (!payroll || payroll.length === 0) {
        console.log('No payroll data to display');
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.style.display = 'table-row';
        }
        return;
    }

    // Hide no results row if it exists
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        noResultsRow.style.display = 'none';
    }

    // Render each payroll row
    payroll.forEach((payroll, index) => {
        console.log('Rendering payroll row:', payroll);
        
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');
        
        // Fill in the data
        row.querySelector('.stt').textContent = index + 1;
        row.querySelector('.employee-code').textContent = payroll.employee.code || '';
        row.querySelector('.employee-name').textContent = payroll.employee.name || '';
        row.querySelector('.department').textContent = payroll.employee.department || '';
        row.querySelector('.payroll-period').textContent = payroll.period.month || '';
        row.querySelector('.basic-salary').textContent = payroll.salary.base || '0';
        row.querySelector('.allowance').textContent = payroll.salary.allowances || '0';
        row.querySelector('.bonus').textContent = payroll.salary.bonuses || '0';
        row.querySelector('.deduction').textContent = payroll.salary.deductions || '0';
        row.querySelector('.net-salary').textContent = payroll.salary.net || '0';
        row.querySelector('.created-by').textContent = payroll.created_by.username || '';
        row.querySelector('.status').innerHTML = `<span class="badge ${getStatusBadgeClass(payroll.status.code)}">${payroll.status.text}</span>`;
        
        // Add event listeners for action buttons
        const viewBtn = row.querySelector('.view-btn');
        const editBtn = row.querySelector('.edit-btn');
        const deleteBtn = row.querySelector('.delete-btn');
        
        if (viewBtn) viewBtn.onclick = () => viewPayrollDetails(payroll.id);
        if (editBtn) editBtn.onclick = () => editPayroll(payroll.id);
        if (deleteBtn) deleteBtn.onclick = () => deletePayroll(payroll.id);
        
        tableBody.appendChild(row);
    });
}

function renderPagination(totalItems) {
    totalPages = Math.ceil(totalItems / itemsPerPage);
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    pagination.innerHTML = `
        <div class="custom-pagination">
            <button class="page-btn prev" id="prevPageBtn" ${currentPage === 1 ? 'disabled' : ''}>
                &larr; Previous page
            </button>
            <button class="page-btn next" id="nextPageBtn" ${currentPage === totalPages ? 'disabled' : ''}>
                Next page &rarr;
            </button>
            <div class="page-info">
                <input type="number" id="currentPageInput" min="1" max="${totalPages}" value="${currentPage}" style="width:48px;text-align:center;" />
                <span>of <span id="totalPages">${totalPages}</span></span>
                <button class="arrow-btn" id="arrowPrevBtn" ${currentPage === 1 ? 'disabled' : ''}>&lt;</button>
                <button class="arrow-btn" id="arrowNextBtn" ${currentPage === totalPages ? 'disabled' : ''}>&gt;</button>
            </div>
        </div>
    `;
    // Gán sự kiện
    document.getElementById('prevPageBtn').onclick = function() {
        if (currentPage > 1) {
            changePage(currentPage - 1);
        }
    };
    document.getElementById('nextPageBtn').onclick = function() {
        if (currentPage < totalPages) {
            changePage(currentPage + 1);
        }
    };
    document.getElementById('arrowPrevBtn').onclick = function() {
        if (currentPage > 1) {
            changePage(currentPage - 1);
        }
    };
    document.getElementById('arrowNextBtn').onclick = function() {
        if (currentPage < totalPages) {
            changePage(currentPage + 1);
        }
    };
    document.getElementById('currentPageInput').onchange = function() {
        let val = parseInt(this.value, 10);
        if (val >= 1 && val <= totalPages) {
            changePage(val);
        } else {
            this.value = currentPage;
        }
    };
}

// Helper Functions
function formatCurrency(value) {
    if (!value) return '0';
    
    // Nếu là input element
    if (value instanceof HTMLInputElement) {
        // Xóa tất cả ký tự không phải số
        let numValue = value.value.replace(/[^\d]/g, '');
        
        // Định dạng số với dấu phẩy ngăn cách hàng nghìn
        if (numValue !== '') {
            numValue = parseInt(numValue, 10).toLocaleString('vi-VN');
        }
        
        // Cập nhật giá trị input
        value.value = numValue;
        return numValue;
    }
    
    // Nếu là số hoặc chuỗi số
    if (typeof value === 'number' || (typeof value === 'string' && !isNaN(value))) {
        return parseInt(value, 10).toLocaleString('vi-VN');
    }
    
    // Nếu là undefined hoặc null
    return '0';
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'paid':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function getStatusText(status) {
    const statusMap = {
        'pending': 'Đang chờ',
        'approved': 'Đã duyệt',
        'rejected': 'Từ chối',
        'paid': 'Đã thanh toán'
    };
    return statusMap[status.toLowerCase()] || status;
}

function getApproverName(approvalInfo) {
    if (!approvalInfo) return '-';
    const approvals = approvalInfo.split('|');
    const lastApproval = approvals[approvals.length - 1];
    const [level, status, name] = lastApproval.split(':');
    return status === 'approved' ? name : '-';
}

// Event Handlers
function handleSearch() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const rows = payrollTableBody.getElementsByTagName('tr');
    let hasResults = false;

    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        let rowVisible = false;

        // Skip the template row
        if (row.id === 'payrollRowTemplate') continue;

        // Search through all cells in the row
        for (let cell of cells) {
            const cellText = cell.textContent.toLowerCase();
            if (cellText.includes(searchTerm)) {
                rowVisible = true;
                break;
            }
        }

        // Show/hide row based on search result
        row.style.display = rowVisible ? '' : 'none';
        if (rowVisible) hasResults = true;
    }

    // Show/hide no results message
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        noResultsRow.style.display = hasResults ? 'none' : '';
    }

    // Show notification if no results found
    if (!hasResults && searchTerm !== '') {
        showError('Không tìm thấy kết quả phù hợp với từ khóa: ' + searchTerm);
    }

    // Reset to first page when searching
    if (currentPage !== 1) {
        currentPage = 1;
        loadPayrollData();
    }
}

function handleFilter() {
    currentPage = 1;
    loadPayrollData();
}

function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadPayrollData();
}

function showAddPayrollModal() {
    addPayrollModal.style.display = 'block';
    loadSalaryComponents();
    loadPayrollPeriods();
}

function hideAddPayrollModal() {
    addPayrollModal.style.display = 'none';
    addPayrollForm.reset();
}

async function loadSalaryComponents() {
    try {
        showLoading();
        
        // Load allowances
        const allowanceResponse = await api.payroll.getAllowances();
        if (allowanceResponse.success) {
            const allowanceSelect = document.getElementById('allowance');
            allowanceSelect.innerHTML = '<option value="">Chọn phụ cấp</option>';
            // Kiểm tra và chuyển đổi data thành mảng nếu cần
            const allowances = Array.isArray(allowanceResponse.data) ? allowanceResponse.data : 
                             allowanceResponse.data.items || [];
            allowances.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.name} (${formatCurrency(item.amount)})`;
                allowanceSelect.appendChild(option);
            });
        }

        // Load bonuses
        const bonusResponse = await api.payroll.getBonuses();
        if (bonusResponse.success) {
            const bonusSelect = document.getElementById('bonus');
            bonusSelect.innerHTML = '<option value="">Chọn thưởng</option>';
            // Kiểm tra và chuyển đổi data thành mảng nếu cần
            const bonuses = Array.isArray(bonusResponse.data) ? bonusResponse.data : 
                          bonusResponse.data.items || [];
            bonuses.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.name} (${formatCurrency(item.amount)})`;
                bonusSelect.appendChild(option);
            });
        }

        // Load deductions
        const deductionResponse = await api.payroll.getDeductions();
        if (deductionResponse.success) {
            const deductionSelect = document.getElementById('deduction');
            deductionSelect.innerHTML = '<option value="">Chọn khấu trừ</option>';
            // Kiểm tra và chuyển đổi data thành mảng nếu cần
            const deductions = Array.isArray(deductionResponse.data) ? deductionResponse.data : 
                             deductionResponse.data.items || [];
            deductions.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.name} (${formatCurrency(item.amount)})`;
                deductionSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading salary components:', error);
        showError('Không thể tải danh sách thành phần lương');
    } finally {
        hideLoading();
    }
}

async function loadPayrollPeriods() {
    try {
        showLoading();
        const response = await api.payroll.getPeriods();
        
        if (response.success) {
            const periodSelect = document.getElementById('payrollPeriod');
            periodSelect.innerHTML = '<option value="">Chọn kỳ lương</option>';
            
            // Kiểm tra và chuyển đổi data thành mảng nếu cần
            const periods = Array.isArray(response.data) ? response.data : 
                          response.data.items || [];
            
            periods.forEach(period => {
                const option = document.createElement('option');
                option.value = period.id;
                if (period.month) {
                    option.textContent = `Tháng ${period.month}`;
                } else if (period.start && period.end) {
                    option.textContent = `${formatDate(period.start)} - ${formatDate(period.end)}`;
                } else {
                    option.textContent = 'Kỳ lương không xác định';
                }
                periodSelect.appendChild(option);
            });
        } else {
            showError('Không thể tải danh sách kỳ lương');
        }
    } catch (error) {
        console.error('Error loading payroll periods:', error);
        showError('Lỗi khi tải danh sách kỳ lương');
    } finally {
        hideLoading();
    }
}
// Hàm chuyển đổi chuỗi số tiền kiểu Việt Nam thành số nguyên
function parseVnCurrency(str) {
    if (!str) return 0;
    return parseInt(str.replace(/[^\d]/g, ''), 10) || 0;
}

// Hàm validate form
function validatePayrollForm() {
    const form = document.getElementById('addPayrollForm');
    if (!form) return false;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Validate số tiền
    const basicSalary = parseVnCurrency(document.getElementById('basicSalary').value);
    if (isNaN(basicSalary) || basicSalary < 0) {
        document.getElementById('basicSalary').classList.add('is-invalid');
        isValid = false;
    }

    return isValid;
}

// Cập nhật hàm handleAddPayroll
async function handleAddPayroll(event) {
    event.preventDefault();
    
    if (!validatePayrollForm()) {
        showError('Vui lòng điền đầy đủ thông tin bắt buộc');
        return;
    }

    showLoading();

    try {
        const formData = new FormData(addPayrollForm);
        const data = {
            employee_id: formData.get('employee_id'),
            period_id: formData.get('payrollPeriod'),
            basicSalary: parseVnCurrency(formData.get('basicSalary')),
            allowancesTotal: parseVnCurrency(formData.get('allowance')),
            bonusesTotal: parseVnCurrency(formData.get('bonus')),
            deductionsTotal: parseVnCurrency(formData.get('deduction')),
            netSalary: calculateNetSalary(),
            notes: formData.get('notes')
        };
        console.log('Dữ liệu gửi lên backend:', data);

        const response = await api.payroll.add(data);

        if (response.success) {
            showSuccess('Thêm phiếu lương thành công');
            hideAddPayrollModal();
            loadPayrollData();
        } else {
            showError(response.message || 'Thêm phiếu lương thất bại');
        }
    } catch (error) {
        showError('Lỗi khi thêm phiếu lương');
        console.error(error);
    } finally {
        hideLoading();
    }
}

async function handleExport() {
    try {
        showLoading();
        
        // Lấy giá trị từ các trường tìm kiếm
        const search = document.getElementById('searchInput').value;
        const department = document.getElementById('departmentFilter').value;
        const month = document.getElementById('monthFilter').value;
        const year = document.getElementById('yearFilter').value;

        // Tạo FormData để gửi dữ liệu
        const formData = new FormData();
        formData.append('search', search);
        formData.append('department', department);
        formData.append('month', month);
        formData.append('year', year);

        // Gọi API xuất Excel
        const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=export', {
            method: 'POST',
            body: formData
        });

        // Kiểm tra content type của response
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            // Nếu là JSON thì có lỗi
            const errorData = await response.json();
            throw new Error(errorData.error || 'Lỗi khi xuất file Excel');
        }

        if (!response.ok) {
            throw new Error('Lỗi khi xuất file Excel');
        }

        // Lấy tên file từ header
        const contentDisposition = response.headers.get('content-disposition');
        let filename = 'payroll_export.xlsx';
        if (contentDisposition) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(contentDisposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }

        // Tạo blob và download file
        const blob = await response.blob();
        if (blob.size === 0) {
            throw new Error('File Excel trống');
        }

        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        showSuccess('Xuất file Excel thành công');
    } catch (error) {
        console.error('Export error:', error);
        showError(error.message || 'Lỗi khi xuất file Excel');
    } finally {
        hideLoading();
    }
}

function handleReload() {
    showInfo('Đang tải lại dữ liệu...');
    currentPage = 1;
    searchInput.value = '';
    departmentFilter.value = '';
    monthFilter.value = '';
    yearFilter.value = '';
    loadPayrollData();
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// UI Functions
function showLoading() {
    console.log('Showing loading overlay...');
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
        // Add show class for animation
        loadingOverlay.classList.add('show');
        console.log('Loading overlay displayed');
    } else {
        console.error('Loading overlay element not found');
    }
}

function hideLoading() {
    console.log('Hiding loading overlay...');
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        // Remove show class first for animation
        loadingOverlay.classList.remove('show');
        // Wait for animation to complete before hiding
        setTimeout(() => {
            loadingOverlay.style.display = 'none';
            console.log('Loading overlay hidden');
        }, 300);
    } else {
        console.error('Loading overlay element not found');
    }
}

function showError(message) {
    const notificationContainer = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = 'alert alert-danger alert-dismissible fade show';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="pic/delete-button.png" alt="Error" style="width: 24px; height: 24px; margin-right: 10px;">
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    notificationContainer.appendChild(notification);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function showSuccess(message) {
    const notificationContainer = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="pic/check.png" alt="Success" style="width: 24px; height: 24px; margin-right: 10px;">
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    notificationContainer.appendChild(notification);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function showWarning(message) {
    const notificationContainer = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = 'alert alert-warning alert-dismissible fade show';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="pic/warning.png" alt="Warning" style="width: 24px; height: 24px; margin-right: 10px;">
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    notificationContainer.appendChild(notification);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function showInfo(message) {
    const notificationContainer = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = 'alert alert-info alert-dismissible fade show';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-2"></i>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    notificationContainer.appendChild(notification);
    
    // Tự động ẩn sau 5 giây
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// CRUD Operations
async function viewPayrollDetails(payrollId) {
    showLoading();
    try {
        const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=details&id=${payrollId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Có lỗi xảy ra khi xem chi tiết phiếu lương');
        }
        
        const payroll = data.data;
        if (!payroll) {
            throw new Error('Không tìm thấy thông tin phiếu lương');
        }

        // Format số tiền
        const formatMoney = (amount) => {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        };

        // Format ngày tháng
        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleDateString('vi-VN');
        };

        // Cập nhật nội dung modal
        const modalContent = `
            <div class="payroll-details">
                <div class="section">
                    <h4>Thông tin nhân viên</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Mã nhân viên:</label>
                            <span>${payroll.employee.code || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Họ tên:</label>
                            <span>${payroll.employee.name || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Phòng ban:</label>
                            <span>${payroll.employee.department || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Chức vụ:</label>
                            <span>${payroll.employee.position || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h4>Thông tin lương</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Kỳ lương:</label>
                            <span>${formatDate(payroll.period.start)} - ${formatDate(payroll.period.end)}</span>
                        </div>
                        <div class="info-item">
                            <label>Lương cơ bản:</label>
                            <span>${formatMoney(payroll.salary.base)}</span>
                        </div>
                        <div class="info-item">
                            <label>Phụ cấp:</label>
                            <span>${formatMoney(payroll.salary.allowances)}</span>
                        </div>
                        <div class="info-item">
                            <label>Thưởng:</label>
                            <span>${formatMoney(payroll.salary.bonuses)}</span>
                        </div>
                        <div class="info-item">
                            <label>Khấu trừ:</label>
                            <span>${formatMoney(payroll.salary.deductions)}</span>
                        </div>
                        <div class="info-item">
                            <label>Thực lĩnh:</label>
                            <span>${formatMoney(payroll.salary.net)}</span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h4>Thông tin thanh toán</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Ngày thanh toán:</label>
                            <span>${payroll.payment.date ? formatDate(payroll.payment.date) : 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Phương thức:</label>
                            <span>${payroll.payment.method || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Mã tham chiếu:</label>
                            <span>${payroll.payment.reference || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h4>Thông tin khác</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Trạng thái:</label>
                            <span class="badge ${getStatusBadgeClass(payroll.status.code)}">${payroll.status.text}</span>
                        </div>
                        <div class="info-item">
                            <label>Người tạo:</label>
                            <span>${payroll.created_by.username || 'N/A'}</span>
                        </div>
                        <div class="info-item full-width">
                            <label>Ghi chú:</label>
                            <span>${payroll.notes || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Lấy modal element
        const modal = document.getElementById('payrollDetailsModal');
        if (!modal) {
            throw new Error('Không tìm thấy modal chi tiết phiếu lương');
        }

        // Cập nhật nội dung modal
        const modalBody = modal.querySelector('.modal-body');
        if (modalBody) {
            modalBody.innerHTML = modalContent;
        }

        // Khởi tạo Bootstrap Modal với các options
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: true,
            focus: true
        });
        
        // Thêm event listeners
        modal.addEventListener('hidden.bs.modal', function () {
            // Cleanup khi modal đóng
            modalBody.innerHTML = '';
        });

        // Thêm event listener cho nút đóng
        const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                bsModal.hide();
            });
        });

        // Thêm event listener cho click bên ngoài modal
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                bsModal.hide();
            }
        });

        // Hiển thị modal
        bsModal.show();

    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
    } finally {
        hideLoading();
    }
}

// Thêm hàm helper để lấy class cho badge trạng thái
function getStatusBadgeClass(status) {
    const statusClasses = {
        'pending': 'bg-warning',
        'approved': 'bg-success',
        'rejected': 'bg-danger',
        'paid': 'bg-info'
    };
    return statusClasses[status] || 'bg-secondary';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function updateDashboardCards(data) {
    if (!data || !Array.isArray(data)) {
        console.warn('Invalid statistics data:', data);
        return;
    }

    try {
        // Lấy tháng và năm hiện tại
        const currentDate = new Date();
        const currentMonth = currentDate.getMonth() + 1;
        const currentYear = currentDate.getFullYear();

        // Lọc dữ liệu theo tháng và năm hiện tại
        const currentMonthData = data.filter(payroll => {
            if (!payroll.period || !payroll.period.month) return false;
            const [month, year] = payroll.period.month.split('/');
            return parseInt(month) === currentMonth && parseInt(year) === currentYear;
        });

        // Tính toán tổng lương
        const totalSalary = currentMonthData.reduce((sum, payroll) => {
            if (!payroll.salary) return sum;
            const netSalary = convertCurrencyToNumber(payroll.salary.net || '0');
            return sum + netSalary;
        }, 0);

        // Tính toán tổng thưởng
        const totalBonus = currentMonthData.reduce((sum, payroll) => {
            if (!payroll.salary) return sum;
            const bonus = convertCurrencyToNumber(payroll.salary.bonuses || '0');
            return sum + bonus;
        }, 0);

        // Tính lương trung bình
        const averageSalary = currentMonthData.length > 0 ? Math.round(totalSalary / currentMonthData.length) : 0;

        // Cập nhật các card với animation
        animateValue('totalSalary', 0, totalSalary, 1000);
        animateValue('totalBonus', 0, totalBonus, 1000);
        animateValue('averageSalary', 0, averageSalary, 1000);
        
        // Cập nhật tổng số phiếu lương của tháng hiện tại
        const totalPayrollsElement = document.getElementById('totalPayrolls');
        if (totalPayrollsElement) {
            // Sử dụng số lượng phiếu lương của tháng hiện tại
            totalPayrollsElement.textContent = currentMonthData.length;
        }

        // Log để debug
        console.log('Monthly Statistics for May 2025:', {
            totalSalary,
            totalBonus,
            averageSalary,
            totalPayrolls: currentMonthData.length,
            payrolls: currentMonthData.map(p => ({
                id: p.id,
                employee: p.employee.name,
                net_salary: p.salary.net,
                period: p.period.month
            }))
        });

    } catch (error) {
        console.error('Error updating dashboard cards:', error);
    }
}

// Thêm hàm animation cho số
function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const animate = () => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            // Nếu là tổng số phiếu lương thì chỉ hiển thị số, không định dạng tiền tệ
            if (elementId === 'totalPayrolls') {
                element.textContent = end;
            } else {
                element.textContent = formatCurrency(end);
            }
        } else {
            if (elementId === 'totalPayrolls') {
                element.textContent = Math.round(current);
            } else {
                element.textContent = formatCurrency(Math.round(current));
            }
            requestAnimationFrame(animate);
        }
    };

    requestAnimationFrame(animate);
}

// Hàm chuyển đổi chuỗi tiền tệ thành số
function convertCurrencyToNumber(currencyString) {
    if (!currencyString) return 0;
    
    // Loại bỏ tất cả ký tự không phải số
    const numericString = currencyString.replace(/[^\d]/g, '');
    
    // Chuyển đổi thành số
    const number = parseInt(numericString, 10);
    
    return isNaN(number) ? 0 : number;
}

async function editPayroll(id) {
    showLoading();
    try {
        // Lấy thông tin chi tiết phiếu lương
        const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=details&id=${id}`);
        const data = await response.json();

        if (data.success) {
            // Lưu ID phiếu lương
            document.getElementById('editPayrollId').value = id;
            
            // Điền dữ liệu vào form
            populateForm(data.data);
            
            // Hiển thị modal
            document.getElementById('editPayrollModal').style.display = 'block';
            
            // Khởi tạo các input tiền tệ
            initializeMoneyInputs();
            
            // Tính toán tổng tiền
            calculateTotals();
        } else {
            showError(data.message || 'Không thể tải thông tin phiếu lương');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Lỗi khi tải thông tin phiếu lương');
    } finally {
        hideLoading();
    }
}

async function deletePayroll(id) {
    try {
        // Lấy thông tin chi tiết phiếu lương
        const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=details&id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            showError(data.message);
            return;
        }

        const payroll = data.data;
        
        // Tạo thông báo chi tiết
        const confirmMessage = `
            Bạn có chắc chắn muốn xóa phiếu lương này?
            
            Thông tin phiếu lương:
            - Nhân viên: ${payroll.employee.name} (${payroll.employee.code})
            - Phòng ban: ${payroll.employee.department}
            - Kỳ lương: ${payroll.period.start} đến ${payroll.period.end}
            - Lương cơ bản: ${payroll.salary.base} VNĐ
            - Phụ cấp: ${payroll.salary.allowances} VNĐ
            - Thưởng: ${payroll.salary.bonuses} VNĐ
            - Khấu trừ: ${payroll.salary.deductions} VNĐ
            - Thực lĩnh: ${payroll.salary.net} VNĐ
        `;

        if (confirm(confirmMessage)) {
            showLoading();
            const deleteResponse = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            const deleteData = await deleteResponse.json();
            hideLoading();

            if (deleteData.success) {
                showSuccess('Xóa phiếu lương thành công');
                loadPayrollData(); // Tải lại danh sách
            } else {
                showError(deleteData.message);
            }
        }
    } catch (error) {
        hideLoading();
        showError('Có lỗi xảy ra khi xóa phiếu lương');
        console.error('Error:', error);
    }
}

// Hàm kiểm tra quyền admin
async function checkAdminRole() {
    try {
        const response = await fetch('/qlnhansu_V3/backend/src/public/admin/api/auth.php?action=checkRole');
        const data = await response.json();
        return data.isAdmin === true;
    } catch (error) {
        console.error('Error checking admin role:', error);
        return false;
    }
}

async function handleCalculatePayroll() {
    if (!confirm('Bạn có chắc chắn muốn tính lương tự động cho tất cả nhân viên?')) {
        showWarning('Đã hủy thao tác tính lương tự động');
        return;
    }

    showLoading();
    try {
        const response = await fetch('/qlnhansu_V3/backend/src/public/api/payroll.php?action=calculate', {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showSuccess('Tính lương tự động thành công');
            loadPayrollData();
        } else {
            showError(data.message || 'Tính lương tự động thất bại');
        }
    } catch (error) {
        showError('Lỗi khi tính lương tự động');
    } finally {
        hideLoading();
    }
}

// Hàm tạo biểu đồ lương theo tháng
function createMonthlySalaryChart(data) {
    console.log('Starting createMonthlySalaryChart with data:', data);

    if (!data || !Array.isArray(data)) {
        console.warn('Invalid data for chart:', data);
        return;
    }

    const ctx = document.getElementById('monthlySalaryChart');
    if (!ctx) {
        console.error('Canvas element not found');
        return;
    }

    // Xử lý dữ liệu theo thời gian
    const yearFilter = document.getElementById('chartYearFilter').value;
    const monthRange = parseInt(document.getElementById('chartMonthRange').value);
    
    console.log('Chart filters:', { yearFilter, monthRange });

    // Lọc dữ liệu theo năm và khoảng thời gian
    const filteredData = data.filter(payroll => {
        if (!payroll.period || !payroll.period.month) {
            console.warn('Invalid payroll period:', payroll);
            return false;
        }

        const [month, year] = payroll.period.month.split('/');
        console.log('Processing payroll:', { month, year, payroll });
        
        return year === yearFilter;
    });

    console.log('Filtered data:', filteredData);

    // Chuẩn bị dữ liệu cho biểu đồ
    const monthlyData = {};
    filteredData.forEach(payroll => {
        const monthKey = payroll.period.month;
        
        if (!monthlyData[monthKey]) {
            monthlyData[monthKey] = {
                totalSalary: 0,
                totalBonus: 0,
                count: 0
            };
        }
        
        // Đảm bảo các giá trị là số
        const salary = convertCurrencyToNumber(payroll.salary?.net || 0);
        const bonus = convertCurrencyToNumber(payroll.salary?.bonuses || 0);
        
        monthlyData[monthKey].totalSalary += salary;
        monthlyData[monthKey].totalBonus += bonus;
        monthlyData[monthKey].count++;
    });

    console.log('Monthly aggregated data:', monthlyData);

    // Sắp xếp dữ liệu theo tháng
    const sortedMonths = Object.keys(monthlyData).sort((a, b) => {
        const [monthA, yearA] = a.split('/');
        const [monthB, yearB] = b.split('/');
        return (yearA - yearB) || (monthA - monthB);
    });

    console.log('Sorted months:', sortedMonths);

    // Chuẩn bị dữ liệu cho biểu đồ
    const labels = sortedMonths;
    const salaryData = sortedMonths.map(month => monthlyData[month].totalSalary);
    const bonusData = sortedMonths.map(month => monthlyData[month].totalBonus);
    const avgSalaryData = sortedMonths.map(month => 
        Math.round(monthlyData[month].totalSalary / monthlyData[month].count)
    );

    console.log('Chart data:', {
        labels,
        salaryData,
        bonusData,
        avgSalaryData
    });

    // Nếu đã có biểu đồ cũ, xóa nó
    if (monthlySalaryChart) {
        monthlySalaryChart.destroy();
    }

    try {
        // Tạo biểu đồ mới
        monthlySalaryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Tổng lương',
                        data: salaryData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Tổng thưởng',
                        data: bonusData,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Lương trung bình',
                        data: avgSalaryData,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        type: 'line'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Biểu đồ lương theo tháng',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2c3e50',
                        bodyColor: '#495057',
                        borderColor: '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += formatCurrency(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
        console.log('Chart created successfully');
    } catch (error) {
        console.error('Error creating chart:', error);
    }
}

// Thêm event listeners cho các bộ lọc
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    const yearFilter = document.getElementById('chartYearFilter');
    const monthRange = document.getElementById('chartMonthRange');

    if (yearFilter) {
        console.log('Year filter found');
        yearFilter.addEventListener('change', function() {
            console.log('Year filter changed:', this.value);
            loadPayrollData();
        });
    } else {
        console.warn('Year filter not found');
    }

    if (monthRange) {
        console.log('Month range found');
        monthRange.addEventListener('change', function() {
            console.log('Month range changed:', this.value);
            loadPayrollData();
        });
    } else {
        console.warn('Month range not found');
    }
});

// Hàm tạo biểu đồ phân bố lương theo phòng ban
function createDepartmentSalaryChart(data) {
    if (!data || !Array.isArray(data)) {
        console.warn('Invalid data for department chart:', data);
        return;
    }

    const ctx = document.getElementById('departmentSalaryChart');
    if (!ctx) {
        console.error('Department chart canvas not found');
        return;
    }

    const yearFilter = document.getElementById('departmentChartYear').value;
    const monthFilter = document.getElementById('departmentChartMonth').value;

    // Lọc dữ liệu theo năm và tháng
    const filteredData = data.filter(payroll => {
        const [month, year] = payroll.period.month.split('/');
        return year === yearFilter && (!monthFilter || month === monthFilter);
    });

    // Tổng hợp dữ liệu theo phòng ban
    const departmentData = {};
    filteredData.forEach(payroll => {
        const dept = payroll.employee.department;
        if (!departmentData[dept]) {
            departmentData[dept] = {
                totalSalary: 0,
                totalBonus: 0,
                employeeCount: 0
            };
        }
        
        departmentData[dept].totalSalary += convertCurrencyToNumber(payroll.salary.net);
        departmentData[dept].totalBonus += convertCurrencyToNumber(payroll.salary.bonuses);
        departmentData[dept].employeeCount++;
    });

    // Tính lương trung bình cho mỗi phòng ban
    const departments = Object.keys(departmentData);
    const avgSalaries = departments.map(dept => 
        Math.round(departmentData[dept].totalSalary / departmentData[dept].employeeCount)
    );
    const totalBonuses = departments.map(dept => departmentData[dept].totalBonus);
    const employeeCounts = departments.map(dept => departmentData[dept].employeeCount);

    // Nếu đã có biểu đồ cũ, xóa nó
    if (departmentSalaryChart) {
        departmentSalaryChart.destroy();
    }

    // Tạo biểu đồ mới
    departmentSalaryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: departments,
            datasets: [
                {
                    label: 'Lương trung bình',
                    data: avgSalaries,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Tổng thưởng',
                    data: totalBonuses,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Số nhân viên',
                    data: employeeCounts,
                    type: 'line',
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Phân bố lương theo phòng ban',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.yAxisID === 'y1') {
                                label += context.parsed.y + ' người';
                            } else {
                                label += formatCurrency(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Lương (VNĐ)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Số nhân viên'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Hàm tạo biểu đồ xu hướng lương
function createSalaryTrendChart(data) {
    if (!data || !Array.isArray(data)) {
        console.warn('Invalid data for trend chart:', data);
        return;
    }

    const ctx = document.getElementById('salaryTrendChart');
    if (!ctx) {
        console.error('Trend chart canvas not found');
        return;
    }

    const yearFilter = document.getElementById('trendChartYear').value;
    const typeFilter = document.getElementById('trendChartType').value;

    // Lọc dữ liệu theo năm
    const filteredData = data.filter(payroll => {
        const [month, year] = payroll.period.month.split('/');
        return year === yearFilter;
    });

    // Tổng hợp dữ liệu theo tháng
    const monthlyData = {};
    filteredData.forEach(payroll => {
        const month = payroll.period.month;
        if (!monthlyData[month]) {
            monthlyData[month] = {
                baseSalary: 0,
                allowances: 0,
                bonuses: 0,
                count: 0
            };
        }
        
        monthlyData[month].baseSalary += convertCurrencyToNumber(payroll.salary.base);
        monthlyData[month].allowances += convertCurrencyToNumber(payroll.salary.allowances);
        monthlyData[month].bonuses += convertCurrencyToNumber(payroll.salary.bonuses);
        monthlyData[month].count++;
    });

    // Sắp xếp tháng
    const months = Object.keys(monthlyData).sort((a, b) => {
        const [monthA, yearA] = a.split('/');
        const [monthB, yearB] = b.split('/');
        return (yearA - yearB) || (monthA - monthB);
    });

    // Chuẩn bị dữ liệu cho biểu đồ
    const datasets = [];
    if (typeFilter === 'all' || typeFilter === 'base') {
        datasets.push({
            label: 'Lương cơ bản',
            data: months.map(month => monthlyData[month].baseSalary / monthlyData[month].count),
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            fill: true
        });
    }
    if (typeFilter === 'all' || typeFilter === 'allowance') {
        datasets.push({
            label: 'Phụ cấp',
            data: months.map(month => monthlyData[month].allowances / monthlyData[month].count),
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            fill: true
        });
    }
    if (typeFilter === 'all' || typeFilter === 'bonus') {
        datasets.push({
            label: 'Thưởng',
            data: months.map(month => monthlyData[month].bonuses / monthlyData[month].count),
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: true
        });
    }

    // Nếu đã có biểu đồ cũ, xóa nó
    if (salaryTrendChart) {
        salaryTrendChart.destroy();
    }

    // Tạo biểu đồ mới
    salaryTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Xu hướng lương theo thời gian',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += formatCurrency(context.parsed.y);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Lương (VNĐ)'
                    }
                }
            }
        }
    });
}

// Hàm tạo biểu đồ phân tích thành phần lương
function createSalaryComponentChart(data) {
    if (!data || !Array.isArray(data)) {
        console.warn('Invalid data for component chart:', data);
        return;
    }

    const ctx = document.getElementById('salaryComponentChart');
    if (!ctx) {
        console.error('Component chart canvas not found');
        return;
    }

    const yearFilter = document.getElementById('componentChartYear').value;
    const monthFilter = document.getElementById('componentChartMonth').value;

    // Lọc dữ liệu theo năm và tháng
    const filteredData = data.filter(payroll => {
        const [month, year] = payroll.period.month.split('/');
        return year === yearFilter && (!monthFilter || month === monthFilter);
    });

    // Tính tổng các thành phần lương
    const totals = filteredData.reduce((acc, payroll) => {
        acc.baseSalary += convertCurrencyToNumber(payroll.salary.base);
        acc.allowances += convertCurrencyToNumber(payroll.salary.allowances);
        acc.bonuses += convertCurrencyToNumber(payroll.salary.bonuses);
        acc.deductions += convertCurrencyToNumber(payroll.salary.deductions);
        return acc;
    }, {
        baseSalary: 0,
        allowances: 0,
        bonuses: 0,
        deductions: 0
    });

    // Nếu đã có biểu đồ cũ, xóa nó
    if (salaryComponentChart) {
        salaryComponentChart.destroy();
    }

    // Tạo biểu đồ mới
    salaryComponentChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Lương cơ bản', 'Phụ cấp', 'Thưởng', 'Khấu trừ'],
            datasets: [{
                data: [
                    totals.baseSalary,
                    totals.allowances,
                    totals.bonuses,
                    totals.deductions
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(255, 159, 64, 0.5)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Phân tích thành phần lương',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Thêm event listeners cho các bộ lọc mới
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners cho biểu đồ phòng ban
    const departmentYearFilter = document.getElementById('departmentChartYear');
    const departmentMonthFilter = document.getElementById('departmentChartMonth');
    
    if (departmentYearFilter) {
        departmentYearFilter.addEventListener('change', loadPayrollData);
    }
    if (departmentMonthFilter) {
        departmentMonthFilter.addEventListener('change', loadPayrollData);
    }

    // Event listeners cho biểu đồ xu hướng
    const trendYearFilter = document.getElementById('trendChartYear');
    const trendTypeFilter = document.getElementById('trendChartType');
    
    if (trendYearFilter) {
        trendYearFilter.addEventListener('change', loadPayrollData);
    }
    if (trendTypeFilter) {
        trendTypeFilter.addEventListener('change', loadPayrollData);
    }

    // Event listeners cho biểu đồ thành phần
    const componentYearFilter = document.getElementById('componentChartYear');
    const componentMonthFilter = document.getElementById('componentChartMonth');
    
    if (componentYearFilter) {
        componentYearFilter.addEventListener('change', loadPayrollData);
    }
    if (componentMonthFilter) {
        componentMonthFilter.addEventListener('change', loadPayrollData);
    }
});

async function loadFilters() {
    try {
        // Load departments
        const deptResponse = await api.departments.getAll();
        const departmentSelect = document.getElementById("departmentFilter");
        departmentSelect.innerHTML = '<option value="">Tất cả phòng ban</option>';
        deptResponse.data.forEach(dept => {
            const option = document.createElement("option");
            option.value = dept.department_id;
            option.textContent = dept.name;
            departmentSelect.appendChild(option);
        });

        // Load years from payroll data
        const yearResponse = await api.payroll.getYears();
        const yearSelect = document.getElementById("yearFilter");
        yearSelect.innerHTML = '<option value="">Chọn năm</option>';
        
        // Check if yearResponse is an array or has a data property
        const years = Array.isArray(yearResponse) ? yearResponse : (yearResponse.data || []);
        
        years.forEach(year => {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        });

        // Set current year as default
        const currentYear = new Date().getFullYear();
        yearSelect.value = currentYear;
    } catch (error) {
        console.error("Error loading filters:", error);
        showError("Không thể tải dữ liệu bộ lọc");
    }
}

async function handleSearchEmployee() {
    const employeeCode = employeeCodeInput.value.trim();
    
    if (!employeeCode) {
        showError('Vui lòng nhập mã nhân viên');
        return;
    }

    showLoading();

    try {
        const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=searchEmployee&employeeCode=${encodeURIComponent(employeeCode)}`);
        const data = await response.json();
        
        if (data.success) {
            const { employee, payrollHistory } = data.data;
            
            // Cập nhật thông tin nhân viên
            employeeNameInput.value = employee.name || employee.email;
            departmentInput.value = employee.department_name || '';
            positionInput.value = employee.position_name || '';
            
            // Hiển thị section lịch sử lương
            const payrollHistorySection = document.getElementById('payrollHistorySection');
            const payrollHistoryContainer = document.getElementById('payrollHistoryContainer');
            
            if (payrollHistory && payrollHistory.length > 0) {
                // Tạo bảng lịch sử lương
                const table = document.createElement('table');
                table.className = 'table table-striped table-bordered';
                table.innerHTML = `
                    <thead>
                        <tr>
                            <th>Kỳ lương</th>
                            <th>Lương cơ bản</th>
                            <th>Phụ cấp</th>
                            <th>Thưởng</th>
                            <th>Khấu trừ</th>
                            <th>Thực lĩnh</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="payrollHistoryBody">
                        ${payrollHistory.slice(0, 3).map(payroll => `
                            <tr>
                                <td>${formatDate(payroll.pay_period_start)} - ${formatDate(payroll.pay_period_end)}</td>
                                <td class="text-end">${formatCurrency(payroll.basic_salary)}</td>
                                <td class="text-end">${formatCurrency(payroll.allowances)}</td>
                                <td class="text-end">${formatCurrency(payroll.bonuses)}</td>
                                <td class="text-end">${formatCurrency(payroll.deductions)}</td>
                                <td class="text-end">${formatCurrency(payroll.net_salary)}</td>
                                <td class="text-center">
                                    <span class="badge ${getStatusBadgeClass(payroll.status)}">
                                        ${getStatusText(payroll.status)}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                `;
                
                // Tạo nút xem thêm/thu gọn
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'text-center mt-3';
                
                if (payrollHistory.length > 3) {
                    buttonContainer.innerHTML = `
                        <button type="button" class="btn btn-link" id="togglePayrollHistory">
                            Xem thêm <i class="fas fa-chevron-down"></i>
                        </button>
                    `;
                }
                
                payrollHistoryContainer.innerHTML = '';
                payrollHistoryContainer.appendChild(table);
                payrollHistoryContainer.appendChild(buttonContainer);
                payrollHistorySection.style.display = 'block';

                // Thêm sự kiện cho nút xem thêm/thu gọn
                if (payrollHistory.length > 3) {
                    const toggleButton = document.getElementById('togglePayrollHistory');
                    let currentIndex = 3;
                    let isExpanded = false;

                    toggleButton.addEventListener('click', () => {
                        const tbody = document.getElementById('payrollHistoryBody');
                        
                        if (!isExpanded) {
                            // Xem thêm
                            const nextItems = payrollHistory.slice(currentIndex, currentIndex + 5);
                            if (nextItems.length > 0) {
                                nextItems.forEach(payroll => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${formatDate(payroll.pay_period_start)} - ${formatDate(payroll.pay_period_end)}</td>
                                        <td class="text-end">${formatCurrency(payroll.basic_salary)}</td>
                                        <td class="text-end">${formatCurrency(payroll.allowances)}</td>
                                        <td class="text-end">${formatCurrency(payroll.bonuses)}</td>
                                        <td class="text-end">${formatCurrency(payroll.deductions)}</td>
                                        <td class="text-end">${formatCurrency(payroll.net_salary)}</td>
                                        <td class="text-center">
                                            <span class="badge ${getStatusBadgeClass(payroll.status)}">
                                                ${getStatusText(payroll.status)}
                                            </span>
                                        </td>
                                    `;
                                    tbody.appendChild(row);
                                });
                                currentIndex += 5;
                                
                                // Cập nhật nút nếu đã hiển thị hết
                                if (currentIndex >= payrollHistory.length) {
                                    toggleButton.innerHTML = 'Thu gọn <i class="fas fa-chevron-up"></i>';
                                    isExpanded = true;
                                }
                            }
                        } else {
                            // Thu gọn
                            while (tbody.children.length > 3) {
                                tbody.removeChild(tbody.lastChild);
                            }
                            currentIndex = 3;
                            toggleButton.innerHTML = 'Xem thêm <i class="fas fa-chevron-down"></i>';
                            isExpanded = false;
                        }
                    });
                }
            } else {
                payrollHistoryContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nhân viên chưa có bản ghi lương nào
                    </div>
                `;
                payrollHistorySection.style.display = 'block';
            }
        } else {
            showError(data.message || 'Không tìm thấy thông tin nhân viên');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Lỗi khi tìm kiếm thông tin nhân viên');
    } finally {
        hideLoading();
    }
}
// Thêm CSS cho bảng lịch sử lương
const style = document.createElement('style');
style.textContent = `
    #payrollHistorySection {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    #payrollHistorySection h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #payrollHistorySection h4 i {
        color: #3498db;
    }

    #payrollHistoryContainer {
        margin-top: 10px;
    }

    #payrollHistoryContainer .table {
        margin-bottom: 0;
    }

    #payrollHistoryContainer .table th {
        background-color: #f1f1f1;
        font-weight: 600;
        text-align: center;
    }

    #payrollHistoryContainer .table td {
        vertical-align: middle;
    }

    #payrollHistoryContainer .badge {
        padding: 5px 10px;
        font-size: 0.85em;
    }

    #payrollHistoryContainer .alert {
        margin-bottom: 0;
    }

    .text-end {
        text-align: right !important;
    }

    .text-center {
        text-align: center !important;
    }

    #togglePayrollHistory {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    #togglePayrollHistory:hover {
        color: #2980b9;
        text-decoration: underline;
    }

    #togglePayrollHistory i {
        margin-left: 5px;
        transition: transform 0.3s ease;
    }

    #togglePayrollHistory:hover i {
        transform: translateY(2px);
    }
`;
document.head.appendChild(style);

// Xử lý tính toán lương
function handleSalaryCalculations() {
    // Lấy các input elements
    const basicSalaryInput = document.getElementById('basicSalary');
    const allowanceInput = document.getElementById('allowance');
    const bonusInput = document.getElementById('bonus');
    const deductionInput = document.getElementById('deduction');
    
    // Lấy các elements hiển thị kết quả
    const totalIncomeElement = document.getElementById('totalIncome');
    const totalDeductionsElement = document.getElementById('totalDeductions');
    const netSalaryElement = document.getElementById('netSalary');

    // Hàm xử lý input
    function handleInput(input) {
        if (!input) return;
        
        // Chỉ cho phép nhập số
        input.value = input.value.replace(/[^\d]/g, '');
        
        // Format giá trị
        formatCurrency(input);
        
        // Cập nhật hiển thị
        updateDisplay();
    }

    // Hàm cập nhật hiển thị
    function updateDisplay() {
        if (!totalIncomeElement || !totalDeductionsElement || !netSalaryElement) return;

        const basicSalary = getNumericValue(basicSalaryInput?.value || '0');
        const allowance = getNumericValue(allowanceInput?.value || '0');
        const bonus = getNumericValue(bonusInput?.value || '0');
        const deduction = getNumericValue(deductionInput?.value || '0');

        const totalIncome = basicSalary + allowance + bonus;
        const netSalary = totalIncome - deduction;

        totalIncomeElement.textContent = formatCurrency(totalIncome);
        totalDeductionsElement.textContent = formatCurrency(deduction);
        netSalaryElement.textContent = formatCurrency(netSalary);
    }

    // Thêm event listeners cho các input
    [basicSalaryInput, allowanceInput, bonusInput, deductionInput].forEach(input => {
        if (!input) return;

        // Remove existing event listeners
        input.removeEventListener('input', input._inputHandler);
        input.removeEventListener('focus', input._focusHandler);
        input.removeEventListener('blur', input._blurHandler);

        // Create new event handlers
        input._inputHandler = function() {
            handleInput(this);
        };
        input._focusHandler = function() {
            // Khi focus, hiển thị giá trị số không có định dạng
            this.value = this.value.replace(/[^\d]/g, '');
        };
        input._blurHandler = function() {
            formatCurrency(this);
            updateDisplay();
        };

        // Add new event listeners
        input.addEventListener('input', input._inputHandler);
        input.addEventListener('focus', input._focusHandler);
        input.addEventListener('blur', input._blurHandler);
    });

    // Initial update of display
    updateDisplay();
}

// Khởi tạo xử lý tính toán lương khi modal được mở
document.getElementById('addPayrollBtn')?.addEventListener('click', function() {
    handleSalaryCalculations();
});  

// Initialize charts
function initializeCharts() {
    // Initialize monthly salary chart
    const monthlySalaryCtx = document.getElementById('monthlySalaryChart');
    if (monthlySalaryCtx) {
        monthlySalaryChart = new Chart(monthlySalaryCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Tổng lương',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Tổng thưởng',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Biểu đồ lương theo tháng',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += formatCurrency(context.parsed.y);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize department salary chart
    const departmentSalaryCtx = document.getElementById('departmentSalaryChart');
    if (departmentSalaryCtx) {
        departmentSalaryChart = new Chart(departmentSalaryCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Lương trung bình',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Phân bố lương theo phòng ban',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += formatCurrency(context.parsed.y);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }
}

// API Functions
async function loadDepartments() {
    try {
        const response = await api.departments.getAll();
        if (response.success) {
            const departments = response.data;
            departmentFilter.innerHTML = '<option value="">Tất cả phòng ban</option>';
            departments.forEach(dept => {
                departmentFilter.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
            });
        }
    } catch (error) {
        showError('Không thể tải danh sách phòng ban');
    }
}

// Hàm lấy giá trị số từ chuỗi đã định dạng
function getNumericValue(formattedValue) {
    return parseInt(formattedValue.replace(/[^\d]/g, ''), 10) || 0;
}

// Hàm xử lý lỗi API
function handleApiError(error, defaultMessage) {
    console.error('API Error:', error);
    
    if (error.response) {
        // Server trả về response với status code ngoài range 2xx
        const errorData = error.response.data;
        showError(errorData.message || defaultMessage);
    } else if (error.request) {
        // Request được gửi nhưng không nhận được response
        showError('Không thể kết nối đến server');
    } else {
        // Có lỗi khi setting up request
        showError(defaultMessage);
    }
}

// Edit Payroll Functions
let originalPayrollData = null;
let allowanceDetails = [];
let bonusDetails = [];
let deductionDetails = [];

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Parse currency string to number
function parseCurrency(currencyString) {
    return parseInt(currencyString.replace(/[^\d-]/g, '')) || 0;
}

// Calculate totals
function calculateTotals() {
    const basicSalary = parseCurrency(document.getElementById('editBasicSalary').value);
    const allowance = parseCurrency(document.getElementById('editAllowance').value);
    const bonus = parseCurrency(document.getElementById('editBonus').value);
    const deduction = parseCurrency(document.getElementById('editDeduction').value);

    const totalIncome = basicSalary + allowance + bonus;
    const totalDeductions = deduction;
    const netSalary = totalIncome - totalDeductions;

    document.getElementById('editTotalIncome').textContent = formatCurrency(totalIncome);
    document.getElementById('editTotalDeductions').textContent = formatCurrency(totalDeductions);
    document.getElementById('editNetSalary').textContent = formatCurrency(netSalary);
}

// Initialize money input formatting
function initializeMoneyInputs() {
    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value);
                e.target.value = formatCurrency(value);
            }
            calculateTotals();
        });

        input.addEventListener('focus', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            e.target.value = value;
        });

        input.addEventListener('blur', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value);
                e.target.value = formatCurrency(value);
            }
        });
    });
}

// Load payroll details
async function loadPayrollDetails(id) {
    try {
        const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=details&id=${id}`);
        const data = await response.json();

        if (data.success) {
            originalPayrollData = data.data;
            populateForm(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        showNotification('error', 'Lỗi khi tải thông tin phiếu lương: ' + error.message);
    }
}

// Populate form with data
function populateForm(data) {
    // Employee info
    document.getElementById('editEmployeeCode').value = data.employee.code;
    document.getElementById('editEmployeeName').value = data.employee.name;
    document.getElementById('editDepartment').value = data.employee.department;
    document.getElementById('editPosition').value = data.employee.position;

    // Salary info
    document.getElementById('editBasicSalary').value = formatCurrency(data.salary.base);
    document.getElementById('editAllowance').value = formatCurrency(data.salary.allowances);
    document.getElementById('editBonus').value = formatCurrency(data.salary.bonuses);
    document.getElementById('editDeduction').value = formatCurrency(data.salary.deductions);

    // Payroll period
    const periodSelect = document.getElementById('editPayrollPeriod');
    const periodValue = `${data.period.start} - ${data.period.end}`;
    if (!Array.from(periodSelect.options).some(option => option.value === periodValue)) {
        const option = new Option(periodValue, periodValue);
        periodSelect.add(option);
    }
    periodSelect.value = periodValue;

    // Notes
    document.getElementById('editNotes').value = data.notes || '';

    // Calculate totals
    calculateTotals();
}

// Save draft
function saveDraft() {
    const formData = getFormData();
    localStorage.setItem('payrollDraft', JSON.stringify(formData));
    showNotification('success', 'Đã lưu nháp phiếu lương');
}

// Load draft
function loadDraft() {
    const draft = localStorage.getItem('payrollDraft');
    if (draft) {
        const formData = JSON.parse(draft);
        populateForm(formData);
        showNotification('info', 'Đã tải dữ liệu nháp');
    }
}

// Reset form
function resetForm() {
    if (originalPayrollData) {
        populateForm(originalPayrollData);
        showNotification('info', 'Đã khôi phục dữ liệu ban đầu');
    }
}

// Get form data
function getFormData() {
    // Lấy kỳ lương và tách thành ngày bắt đầu và kết thúc
    const periodValue = document.getElementById('editPayrollPeriod').value;
    let pay_period_start = '', pay_period_end = '';
    
    if (periodValue && periodValue.includes(' - ')) {
        [pay_period_start, pay_period_end] = periodValue.split(' - ');
        // Đảm bảo định dạng ngày tháng đúng
        pay_period_start = pay_period_start.trim();
        pay_period_end = pay_period_end.trim();
    } else {
        throw new Error('Vui lòng chọn kỳ lương');
    }

    // Lấy các giá trị lương
    const base = parseCurrency(document.getElementById('editBasicSalary').value);
    const allowances = parseCurrency(document.getElementById('editAllowance').value);
    const bonuses = parseCurrency(document.getElementById('editBonus').value);
    const deductions = parseCurrency(document.getElementById('editDeduction').value);

    // Tính toán tổng thu nhập và lương thực lĩnh
    const gross = base + allowances + bonuses;
    const net = gross - deductions;

    return {
        employee_id: document.getElementById('editEmployeeCode').value,
        pay_period_start: pay_period_start,
        pay_period_end: pay_period_end,
        work_days_payable: "22.0", // Mặc định 22 ngày làm việc
        base_salary_period: base.toString(),
        allowances_total: allowances.toString(),
        bonuses_total: bonuses.toString(),
        deductions_total: deductions.toString(),
        gross_salary: gross.toString(),
        tax_deduction: "0",
        insurance_deduction: "0",
        net_salary: net.toString(),
        currency: "VND",
        status: "pending",
        notes: document.getElementById('editNotes').value || null
    };
}

// Submit form
async function submitEditForm(e) {
    e.preventDefault();
    showLoading();

    try {
        const formData = getFormData();
        const payrollId = document.getElementById('editPayrollId').value;

        const response = await fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=update&id=${payrollId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showSuccess('Cập nhật phiếu lương thành công');
            document.getElementById('editPayrollModal').style.display = 'none';
            loadPayrollData(); // Refresh payroll list
        } else {
            showError(data.message || 'Cập nhật phiếu lương thất bại');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Lỗi khi cập nhật phiếu lương');
    } finally {
        hideLoading();
    }
}

// Initialize edit payroll functionality
function initializeEditPayroll() {
    const editForm = document.getElementById('editPayrollForm');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const resetFormBtn = document.getElementById('resetFormBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');

    editForm.addEventListener('submit', submitEditForm);
    saveDraftBtn.addEventListener('click', saveDraft);
    resetFormBtn.addEventListener('click', resetForm);
    cancelEditBtn.addEventListener('click', () => {
        document.getElementById('editPayrollModal').style.display = 'none';
    });

    initializeMoneyInputs();
}

// Add event listeners when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeEditPayroll();
});


