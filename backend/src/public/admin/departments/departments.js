// DOM Elements
const departmentTableBody = document.getElementById('departmentTableBody');
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const totalDepartments = document.getElementById('totalDepartments');
const totalEmployees = document.getElementById('totalEmployees');
const totalManagers = document.getElementById('totalManagers');
const addDepartmentBtn = document.getElementById('addDepartmentBtn');
const exportBtn = document.getElementById('exportBtn');
const departmentForm = document.getElementById('departmentForm');
const departmentModal = document.getElementById('departmentModal');
const cancelBtn = document.getElementById('cancelBtn');
const parentDepartmentSelect = document.getElementById('parentDepartment');
const departmentManagerSelect = document.getElementById('departmentManager');

// State
let departments = [];
let filteredDepartments = [];
let currentDepartmentId = null;
let isLoading = false;
let debounceTimer;
let departmentChart = null;
let departmentStatusChart = null;
let isBarChart = true;

// Fetch departments data with loading state
async function fetchDepartments() {
    if (isLoading) return;
    
    try {
        isLoading = true;
        showLoadingState();
        
        const response = await fetch('/qlnhansu_V3/backend/src/api/v1/departments.php?action=getAll');
        const result = await response.json();
        
        if (result.status === 'success') {
            departments = result.data;
            filteredDepartments = [...departments];
            updateDashboardStats();
            renderDepartments();
            updateParentDepartmentOptions();
            updateManagerOptions();
            
            // Đảm bảo Chart.js đã được tải
            if (typeof Chart !== 'undefined') {
                initializeCharts(departments);
            } else {
                console.warn('Chart.js chưa được tải. Biểu đồ sẽ không được hiển thị.');
            }
        } else {
            showToast('error', 'Không thể tải dữ liệu phòng ban');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi kết nối server');
    } finally {
        isLoading = false;
        hideLoadingState();
    }
}

// Show loading state
function showLoadingState() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.body.appendChild(loadingOverlay);
}

// Hide loading state
function hideLoadingState() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

// Update dashboard statistics with animations
function updateDashboardStats() {
    animateNumber(totalDepartments, departments.length);
    animateNumber(totalEmployees, departments.reduce((sum, dept) => sum + dept.employee_count, 0));
    animateNumber(totalManagers, departments.filter(dept => dept.manager.id).length);
}

// Animate number counting
function animateNumber(element, target) {
    const start = parseInt(element.textContent) || 0;
    const duration = 1000;
    const steps = 60;
    const increment = (target - start) / steps;
    let current = start;
    let step = 0;

    const timer = setInterval(() => {
        step++;
        current = Math.round(start + (increment * step));
        element.textContent = current.toLocaleString();

        if (step >= steps) {
            element.textContent = target.toLocaleString();
            clearInterval(timer);
        }
    }, duration / steps);
}

// Update parent department options
function updateParentDepartmentOptions() {
    parentDepartmentSelect.innerHTML = '<option value="">Không có</option>';
    
    departments
        .filter(dept => dept.id !== currentDepartmentId)
        .forEach(dept => {
            const option = document.createElement('option');
            option.value = dept.id;
            option.textContent = dept.name;
            parentDepartmentSelect.appendChild(option);
        });
}

// Update manager options
function updateManagerOptions() {
    departmentManagerSelect.innerHTML = '<option value="">Chọn quản lý</option>';
    
    // Fetch employees who can be managers
    fetch('/qlnhansu_V3/backend/src/api/employees.php?action=getPotentialManagers')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (result.success && Array.isArray(result.data)) {
                result.data.forEach(employee => {
                    const option = document.createElement('option');
                    option.value = employee.id;
                    option.textContent = `${employee.name} - ${employee.position_name}`;
                    departmentManagerSelect.appendChild(option);
                });
            } else {
                console.warn('Không thể lấy danh sách quản lý:', result.message);
                showToast('error', 'Không thể tải danh sách quản lý');
            }
        })
        .catch(error => {
            console.error('Error fetching managers:', error);
            showToast('error', 'Lỗi kết nối server. Vui lòng thử lại sau.');
            // Thêm một option mặc định khi có lỗi
            const option = document.createElement('option');
            option.value = "";
            option.textContent = "Không thể tải danh sách quản lý";
            departmentManagerSelect.appendChild(option);
        });
}

