// API endpoints
const API_URL = '/qlnhansu_V3/backend/src/api/v1/attendance.php';

// DOM Elements
const attendanceList = document.getElementById('attendanceList');
const dateFilter = document.getElementById('dateFilter');
const statusFilter = document.getElementById('statusFilter');
const addAttendanceButton = document.getElementById('addAttendanceButton');

// Add filter elements
const filterContainer = document.createElement('div');
filterContainer.className = 'filter-container';
filterContainer.innerHTML = `
    <div class="row">
        <div class="col-md-3 mb-3">
            <input type="text" id="employeeIdFilter" class="form-control" placeholder="Mã nhân viên">
        </div>
        <div class="col-md-3 mb-3">
            <input type="text" id="nameFilter" class="form-control" placeholder="Họ tên">
        </div>
        <div class="col-md-2 mb-3">
            <input type="date" id="dateFilter" class="form-control">
        </div>
        <div class="col-md-2 mb-3">
            <select id="statusFilter" class="form-control">
                <option value="">Tất cả trạng thái</option>
                <option value="P">Có mặt</option>
                <option value="A">Vắng mặt</option>
                <option value="L">Nghỉ phép</option>
                <option value="WFH">Làm việc từ xa</option>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <button id="resetFilter" class="btn btn-secondary w-100">
                <ion-icon name="refresh-outline"></ion-icon> Reset
            </button>
        </div>
    </div>
`;

// Insert filter container before the table
const tableContainer = document.querySelector('.table-container');
tableContainer.parentNode.insertBefore(filterContainer, tableContainer);

// Get filter elements
const employeeIdFilter = document.getElementById('employeeIdFilter');
const nameFilter = document.getElementById('nameFilter');

// Add CSS styles
const style = document.createElement('style');
style.textContent = `
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
    }
    
    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        width: 90%;
        max-width: 500px;
        position: relative;
    }
    
    .modal h2 {
        margin: 0 0 20px 0;
        color: #212529;
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #495057;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #0056b3;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(0,86,179,0.25);
    }
    
    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    /* Button Styles */
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 25px;
    }
    
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background-color: #0056b3;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #004494;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    
    /* Status Badge Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .status-present {
        background-color: #28a745;
        color: white;
    }
    
    .status-absent {
        background-color: #dc3545;
        color: white;
    }
    
    .status-late {
        background-color: #ffc107;
        color: #212529;
    }
    
    .status-wfh {
        background-color: #17a2b8;
        color: white;
    }
    
    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-top: 20px;
    }
    
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }
    
    .table th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        padding: 15px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #dee2e6;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .modal-content {
            margin: 5% auto;
            width: 95%;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
`;
document.head.appendChild(style);

// Set default date to today
dateFilter.valueAsDate = new Date();

// Add sorting state
let currentSort = {
    column: null,
    direction: 'asc'
};

// Load attendance data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadAttendanceData();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    addAttendanceButton.addEventListener('click', showAddAttendanceModal);
    
    // Add filter event listeners
    employeeIdFilter.addEventListener('input', applyFilters);
    nameFilter.addEventListener('input', applyFilters);
    dateFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    
    // Reset filter button
    document.getElementById('resetFilter').addEventListener('click', resetFilters);
}

// Reset all filters
function resetFilters() {
    employeeIdFilter.value = '';
    nameFilter.value = '';
    dateFilter.valueAsDate = new Date();
    statusFilter.value = '';
    loadAttendanceData();
}

// Load attendance data
async function loadAttendanceData() {
    try {
        const response = await fetch(`${API_URL}?action=getAll`);
        const data = await response.json();
        if (data.success && Array.isArray(data.data)) {
            displayAttendanceData(data.data);
        } else {
            showError('Lỗi khi tải dữ liệu chấm công');
            console.error('API response:', data);
        }
    } catch (error) {
        showError('Lỗi kết nối server');
        console.error('Error:', error);
    }
}

