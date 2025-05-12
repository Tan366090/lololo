// API endpoints
const API_URL = '/qlnhansu_V3/backend/src/api/v1/attendance.php';

// DOM Elements
const attendanceList = document.getElementById('attendanceList');
const dateFilter = document.getElementById('dateFilter');
const statusFilter = document.getElementById('statusFilter');
const addAttendanceButton = document.getElementById('addAttendanceButton');

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

// Load attendance data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadAttendanceData();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    addAttendanceButton.addEventListener('click', showAddAttendanceModal);
}

// Load attendance data
async function loadAttendanceData() {
    try {
        const response = await fetch(`${API_URL}?action=getAll`);
        const data = await response.json();
        
        if (data.success) {
            displayAttendanceData(data.data);
        } else {
            showError('Lỗi khi tải dữ liệu chấm công');
        }
    } catch (error) {
        showError('Lỗi kết nối server');
        console.error('Error:', error);
    }
}

// Display attendance data in table
function displayAttendanceData(attendanceData) {
    attendanceList.innerHTML = '';
    
    attendanceData.forEach(record => {
        const row = document.createElement('tr');
        
        // Format date
        const date = new Date(record.attendance_date);
        const formattedDate = date.toLocaleDateString('vi-VN');
        
        // Get status badge class
        const statusClass = getStatusBadgeClass(record.attendance_symbol);
        
        row.innerHTML = `
            <td>${record.employee_id} - ${record.full_name}</td>
            <td>${formattedDate}</td>
            <td><span class="status-badge ${statusClass}">${getStatusText(record.attendance_symbol)}</span></td>
            <td>${record.notes || '-'}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editAttendance(${record.attendance_id})">
                    <ion-icon name="create-outline"></ion-icon>
                </button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete(${record.attendance_id})">
                    <ion-icon name="trash-outline"></ion-icon>
                </button>
            </td>
        `;
        
        attendanceList.appendChild(row);
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
    const date = dateFilter.value;
    const status = statusFilter.value;
    
    // Filter logic will be implemented here
    // For now, just reload all data
    loadAttendanceData();
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