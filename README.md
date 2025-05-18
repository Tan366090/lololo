# Hướng dẫn cài đặt và sử dụng dự án QLNHANSU_V3

## 1. Yêu cầu hệ thống
- **PHP:** Phiên bản 7.4 trở lên
- **MySQL:** Phiên bản 5.7 trở lên
- **Web Server:** Apache hoặc Nginx
- **Composer:** Để quản lý dependencies PHP
- **Node.js & npm:** Để quản lý dependencies JavaScript (nếu có)

## 2. Cài đặt

### Bước 1: Clone dự án
```bash
git clone <repository_url>
cd qlnhansu_V3
```

### Bước 2: Cài đặt dependencies PHP
```bash
composer install
```

### Bước 3: Cài đặt dependencies JavaScript (nếu có)
```bash
npm install
```

### Bước 4: Cấu hình database
- Tạo database mới trong MySQL:
  ```sql
  CREATE DATABASE qlnhansu;
  ```
- Import dữ liệu mẫu (nếu có):
  ```bash
  mysql -u <username> -p qlnhansu < qlnhansu.sql
  ```
- Cập nhật thông tin kết nối database trong file `config/database.php`:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_USER', 'your_username');
  define('DB_PASS', 'your_password');
  define('DB_NAME', 'qlnhansu');
  ```

### Bước 5: Cấu hình Web Server
- **Apache:**  
  Đảm bảo mod_rewrite đã bật và .htaccess hoạt động đúng.
- **Nginx:**  
  Cấu hình rewrite rules tương tự như trong file `.htaccess`.

### Bước 6: Khởi động Web Server
- **Apache:**
  ```bash
  sudo service apache2 start
  ```
- **Nginx:**
  ```bash
  sudo service nginx start
  ```

## 3. Sử dụng

### Bước 1: Truy cập ứng dụng
- Mở trình duyệt và truy cập:
  ```
  http://localhost/qlnhansu_V3
  ```

### Bước 2: Đăng nhập
- Sử dụng tài khoản mặc định (nếu có):
  - **Username:** admin
  - **Password:** admin123

### Bước 3: Khám phá các tính năng
- **Dashboard:** Xem tổng quan về chấm công, nhân viên, phòng ban.
- **Chấm công:** Quản lý thông tin chấm công của nhân viên.
- **Nhân viên:** Quản lý thông tin nhân viên, phòng ban.
- **Báo cáo:** Xuất báo cáo Excel, PDF.

## 4. Troubleshooting

### Lỗi kết nối database
- Kiểm tra lại thông tin kết nối trong `config/database.php`.
- Đảm bảo MySQL đang chạy:
  ```bash
  sudo service mysql status
  ```

### Lỗi 404 Not Found
- Kiểm tra lại cấu hình Web Server (Apache/Nginx) và .htaccess.
- Đảm bảo mod_rewrite đã bật:
  ```bash
  sudo a2enmod rewrite
  sudo service apache2 restart
  ```

### Lỗi JavaScript
- Kiểm tra Console (F12 → Console) để xem lỗi.
- Đảm bảo các file JS đã được tải đúng.

## 5. Tài liệu tham khảo
- **README.md:** Chứa thông tin chi tiết về dự án.
- **API Documentation:** Tài liệu API (nếu có) để tích hợp với hệ thống khác.

## 6. Liên hệ hỗ trợ
Nếu gặp vấn đề, hãy liên hệ:
- **Email:** support@example.com
- **GitHub Issues:** Tạo issue trên repository.