// Display attendance data in table
function displayAttendanceData(attendanceData) {
    const attendanceList = document.getElementById('attendanceList');
    attendanceList.innerHTML = '';
    console.log('DATA:', attendanceData); // Log kiểm tra dữ liệu
    if (!Array.isArray(attendanceData) || attendanceData.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="8" style="text-align:center;">Không có dữ liệu chấm công</td>`;
        attendanceList.appendChild(row);
        return;
    }
    attendanceData.forEach(record => {
        const date = new Date(record.attendance_date);
        const formattedDate = date.toLocaleDateString('vi-VN');
        const formatTime = (time) => time ? time.substring(0, 5) : '-';
        const statusClass = getStatusBadgeClass(record.attendance_symbol);
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${record.employee_id}</td>
            <td>${record.full_name}</td>
            <td>${formattedDate}</td>
            <td>${formatTime(record.check_in_time)}</td>
            <td>${formatTime(record.check_out_time)}</td>
            <td><span class="status-badge ${statusClass}">${getStatusText(record.attendance_symbol)}</span></td>
            <td>${record.notes ? record.notes : '-'}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editAttendance(${record.attendance_id})"><ion-icon name="create-outline"></ion-icon></button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete(${record.attendance_id})"><ion-icon name="trash-outline"></ion-icon></button>
            </td>
        `;
        attendanceList.appendChild(row);
    });
    // Add sort event listeners
    setupSortListeners();
}

function setupSortListeners() {
    const sortButtons = document.querySelectorAll('.sort-btn');
    sortButtons.forEach(button => {
        button.addEventListener('click', () => {
            const column = button.dataset.column;
            const direction = button.dataset.direction;
            sortTable(column, direction);
        });
    });
}

function sortTable(column, direction) {
    const rows = Array.from(attendanceList.getElementsByTagName('tr')).slice(1); // Skip header row
    const sortedRows = rows.sort((a, b) => {
        const aValue = a.children[getColumnIndex(column)].textContent;
        const bValue = b.children[getColumnIndex(column)].textContent;
        if (column === 'date') {
            return direction === 'asc' 
                ? new Date(aValue) - new Date(bValue)
                : new Date(bValue) - new Date(aValue);
        }
        if (column === 'check_in' || column === 'check_out') {
            const aTime = aValue === '-' ? '00:00' : aValue;
            const bTime = bValue === '-' ? '00:00' : bValue;
            return direction === 'asc'
                ? aTime.localeCompare(bTime)
                : bTime.localeCompare(aTime);
        }
        return direction === 'asc'
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });
    while (attendanceList.children.length > 1) {
        attendanceList.removeChild(attendanceList.lastChild);
    }
    sortedRows.forEach(row => attendanceList.appendChild(row));
    currentSort = { column, direction };
    updateSortButtonStyles();
}

function getColumnIndex(column) {
    switch (column) {
        case 'employee_id': return 0;
        case 'full_name': return 1;
        case 'date': return 2;
        case 'check_in': return 3;
        case 'check_out': return 4;
        case 'status': return 5;
        case 'notes': return 6;
        default: return 0;
    }
}

