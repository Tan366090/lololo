-- Thêm cột status vào bảng departments
ALTER TABLE departments 
ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' 
COMMENT 'Trạng thái hoạt động của phòng ban' 
AFTER description;

-- Cập nhật tất cả các phòng ban hiện có thành active
UPDATE departments SET status = 'active' WHERE status IS NULL;

-- Thêm index cho cột status để tối ưu hiệu suất tìm kiếm
ALTER TABLE departments
ADD INDEX idx_dept_status (status); 