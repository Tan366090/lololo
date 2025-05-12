// Xử lý các hành động liên quan đến phiếu lương
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo các biến
    const addPayrollBtn = document.getElementById('addPayrollBtn');
    const addPayrollModal = document.getElementById('addPayrollModal');
    const editPayrollModal = document.getElementById('editPayrollModal');
    const viewPayrollModal = document.getElementById('viewPayrollModal');
    const searchEmployeeBtn = document.getElementById('searchEmployeeBtn');
    const exportBtn = document.getElementById('exportBtn');

    // Xử lý nút thêm phiếu lương
    if (addPayrollBtn) {
        addPayrollBtn.addEventListener('click', function() {
            addPayrollModal.style.display = 'block';
            // Reset form
            document.getElementById('addPayrollForm').reset();
            // Ẩn section lịch sử lương
            document.getElementById('payrollHistorySection').style.display = 'none';
        });
    }

    // Xử lý nút tìm kiếm nhân viên
    if (searchEmployeeBtn) {
        searchEmployeeBtn.addEventListener('click', function() {
            const employeeCode = document.getElementById('employeeCode').value;
            if (employeeCode) {
                searchEmployee(employeeCode);
            }
        });
    }

    // Xử lý nút xuất Excel
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportToExcel();
        });
    }

    // Xử lý đóng modal
    document.querySelectorAll('.close').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Đóng modal khi click bên ngoài
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });

    // Hàm tìm kiếm nhân viên
    function searchEmployee(employeeCode) {
        fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=getEmployeePayroll&employeeCode=${employeeCode}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const employee = data.data.employee;
                    document.getElementById('employeeName').value = employee.name;
                    document.getElementById('department').value = employee.department;
                    document.getElementById('position').value = employee.position;
                    document.getElementById('basicSalary').value = employee.base_salary;
                    
                    // Hiển thị lịch sử lương
                    showPayrollHistory(employee.id);
                } else {
                    alert('Không tìm thấy nhân viên');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tìm kiếm nhân viên');
            });
    }

    // Hàm hiển thị lịch sử lương
    function showPayrollHistory(employeeId) {
        fetch(`/qlnhansu_V3/backend/src/public/admin/api/payroll.php?action=getEmployeePayroll&employeeId=${employeeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const historyContainer = document.getElementById('payrollHistoryContainer');
                    const historySection = document.getElementById('payrollHistorySection');
                    
                    if (data.data.payrolls && data.data.payrolls.length > 0) {
                        let html = `
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Kỳ lương</th>
                                        <th>Lương cơ bản</th>
                                        <th>Phụ cấp</th>
                                        <th>Thưởng</th>
                                        <th>Khấu trừ</th>
                                        <th>Thực lĩnh</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        data.data.payrolls.forEach(payroll => {
                            html += `
                                <tr>
                                    <td>${payroll.period.month}</td>
                                    <td>${payroll.salary.base}</td>
                                    <td>${payroll.salary.allowances}</td>
                                    <td>${payroll.salary.bonuses}</td>
                                    <td>${payroll.salary.deductions}</td>
                                    <td>${payroll.salary.net}</td>
                                </tr>
                            `;
                        });
                        
                        html += '</tbody></table>';
                        historyContainer.innerHTML = html;
                        historySection.style.display = 'block';
                    } else {
                        historySection.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Hàm xuất Excel
    function exportToExcel() {
        const searchValue = document.getElementById('searchInput').value;
        const departmentId = document.getElementById('departmentFilter').value;
        const month = document.getElementById('monthFilter').value;
        const year = document.getElementById('yearFilter').value;

        const params = new URLSearchParams({
            action: 'export',
            search: searchValue,
            department_id: departmentId,
            month: month,
            year: year
        });

        window.location.href = `/qlnhansu_V3/backend/src/public/admin/api/payroll.php?${params.toString()}`;
    }

    // Hàm định dạng tiền tệ
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    // Xử lý form thêm phiếu lương
    const addPayrollForm = document.getElementById('addPayrollForm');
    if (addPayrollForm) {
        addPayrollForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Thêm các giá trị khác
            data.basicSalary = getNumericValue(document.getElementById('basicSalary').value);
            data.allowance = getNumericValue(document.getElementById('allowance').value);
            data.bonus = getNumericValue(document.getElementById('bonus').value);
            data.deduction = getNumericValue(document.getElementById('deduction').value);
            
            // Gửi request
            fetch('/qlnhansu_V3/backend/src/public/admin/api/payroll.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thêm phiếu lương thành công');
                    addPayrollModal.style.display = 'none';
                    // Reload danh sách
                    if (typeof loadPayrollData === 'function') {
                        loadPayrollData();
                    }
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm phiếu lương');
            });
        });
    }

    // Hàm lấy giá trị số từ chuỗi đã định dạng
    function getNumericValue(formattedValue) {
        return parseInt(formattedValue.replace(/[^\d]/g, ''), 10) || 0;
    }
}); 