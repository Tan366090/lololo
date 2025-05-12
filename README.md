# Phân tích chi tiết Dashboard Quản lý Nhân sự

## 1. 10 Dashboard Cards

### 1.1. Tổng số nhân viên
- **Hiển thị**: Số lượng nhân viên hiện tại
- **Dữ liệu**: Bảng `employees`
- **Chi tiết**:
  * Tổng số nhân viên đang làm việc
  * Phân loại theo loại hợp đồng
  * So sánh với tháng trước
  * Tỷ lệ tăng/giảm

### 1.2. Nhân viên mới
- **Hiển thị**: Số nhân viên mới trong tháng
- **Dữ liệu**: Bảng `employees` (hire_date)
- **Chi tiết**:
  * Số lượng tuyển mới
  * Phân bố theo phòng ban
  * Tỷ lệ hoàn thành thử việc
  * Chi phí tuyển dụng

### 1.3. Thôi việc
- **Hiển thị**: Số nhân viên nghỉ việc
- **Dữ liệu**: Bảng `employees` (status)
- **Chi tiết**:
  * Số lượng nghỉ việc
  * Lý do nghỉ việc
  * Phân tích theo phòng ban
  * Tỷ lệ thôi việc

### 1.4. Quỹ lương
- **Hiển thị**: Tổng chi phí lương
- **Dữ liệu**: Bảng `payroll`
- **Chi tiết**:
  * Tổng quỹ lương
  * Lương cơ bản
  * Phụ cấp và thưởng
  * So sánh với ngân sách

### 1.5. Số phòng ban
- **Hiển thị**: Tổng số phòng ban
- **Dữ liệu**: Bảng `departments`
- **Chi tiết**:
  * Số lượng phòng ban
  * Nhân sự mỗi phòng
  * Phòng ban mới thành lập
  * Phòng ban giải thể

### 1.6. Nghỉ phép
- **Hiển thị**: Số nhân viên đang nghỉ
- **Dữ liệu**: Bảng `leave_requests`
- **Chi tiết**:
  * Số người đang nghỉ
  * Loại nghỉ phép
  * Thời gian nghỉ
  * Phân bố theo phòng

### 1.7. Đánh giá
- **Hiển thị**: Kết quả đánh giá
- **Dữ liệu**: Bảng `performance_evaluations`
- **Chi tiết**:
  * Điểm đánh giá trung bình
  * Tỷ lệ hoàn thành
  * Phân loại kết quả
  * Xu hướng đánh giá

### 1.8. Đào tạo
- **Hiển thị**: Thông tin đào tạo
- **Dữ liệu**: Bảng `training`
- **Chi tiết**:
  * Số khóa đào tạo
  * Số người tham gia
  * Chi phí đào tạo
  * Hiệu quả đào tạo

### 1.9. Kỷ luật
- **Hiển thị**: Số vụ việc kỷ luật
- **Dữ liệu**: Bảng `disciplinary_actions`
- **Chi tiết**:
  * Số vụ việc
  * Mức độ vi phạm
  * Hình thức kỷ luật
  * Xu hướng theo thời gian

### 1.10. Tuyển dụng
- **Hiển thị**: Trạng thái tuyển dụng
- **Dữ liệu**: Bảng `recruitment`
- **Chi tiết**:
  * Vị trí đang tuyển
  * Số ứng viên
  * Tỷ lệ chuyển đổi
  * Thời gian tuyển dụng

## 2. 6 Biểu đồ

### 2.1. Biểu đồ tròn: Phân bố nhân viên
- **Dữ liệu**: Bảng `employees` và `departments`
- **Hiển thị**:
  * Phân bố theo phòng ban
  * Tỷ lệ phần trăm
  * Tương tác khi hover
  * Xuất dữ liệu chi tiết

### 2.2. Biểu đồ cột: Xu hướng nhân sự
- **Dữ liệu**: Bảng `employees`
- **Hiển thị**:
  * Tuyển mới theo tháng
  * Thôi việc theo tháng
  * So sánh các năm
  * Dự báo xu hướng

### 2.3. Biểu đồ đường: Chi phí lương
- **Dữ liệu**: Bảng `payroll`
- **Hiển thị**:
  * Chi phí theo tháng
  * So với ngân sách
  * Phân tích thành phần
  * Dự báo tương lai

### 2.4. Biểu đồ miền: Phân bố độ tuổi
- **Dữ liệu**: Bảng `employees`
- **Hiển thị**:
  * Phân bố theo nhóm tuổi
  * Phân biệt giới tính
  * Theo phòng ban
  * Xu hướng thay đổi

### 2.5. Biểu đồ radar: Đánh giá năng lực
- **Dữ liệu**: Bảng `performance_evaluations`
- **Hiển thị**:
  * Các tiêu chí đánh giá
  * So sánh với mục tiêu
  * Theo phòng ban
  * Xu hướng cải thiện

### 2.6. Biểu đồ thanh ngang: Top phòng ban
- **Dữ liệu**: Bảng `departments` và `performance_evaluations`
- **Hiển thị**:
  * Top 5 phòng ban
  * Chỉ số KPI
  * Tăng trưởng
  * So sánh với mục tiêu

## 3. 4 Tác vụ nhanh

### 3.1. Duyệt đơn nghỉ phép
- **Dữ liệu**: Bảng `leave_requests`
- **Chức năng**:
  * Xem danh sách đơn
  * Duyệt/từ chối
  * Thêm ghi chú
  * Xuất báo cáo

