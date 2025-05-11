-- Xóa các trigger theo dõi CSDL không còn cần thiết
-- Các trigger này được tạo để gọi hàm call_sync_script() nhằm theo dõi thay đổi CSDL
-- Nhưng hiện tại không còn cần thiết nữa nên có thể xóa an toàn

DROP TRIGGER IF EXISTS after_leave_insert;
DROP TRIGGER IF EXISTS after_leave_update;
DROP TRIGGER IF EXISTS after_leave_delete; 