function updateSortButtonStyles() {
    const sortButtons = document.querySelectorAll('.sort-btn');
    sortButtons.forEach(button => {
        const column = button.dataset.column;
        const direction = button.dataset.direction;
        if (column === currentSort.column && direction === currentSort.direction) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
}

// Get status badge class
function getStatusBadgeClass(symbol) {
    switch (symbol) {
        case 'P':
            return 'status-present';
        case 'A':
            return 'status-absent';
        case 'L':
            return 'status-late';
        case 'WFH':
            return 'status-wfh';
        default:
            return '';
    }
}

// Get status text
function getStatusText(symbol) {
    switch (symbol) {
        case 'P':
            return 'Có mặt';
        case 'A':
            return 'Vắng mặt';
        case 'L':
            return 'Nghỉ phép';
        case 'WFH':
            return 'Làm việc từ xa';
        default:
            return symbol;
    }
}

// Apply filters
function applyFilters() {
    const employeeId = employeeIdFilter.value.toLowerCase();
    const name = nameFilter.value.toLowerCase();
    const date = dateFilter.value;
    const status = statusFilter.value;
    
    const rows = attendanceList.getElementsByTagName('tr');
    
    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        if (cells.length === 0) continue;
        
        const employeeInfo = cells[0].textContent.toLowerCase();
        const rowDate = cells[2].textContent;
        const rowStatus = cells[5].textContent;
        
        const matchEmployeeId = employeeInfo.includes(employeeId);
        const matchName = employeeInfo.includes(name);
        const matchDate = !date || rowDate === new Date(date).toLocaleDateString('vi-VN');
        const matchStatus = !status || rowStatus === getStatusText(status);
        
        row.style.display = matchEmployeeId && matchName && matchDate && matchStatus ? '' : 'none';
    }
}

// Show add attendance modal
function showAddAttendanceModal() {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    const title = modal.querySelector('h2');
    
    // Reset form
    form.reset();
    
    // Set modal title
    title.textContent = 'Thêm chấm công mới';
    
    // Show modal
    modal.style.display = 'block';
    
    // Handle form submission
    form.onsubmit = async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = {
            employee_id: formData.get('employeeId'),
            attendance_date: formData.get('attendanceDate'),
            attendance_symbol: formData.get('attendanceSymbol'),
            notes: formData.get('notes')
        };
        
        try {
            const response = await fetch(`${API_URL}?action=add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess('Thêm chấm công thành công');
                modal.style.display = 'none';
                loadAttendanceData();
            } else {
                showError(result.message);
            }
        } catch (error) {
            showError('Lỗi khi thêm chấm công');
            console.error('Error:', error);
        }
    };
}

// Edit attendance
async function editAttendance(id) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    const title = modal.querySelector('h2');
    
    try {
        // Get attendance data
        const response = await fetch(`${API_URL}?action=getByEmployee&id=${id}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            const record = data.data[0];
            
            // Set form values
            form.employeeId.value = record.employee_id;
            form.attendanceDate.value = record.attendance_date;
            form.attendanceSymbol.value = record.attendance_symbol;
            form.notes.value = record.notes || '';
            
            // Set modal title
            title.textContent = 'Sửa thông tin chấm công';
            
            // Show modal
            modal.style.display = 'block';
            
            // Handle form submission
            form.onsubmit = async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const updateData = {
                    attendance_id: id,
                    employee_id: formData.get('employeeId'),
                    attendance_date: formData.get('attendanceDate'),
                    attendance_symbol: formData.get('attendanceSymbol'),
                    notes: formData.get('notes')
                };
                
                try {
                    const updateResponse = await fetch(`${API_URL}?action=update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(updateData)
                    });
                    
                    const result = await updateResponse.json();
                    
                    if (result.success) {
                        showSuccess('Cập nhật chấm công thành công');
                        modal.style.display = 'none';
                        loadAttendanceData();
                    } else {
                        showError(result.message);
                    }
                } catch (error) {
                    showError('Lỗi khi cập nhật chấm công');
                    console.error('Error:', error);
                }
            };
        } else {
            showError('Không tìm thấy thông tin chấm công');
        }
    } catch (error) {
        showError('Lỗi khi tải thông tin chấm công');
        console.error('Error:', error);
    }
}

// Close modal
function closeModal() {
    const modal = document.getElementById('editModal');
    modal.style.display = 'none';
}

// Confirm delete
function confirmDelete(id) {
    const modal = document.getElementById('deleteModal');
    const confirmButton = document.getElementById('confirmDeleteButton');
    const cancelButton = document.getElementById('cancelDeleteButton');
    
    modal.style.display = 'block';
    
    confirmButton.onclick = async () => {
        try {
            const response = await fetch(`${API_URL}?action=delete&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                showSuccess('Xóa bản ghi chấm công thành công');
                loadAttendanceData();
            } else {
                showError(data.message);
            }
        } catch (error) {
            showError('Lỗi khi xóa bản ghi chấm công');
            console.error('Error:', error);
        }
        
        modal.style.display = 'none';
    };
    
    cancelButton.onclick = () => {
        modal.style.display = 'none';
    };
}

// Show success message
function showSuccess(message) {
    // Implementation will be added
    alert(message);
}

// Show error message
function showError(message) {
    // Implementation will be added
    alert(message);
} 