// Render departments table with sorting and pagination
function renderDepartments() {
    departmentTableBody.innerHTML = '';
    
    // Sort departments
    const sortBy = localStorage.getItem('departmentSortBy') || 'name';
    const sortOrder = localStorage.getItem('departmentSortOrder') || 'asc';
    
    filteredDepartments.sort((a, b) => {
        let comparison = 0;
        if (sortBy === 'name') {
            comparison = a.name.localeCompare(b.name);
        } else if (sortBy === 'employee_count') {
            comparison = a.employee_count - b.employee_count;
        } else if (sortBy === 'created_at') {
            comparison = new Date(a.created_at) - new Date(b.created_at);
        }
        return sortOrder === 'asc' ? comparison : -comparison;
    });
    
    // Pagination
    const itemsPerPage = 10;
    const currentPage = parseInt(localStorage.getItem('currentPage')) || 1;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedDepartments = filteredDepartments.slice(startIndex, endIndex);
    
    // Render rows
    paginatedDepartments.forEach((dept, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${startIndex + index + 1}</td>
            <td>${dept.id}</td>
            <td>${dept.name}</td>
            <td>${dept.manager.name || 'Chưa có'}</td>
            <td>${dept.employee_count}</td>
            <td>
                <span class="status-badge ${dept.status}">
                    ${dept.status === 'active' ? 'Đang hoạt động' : 'Không hoạt động'}
                </span>
            </td>
            <td>${dept.manager.position || 'Chưa có'}</td>
            <td>${dept.description || 'Chưa có'}</td>
            <td>${formatDate(dept.created_at)}</td>
            <td>${formatDate(dept.updated_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-view" onclick="viewDepartment(${dept.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-edit" onclick="editDepartment(${dept.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-delete" onclick="deleteDepartment(${dept.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        departmentTableBody.appendChild(row);
    });
    
    // Render pagination
    renderPagination();
}

// Render pagination controls
function renderPagination() {
    const itemsPerPage = 10;
    const totalPages = Math.ceil(filteredDepartments.length / itemsPerPage);
    const currentPage = parseInt(localStorage.getItem('currentPage')) || 1;
    
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <button class="page-link" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    pagination.appendChild(prevLi);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `
            <button class="page-link" onclick="changePage(${i})">${i}</button>
        `;
        pagination.appendChild(li);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <button class="page-link" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    pagination.appendChild(nextLi);
}

// Change page
function changePage(page) {
    localStorage.setItem('currentPage', page);
    renderDepartments();
}

// Smart search with debounce
function smartSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        
        filteredDepartments = departments.filter(dept => {
            const matchesSearch = 
                dept.name.toLowerCase().includes(searchTerm) ||
                (dept.description && dept.description.toLowerCase().includes(searchTerm)) ||
                (dept.manager.name && dept.manager.name.toLowerCase().includes(searchTerm)) ||
                dept.id.toString().includes(searchTerm);
                
            const matchesStatus = !statusValue || dept.status === statusValue;
            return matchesSearch && matchesStatus;
        });
        
        localStorage.setItem('currentPage', 1);
        renderDepartments();
    }, 300);
}

// Enhanced save department with validation and retry mechanism
async function saveDepartment(event) {
    event.preventDefault();
    
    const name = document.getElementById('departmentName').value.trim();
    const description = document.getElementById('departmentDescription').value.trim();
    const status = document.getElementById('departmentStatus').value;
    const parentId = document.getElementById('parentDepartment').value;
    const managerId = document.getElementById('departmentManager').value;
    
    // Validation
    if (!name) {
        showToast('error', 'Tên phòng ban không được để trống');
        return;
    }
    
    if (name.length < 3) {
        showToast('error', 'Tên phòng ban phải có ít nhất 3 ký tự');
        return;
    }
    
    if (name.length > 255) {
        showToast('error', 'Tên phòng ban không được vượt quá 255 ký tự');
        return;
    }
    
    // Check for duplicate names
    const isDuplicate = departments.some(dept => 
        dept.name.toLowerCase() === name.toLowerCase() && 
        dept.id !== currentDepartmentId
    );
    
    if (isDuplicate) {
        showToast('error', 'Tên phòng ban đã tồn tại');
        return;
    }
    
    // Validate status
    if (!status || !['active', 'inactive'].includes(status)) {
        showToast('error', 'Trạng thái phòng ban không hợp lệ');
        return;
    }
    
    const formData = {
        name,
        description,
        status: status, // Use the selected status
        parent_id: parentId || null,
        manager_id: managerId || null
    };

    // Add version if editing
    if (currentDepartmentId) {
        const currentDept = departments.find(d => d.id === currentDepartmentId);
        if (currentDept) {
            formData.version = currentDept.version;
        }
    }
    
    let retries = 3;
    while (retries > 0) {
        try {
            showLoadingState();
            
            const url = currentDepartmentId 
                ? `/qlnhansu_V3/backend/src/api/v1/departments.php?action=update&id=${currentDepartmentId}`
                : '/qlnhansu_V3/backend/src/api/v1/departments.php?action=create';
                
            const response = await fetch(url, {
                method: currentDepartmentId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('success', currentDepartmentId ? 'Cập nhật phòng ban thành công' : 'Thêm phòng ban thành công');
                departmentModal.style.display = 'none';
                await fetchDepartments(); // Refresh data
                break;
            } else {
                if (result.message.includes('Dữ liệu đã bị thay đổi')) {
                    // Refresh data and retry
                    await fetchDepartments();
                    const updatedDept = departments.find(d => d.id === currentDepartmentId);
                    if (updatedDept) {
                        formData.version = updatedDept.version;
                    }
                    retries--;
                    if (retries === 0) {
                        showToast('error', 'Không thể cập nhật do xung đột dữ liệu. Vui lòng thử lại.');
                    }
                    continue;
                }
                throw new Error(result.message || 'Có lỗi xảy ra');
            }
        } catch (error) {
            console.error('Error:', error);
            retries--;
            if (retries === 0) {
                showToast('error', 'Lỗi kết nối server. Vui lòng thử lại sau.');
            } else {
                await new Promise(resolve => setTimeout(resolve, 1000)); // Wait 1 second before retry
            }
        } finally {
            hideLoadingState();
        }
    }
}

// Enhanced delete with retry mechanism
async function deleteDepartment(id) {
    const dept = departments.find(d => d.id === id);
    if (!dept) {
        showToast('error', 'Không tìm thấy phòng ban');
        return;
    }
    
    // Check for dependencies
    if (dept.employee_count > 0) {
        showToast('error', 'Không thể xóa phòng ban vì còn nhân viên');
        return;
    }
    
    // Check for child departments
    const hasChildren = departments.some(d => d.parent_id === id);
    if (hasChildren) {
        showToast('error', 'Không thể xóa phòng ban đang có phòng ban con');
        return;
    }
    
    if (!confirm('Bạn có chắc chắn muốn xóa phòng ban này?')) return;
    
    let retries = 3;
    while (retries > 0) {
        try {
            showLoadingState();
            
            const response = await fetch(`/qlnhansu_V3/backend/src/api/v1/departments.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (result.success) {
                showToast('success', 'Xóa phòng ban thành công');
                await fetchDepartments(); // Refresh data
                break;
            } else {
                throw new Error(result.message || 'Không thể xóa phòng ban');
            }
        } catch (error) {
            console.error('Error:', error);
            retries--;
            if (retries === 0) {
                showToast('error', 'Lỗi kết nối server. Vui lòng thử lại sau.');
            } else {
                await new Promise(resolve => setTimeout(resolve, 1000)); // Wait 1 second before retry
            }
        } finally {
            hideLoadingState();
        }
    }
}

// Enhanced export with progress
async function exportToExcel() {
    try {
        // Check if XLSX is available
        if (typeof XLSX === 'undefined') {
            showToast('error', 'Thư viện xuất Excel chưa được tải. Vui lòng tải lại trang.');
            return;
        }

        showLoadingState();
        
        // Format data with better structure
        const data = filteredDepartments.map(dept => ({
            'Mã PB': dept.id,
            'Tên phòng ban': dept.name,
            'Trưởng phòng': dept.manager.name || 'Chưa có',
            'Số nhân viên': dept.employee_count,
            'Trạng thái': dept.status === 'active' ? 'Đang hoạt động' : 'Không hoạt động',
            'Mô tả': dept.description || 'Chưa có',
            'Ngày tạo': formatDate(dept.created_at),
            'Cập nhật lần cuối': formatDate(dept.updated_at)
        }));

        // Create workbook
        const workbook = XLSX.utils.book_new();
        
        // Create worksheet
        const worksheet = XLSX.utils.json_to_sheet(data);

        // Add title row
        const title = "DANH SÁCH PHÒNG BAN";
        const subtitle = `Ngày xuất: ${formatDate(new Date())}`;
        const titleRow = [title];
        const subtitleRow = [subtitle];
        const emptyRow = [""];
        
        // Insert title rows at the beginning
        XLSX.utils.sheet_add_aoa(worksheet, [titleRow, subtitleRow, emptyRow], { origin: "A1" });
        
        // Merge cells for title
        worksheet['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: 7 } }, // Merge title
            { s: { r: 1, c: 0 }, e: { r: 1, c: 7 } }  // Merge subtitle
        ];

        // Style configuration
        const headerStyle = {
            font: { bold: true, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "1F4E78" } },
            alignment: { horizontal: "center", vertical: "center" },
            border: {
                top: { style: "thin", color: { rgb: "000000" } },
                bottom: { style: "thin", color: { rgb: "000000" } },
                left: { style: "thin", color: { rgb: "000000" } },
                right: { style: "thin", color: { rgb: "000000" } }
            }
        };

        const titleStyle = {
            font: { bold: true, size: 16, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "4472C4" } },
            alignment: { horizontal: "center", vertical: "center" }
        };

        const subtitleStyle = {
            font: { italic: true, size: 12, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "5B9BD5" } },
            alignment: { horizontal: "center", vertical: "center" }
        };

        const cellStyle = {
            alignment: { vertical: "center" },
            border: {
                top: { style: "thin", color: { rgb: "000000" } },
                bottom: { style: "thin", color: { rgb: "000000" } },
                left: { style: "thin", color: { rgb: "000000" } },
                right: { style: "thin", color: { rgb: "000000" } }
            }
        };

        // Apply styles
        const range = XLSX.utils.decode_range(worksheet['!ref']);
        for (let R = range.s.r; R <= range.e.r; R++) {
            for (let C = range.s.c; C <= range.e.c; C++) {
                const cell_address = XLSX.utils.encode_cell({ r: R, c: C });
                if (!worksheet[cell_address]) continue;

                if (R === 0) {
                    // Title row
                    worksheet[cell_address].s = titleStyle;
                } else if (R === 1) {
                    // Subtitle row
                    worksheet[cell_address].s = subtitleStyle;
                } else if (R === 3) {
                    // Header row
                    worksheet[cell_address].s = headerStyle;
                } else {
                    // Data rows
                    worksheet[cell_address].s = cellStyle;
                }
            }
        }

        // Set column widths
        const wscols = [
            { wch: 10 },  // Mã PB
            { wch: 30 },  // Tên phòng ban
            { wch: 25 },  // Trưởng phòng
            { wch: 15 },  // Số nhân viên
            { wch: 20 },  // Trạng thái
            { wch: 40 },  // Mô tả
            { wch: 20 },  // Ngày tạo
            { wch: 20 }   // Cập nhật lần cuối
        ];
        worksheet['!cols'] = wscols;

        // Set row heights
        worksheet['!rows'] = [
            { hpt: 30 },  // Title row
            { hpt: 20 },  // Subtitle row
            { hpt: 10 },  // Empty row
            { hpt: 25 }   // Header row
        ];

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Phòng ban');

        // Generate filename with timestamp
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const filename = `danh_sach_phong_ban_${timestamp}.xlsx`;

        // Write file
        XLSX.writeFile(workbook, filename);
        showToast('success', 'Xuất Excel thành công');
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi khi xuất Excel');
    } finally {
        hideLoadingState();
    }
}

// View department details
async function viewDepartment(id) {
    try {
        showLoadingState();
        const response = await fetch(`/qlnhansu_V3/backend/src/api/v1/departments.php?action=getById&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const dept = result.data;
            
            // Update modal content with null checks
            document.getElementById('infoDeptCode').textContent = dept.id || 'N/A';
            document.getElementById('infoDeptName').textContent = dept.name || 'N/A';
            document.getElementById('infoDeptDesc').textContent = dept.description || 'Chưa có';
            document.getElementById('infoDeptManager').textContent = dept.manager?.name || 'Chưa có';
            document.getElementById('infoDeptEmployeeCount').textContent = dept.employee_count || 0;
            document.getElementById('infoDeptParent').textContent = dept.parent_department?.name || 'Không có';
            document.getElementById('infoDeptCreated').textContent = formatDate(dept.created_at) || 'N/A';
            document.getElementById('infoDeptUpdated').textContent = formatDate(dept.updated_at) || 'N/A';
            document.getElementById('infoDeptStatus').textContent = 
                dept.status === 'active' ? 'Đang hoạt động' : 'Không hoạt động';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('departmentInfoModal'));
            modal.show();
        } else {
            showToast('error', 'Không thể lấy thông tin phòng ban');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi kết nối server');
    } finally {
        hideLoadingState();
    }
}

// Add new department
function addDepartment() {
    currentDepartmentId = null;
    document.getElementById('modalTitle').textContent = 'Thêm phòng ban mới';
    document.getElementById('departmentForm').reset();
    
    // Set default status to active
    document.getElementById('departmentStatus').value = 'active';
    
    // Reset other fields
    document.getElementById('departmentCode').value = '';
    document.getElementById('parentDepartment').value = '';
    document.getElementById('departmentManager').value = '';
    
    departmentModal.style.display = 'block';
}

// Edit department
function editDepartment(id) {
    const dept = departments.find(d => d.id === id);
    if (!dept) {
        showToast('error', 'Không tìm thấy phòng ban');
        return;
    }
    
    currentDepartmentId = id;
    document.getElementById('modalTitle').textContent = 'Chỉnh sửa phòng ban';
    
    // Fill form data
    document.getElementById('departmentName').value = dept.name;
    document.getElementById('departmentCode').value = dept.id;
    document.getElementById('departmentDescription').value = dept.description || '';
    
    // Set status with proper handling
    const statusSelect = document.getElementById('departmentStatus');
    statusSelect.value = dept.status || 'active';
    
    // Set parent department
    const parentSelect = document.getElementById('parentDepartment');
    parentSelect.value = dept.parent_id || '';
    
    // Set manager
    const managerSelect = document.getElementById('departmentManager');
    managerSelect.value = dept.manager_id || '';
    
    // Show modal
    departmentModal.style.display = 'block';
}

// Format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Show toast notification with improved styling
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <div class="toast-progress"></div>
    `;
    
    document.querySelector('.toast-container').appendChild(toast);
    
    // Add animation
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Event Listeners
searchInput.addEventListener('input', smartSearch);
statusFilter.addEventListener('change', smartSearch);
addDepartmentBtn.addEventListener('click', addDepartment);
exportBtn.addEventListener('click', exportToExcel);
departmentForm.addEventListener('submit', saveDepartment);
cancelBtn.addEventListener('click', () => {
    departmentModal.style.display = 'none';
});

// Close modal when clicking outside
window.addEventListener('click', (event) => {
    if (event.target === departmentModal) {
        departmentModal.style.display = 'none';
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    fetchDepartments();
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            addDepartment();
        }
    });
    
    // Add form validation
    const departmentForm = document.getElementById('departmentForm');
    departmentForm.addEventListener('submit', saveDepartment);
    
    // Add modal close handlers
    const closeButtons = document.querySelectorAll('.close, .btn-close, #cancelBtn');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            departmentModal.style.display = 'none';
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === departmentModal) {
            departmentModal.style.display = 'none';
        }
    });
});

// Hàm khởi tạo biểu đồ
function initializeCharts(departments) {
    // Destroy existing charts if they exist
    if (departmentChart) {
        departmentChart.destroy();
        departmentChart = null;
    }
    if (departmentStatusChart) {
        departmentStatusChart.destroy();
        departmentStatusChart = null;
    }

    // Dữ liệu cho biểu đồ phân bố nhân viên
    const employeeData = {
        labels: departments.map(dept => dept.name),
        datasets: [{
            label: 'Số nhân viên',
            data: departments.map(dept => dept.employee_count),
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',    // Hồng tươi
                'rgba(54, 162, 235, 0.8)',    // Xanh dương tươi
                'rgba(255, 206, 86, 0.8)',    // Vàng tươi
                'rgba(75, 192, 192, 0.8)',    // Ngọc lam
                'rgba(153, 102, 255, 0.8)',   // Tím tươi
                'rgba(255, 159, 64, 0.8)',    // Cam tươi
                'rgba(46, 204, 113, 0.8)',    // Xanh lá tươi
                'rgba(155, 89, 182, 0.8)'     // Tím hồng
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(46, 204, 113, 1)',
                'rgba(155, 89, 182, 1)'
            ],
            borderWidth: 2
        }]
    };

    // Dữ liệu cho biểu đồ trạng thái phòng ban
    const activeDepartments = departments.filter(dept => dept.status === 'active');
    const inactiveDepartments = departments.filter(dept => dept.status === 'inactive');
    
    const statusData = {
        labels: [
            ...activeDepartments.map(dept => `${dept.name} (Đang hoạt động)`),
            ...inactiveDepartments.map(dept => `${dept.name} (Không hoạt động)`)
        ],
        datasets: [{
            data: [
                ...activeDepartments.map(() => 1),
                ...inactiveDepartments.map(() => 1)
            ],
            backgroundColor: [
                ...activeDepartments.map(() => '#00e676'),  // Xanh lá tươi
                ...inactiveDepartments.map(() => '#ff1744') // Đỏ tươi
            ],
            borderColor: [
                ...activeDepartments.map(() => '#00c853'),
                ...inactiveDepartments.map(() => '#d50000')
            ],
            borderWidth: 2
        }]
    };

    // Cấu hình chung cho biểu đồ
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    boxWidth: 15,
                    padding: 15,
                    color: '#2c3e50'
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        return label;
                    }
                },
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#2c3e50',
                bodyColor: '#2c3e50',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                padding: 10,
                cornerRadius: 8
            }
        }
    };

    // Khởi tạo biểu đồ phân bố nhân viên
    const departmentCtx = document.getElementById('departmentChart').getContext('2d');
    departmentChart = new Chart(departmentCtx, {
        type: isBarChart ? 'bar' : 'pie',
        data: employeeData,
        options: {
            ...commonOptions,
            scales: isBarChart ? {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#2c3e50',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#2c3e50',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            } : undefined
        }
    });

    // Khởi tạo biểu đồ trạng thái
    const statusCtx = document.getElementById('departmentStatusChart').getContext('2d');
    departmentStatusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: statusData,
        options: {
            ...commonOptions,
            cutout: '60%',
            plugins: {
                ...commonOptions.plugins,
                title: {
                    display: true,
                    font: {
                        size: 16,
                        weight: 'bold',
                        family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                    },
                    color: '#2c3e50',
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                }
            }
        }
    });
}

// Hàm cập nhật biểu đồ
function updateCharts(departments) {
    if (departmentChart) {
        departmentChart.destroy();
    }
    if (departmentStatusChart) {
        departmentStatusChart.destroy();
    }
    initializeCharts(departments);
}

// Xử lý sự kiện chuyển đổi loại biểu đồ
document.getElementById('toggleChartView').addEventListener('click', function() {
    isBarChart = !isBarChart;
    const departments = JSON.parse(localStorage.getItem('departments') || '[]');
    updateCharts(departments);
}); 