### 3.2. Phê duyệt lương
- **Dữ liệu**: Bảng `payroll` và `payroll_approvals`
- **Chức năng**:
  * Xem bảng lương
  * Phê duyệt
  * Điều chỉnh
  * Xuất file

### 3.3. Đánh giá nhân viên
- **Dữ liệu**: Bảng `performance_evaluations`
- **Chức năng**:
  * Tạo đánh giá
  * Nhập điểm
  * Xem lịch sử
  * Xuất báo cáo

### 3.4. Báo cáo nhanh
- **Dữ liệu**: Tất cả các bảng liên quan
- **Chức năng**:
  * Chọn loại báo cáo
  * Tùy chỉnh thời gian
  * Xuất PDF/Excel
  * Lưu mẫu

## 4. Footer

### 4.1. Thông tin hệ thống
- **Dữ liệu**: Bảng `activities`
- **Hiển thị**:
  * Phiên bản phần mềm
  * Thời gian cập nhật
  * Trạng thái backup
  * Thông tin server

### 4.2. Cảnh báo quan trọng
- **Dữ liệu**: Các bảng liên quan
- **Hiển thị**:
  * Hợp đồng sắp hết hạn
  * Nhân viên sắp nghỉ hưu
  * Sự kiện sắp tới
  * Vấn đề cần xử lý

### 4.3. Thống kê nhanh
- **Dữ liệu**: Bảng `activities`
- **Hiển thị**:
  * Số lượt truy cập
  * Thời gian hoạt động
  * Hoạt động gần đây
  * Lỗi hệ thống

### 4.4. Liên kết nhanh
- **Dữ liệu**: Các bảng liên quan
- **Hiển thị**:
  * Hướng dẫn sử dụng
  * Hỗ trợ kỹ thuật
  * Báo lỗi
  * Tài liệu tham khảo

## 5. Cấu trúc Database

### 5.1. Bảng chính

1. **activities**
- Lưu trữ hoạt động của người dùng
- Các trường chính: id, user_id, type, description, target_entity, status, created_at

2. **allowances**
- Quản lý phụ cấp
- Các trường chính: allowance_id, name, description, amount, is_percentage, percentage_rate

3. **assets**
- Quản lý tài sản
- Các trường chính: id, name, asset_code, category, status, location

4. **asset_assignments**
- Phân bổ tài sản cho nhân viên
- Các trường chính: id, asset_id, employee_id, assigned_date, status

5. **attendance**
- Chấm công
- Các trường chính: attendance_id, employee_id, attendance_date, check_in_time, check_out_time

6. **audit_logs**
- Nhật ký kiểm tra
- Các trường chính: log_id, user_id, action_type, target_entity, details

7. **backup_logs**
- Nhật ký sao lưu
- Các trường chính: id, backup_type, file_path, status, error_message

8. **benefits**
- Phúc lợi
- Các trường chính: id, name, type, amount, status

9. **bonuses**
- Thưởng
- Các trường chính: bonus_id, employee_id, bonus_type, amount, status

10. **certificates**
- Chứng chỉ
- Các trường chính: id, employee_id, name, issuing_organization, issue_date, expiry_date

11. **chat_context**
- Lưu trữ ngữ cảnh chat
- Các trường chính: user_id, context, updated_at

12. **chat_logs**
- Nhật ký chat
- Các trường chính: id, message, created_at, user_id, intent, entities

13. **contracts**
- Hợp đồng lao động
- Các trường chính: id, employee_id, contract_type, start_date, end_date, salary, status

14. **contract_types**
- Loại hợp đồng
- Các trường chính: id, name

15. **deductions**
- Khấu trừ
- Các trường chính: deduction_id, name, amount, is_percentage, percentage_rate

16. **degrees**
- Bằng cấp
- Các trường chính: degree_id, employee_id, degree_name, major, institution, graduation_date

17. **departments**
- Phòng ban
- Các trường chính: id, name, description, status, manager_id, parent_id

18. **documents**
- Tài liệu
- Các trường chính: id, title, description, file_url, document_type, access_level

19. **document_versions**
- Phiên bản tài liệu
- Các trường chính: id, document_id, version_number, file_url, changes_description

20. **email_verification_tokens**
- Token xác thực email
- Các trường chính: id, user_id, token, expires_at

21. **employees**
- Nhân viên
- Các trường chính: id, user_id, name, email, department_id, position_id, hire_date, status

22. **employee_positions**
- Vị trí nhân viên
- Các trường chính: id, employee_id, position_id, department_id, start_date, end_date

### 5.2. Mối quan hệ giữa các bảng

1. **Quản lý nhân viên**
- employees -> departments (department_id)
- employees -> positions (position_id)
- employees -> contracts (employee_id)
- employees -> degrees (employee_id)
- employees -> certificates (employee_id)

2. **Quản lý tài sản**
- assets -> asset_assignments (asset_id)
- asset_assignments -> employees (employee_id)

3. **Quản lý tài liệu**
- documents -> document_versions (document_id)
- documents -> departments (department_id)

4. **Quản lý lương thưởng**
- employees -> bonuses (employee_id)
- employees -> allowances (employee_id)
- employees -> deductions (employee_id)

5. **Quản lý hoạt động**
- activities -> users (user_id)
- audit_logs -> users (user_id)
- chat_logs -> users (user_id)
