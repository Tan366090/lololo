<?php include 'headers.php'; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' https:; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://fonts.googleapis.com https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: https://cdn.jsdelivr.net https://code.jquery.com; connect-src 'self' https:;">
    <meta name="theme-color" content="#ffffff" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    
    <title>Admin Dashboard - Quản trị hệ thống</title>

    <!-- CSS -->
    <!-- <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css"> -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <link rel="stylesheet" href="../assets/css/loading.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <link rel="stylesheet" href="css/libs/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/libs/roboto.css">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <!-- Thêm Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Thêm ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- Thêm SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JavaScript -->
    <script src="js/libs/jquery-3.7.1.min.js"></script>
    <script src="js/libs/bootstrap.bundle.min.js"></script>
    <style>
        .stat-info h3 {
    color: #222 !important;
    font-weight: bold;
    font-family: 'Roboto', Arial, sans-serif;
    letter-spacing: -0.5px;
}

.data-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    background-color: rgba(255, 255, 255, 0.2);
    color: black;
    font-weight: bold;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-right: 10px;
    font-size: 20px;
    white-space: nowrap;
}

.data-btn:hover {
    background-color: rgba(255, 255, 255, 0.3);
    color: black;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.data-btn i {
    font-size: 14px;
    color: #FFD700;
}

.data-btn span {
    font-size: 13px;
    font-weight: 500;
}

.chart-card {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    transition: all 0.3s ease;
    height: 400px;
    margin-bottom: 24px;
}
.chart-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}
.chart-header {
    padding: 1rem;
    border-bottom: 1px solid #e3e6f0;
    background-color: #f8f9fc;
    border-radius: 8px 8px 0 0;
}
.chart-body {
    padding: 1rem;
    height: calc(100% - 60px);
    display: flex;
    align-items: center;
    justify-content: center;
}
.chart-title {
    margin: 0;
    color: #4e73df;
    font-weight: 600;
    font-size: 1rem;
}

.feature-cards {
  display: flex;
  gap: 32px;
  justify-content: center;
  background: #f5f7fa;
  padding: 40px 0;
}
.feature-card {
  background: #fff;
  border-radius: 24px;
  border: none;
  width: 220px;
  min-height: 260px;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  transition: box-shadow 0.25s, transform 0.25s, background 0.25s;
  cursor: pointer;
  overflow: hidden;
  box-shadow: none;
  outline: none;
}
.feature-card.active {
  background: var(--main-color, #F1C40F);
  color: #fff;
}
.feature-card .icon-circle {
  margin-top: 36px;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(255,255,255,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 38px;
  color: var(--main-color, #F1C40F);
  margin-bottom: 24px;
  border: 2.5px solid var(--main-color, #F1C40F);
  transition: border 0.25s, background 0.25s, color 0.25s;
}
.feature-card:nth-child(2) .icon-circle {
  color: #1ABC9C;
  border-color: #1ABC9C;
}
.feature-card:nth-child(3) .icon-circle {
  color: #9B59B6;
  border-color: #9B59B6;
}
.feature-card:nth-child(4) .icon-circle {
  color: #3498DB;
  border-color: #3498DB;
}
.feature-title {
  font-size: 1.1rem;
  color: #666;
  margin-bottom: 0;
  margin-top: 8px;
  font-weight: 500;
  text-align: center;
  transition: color 0.3s, transform 0.4s cubic-bezier(.4,2,.6,1), opacity 0.4s;
  opacity: 1;
  transform: translateY(0);
}
.feature-card.active .feature-title {
  color: #fff;
}
.feature-card:hover .feature-title, .feature-card:focus .feature-title {
  color: var(--main-color, #F1C40F);
  transform: translateY(-10px) scale(1.04);
  opacity: 0.85;
}
.feature-card:focus {
  outline: 2px solid var(--main-color, #F1C40F);
}
.feature-card:hover, .feature-card:focus {
  box-shadow: 0 8px 32px rgba(52, 152, 219, 0.18);
  transform: translateY(-4px) scale(1.03);
  z-index: 2;
}
.feature-card:nth-child(2):hover, .feature-card:nth-child(2):focus {
  border-color: #1ABC9C;
}
.feature-card:nth-child(3):hover, .feature-card:nth-child(3):focus {
  border-color: #9B59B6;
}
.feature-card:nth-child(4):hover, .feature-card:nth-child(4):focus {
  border-color: #3498DB;
}
@media (max-width: 1200px) {
  .feature-cards { gap: 18px; }
  .feature-card { width: 180px; min-height: 200px; }
}
@media (max-width: 900px) {
  .feature-cards { flex-wrap: wrap; justify-content: flex-start; }
  .feature-card { width: 48%; margin-bottom: 24px; }
}
@media (max-width: 600px) {
  .feature-cards { flex-direction: column; gap: 16px; padding: 16px 0; }
  .feature-card { width: 100%; min-height: 140px; }
}
.feature-detail {
  opacity: 0;
  pointer-events: none;
  position: absolute;
  left: 0; right: 0; bottom: 0;
  background: rgba(255,255,255,0.98);
  color: #333;
  padding: 20px 16px 16px 16px;
  font-size: 0.98rem;
  border-radius: 0 0 24px 24px;
  min-height: 80px;
  text-align: center;
  transition: opacity 0.4s cubic-bezier(.4,2,.6,1), transform 0.4s cubic-bezier(.4,2,.6,1);
  z-index: 3;
  transform: translateY(20px);
}
.feature-card:hover .feature-detail, .feature-card:focus .feature-detail {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0);
}

/* Footer Styles */
.dashboard-footer {
    background: #fff;
    border-top: 1px solid #e3e6f0;
    padding: 20px 0;
    margin-top: 40px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    padding: 0 24px;
}

.footer-section {
    padding: 16px;
    background: #f8f9fc;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.footer-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.footer-title {
    color: #4e73df;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.footer-title i {
    font-size: 1.1rem;
}

.footer-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-list li {
    padding: 8px 0;
    border-bottom: 1px solid #e3e6f0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #666;
}

.footer-list li:last-child {
    border-bottom: none;
}

.footer-list li i {
    color: #4e73df;
    font-size: 0.9rem;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

.status-active {
    background-color: #2ecc71;
}

.status-warning {
    background-color: #f1c40f;
}

.status-error {
    background-color: #e74c3c;
}

@media (max-width: 1200px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
}

/* Modern Footer Styles giống ảnh mẫu */
.modern-footer {
    background: linear-gradient(120deg, #181f36 60%, #233554 100%);
    color: #fff;
    border-bottom-right-radius: 80px;
    font-family: 'Roboto', Arial, sans-serif;
    margin-top: 0;
    padding-top: 48px;
    padding-bottom: 0;
}
.modern-footer .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
}
.footer-logo {
    font-size: 1.6rem;
    font-weight: bold;
    letter-spacing: 1px;
    color: #fff;
    margin-bottom: 12px;
}
.footer-desc {
    font-size: 1rem;
    color: #b0b8c1;
    margin-bottom: 18px;
}
.footer-social a {
    color: #b0b8c1;
    font-size: 18px;
    margin-right: 12px;
    transition: color 0.2s;
}
.footer-social a:last-child { margin-right: 0; }
.footer-social a:hover { color: #fff; }
.footer-heading {
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 18px;
    letter-spacing: 0.5px;
}
.footer-links {
    padding-left: 0;
    margin-bottom: 0;
}
.footer-link {
    color: #b0b8c1;
    text-decoration: none;
    font-size: 1rem;
    display: block;
    margin-bottom: 10px;
    transition: color 0.2s;
}
.footer-link:hover {
    color: #fff;
    text-decoration: underline;
}
.footer-blog h6 {
    color: #fff;
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 10px;
}
.footer-blog img {
    width: 36px;
    height: 36px;
    object-fit: cover;
    border-radius: 6px;
    margin-right: 10px;
}
.footer-blog a {
    color: #b0b8c1;
    font-size: 0.97rem;
    text-decoration: none;
    transition: color 0.2s;
}
.footer-blog a:hover { color: #fff; text-decoration: underline; }

/* Dòng dưới cùng */
.modern-footer hr {
    border: none;
    border-top: 1px solid #232a45;
    margin: 32px 0 16px 0;
}
.modern-footer .row.align-items-center {
    font-size: 0.97rem;
    color: #b0b8c1;
}
.modern-footer .footer-link.mx-2 {
    margin-left: 10px !important;
    margin-right: 10px !important;
    font-size: 0.97rem;
    color: #b0b8c1;
    text-decoration: none;
    transition: color 0.2s;
}
.modern-footer .footer-link.mx-2:hover {
    color: #fff;
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 1200px) {
    .modern-footer .container-fluid { padding-left: 24px; padding-right: 24px; }
}
@media (max-width: 992px) {
    .modern-footer .row > div { margin-bottom: 32px; }
    .modern-footer .container-fluid { padding-left: 12px; padding-right: 12px; }
}
@media (max-width: 768px) {
    .modern-footer .row { flex-direction: column; }
    .modern-footer .row > div { width: 100%; margin-bottom: 24px; }
    .modern-footer { border-bottom-right-radius: 32px; }
}
@media (max-width: 500px) {
    .modern-footer { border-bottom-right-radius: 16px; padding-top: 24px; }
    .footer-logo { font-size: 1.2rem; }
}

.stats-swiper {
  width: 100%;
  max-width: 900px;
  margin: 0 auto 32px auto;
  padding: 32px 0 48px 0;
  background: linear-gradient(120deg, #3a8dde 0%, #6dd5ed 100%);
  border-radius: 24px;
  box-shadow: 0 8px 32px rgba(52, 152, 219, 0.18);
}
.stats-swiper .swiper-wrapper {
  align-items: center;
}
.stat-card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.07);
  padding: 32px 24px 24px 24px;
  min-width: 260px;
  max-width: 280px;
  margin: 0 12px;
  text-align: center;
  transition: 
    transform 0.5s cubic-bezier(.77,0,.18,1),
    box-shadow 0.5s,
    opacity 0.5s;
  opacity: 0.7;
  filter: blur(0.5px);
}
.stat-card .stat-icon {
  font-size: 2.5rem;
  color: #4e73df;
  margin-bottom: 18px;
}
.stat-card .stat-info h3 {
  font-size: 1.1rem;
  font-weight: bold;
  color: #222;
  margin-bottom: 8px;
}
.stat-card .stat-number {
  font-size: 2.1rem;
  font-weight: bold;
  color: #1abc9c;
  margin-bottom: 6px;
}
.stat-card .stat-change {
  font-size: 1rem;
  color: #888;
}
.stat-card .stat-change.positive { color: #27ae60; }
.stat-card .stat-change.negative { color: #e74c3c; }
.stats-swiper .swiper-slide-active {
  transform: scale(1.12) translateY(-12px);
  box-shadow: 0 8px 32px rgba(52, 152, 219, 0.18);
  opacity: 1;
  filter: none;
  z-index: 2;
}
.stats-swiper .swiper-slide-next,
.stats-swiper .swiper-slide-prev {
  transform: scale(0.98);
  opacity: 0.85;
  filter: blur(0.2px);
  z-index: 1;
}
.stats-swiper .swiper-slide {
  pointer-events: none;
}
.stats-swiper .swiper-slide-active,
.stats-swiper .swiper-slide-next,
.stats-swiper .swiper-slide-prev {
  pointer-events: auto;
}
.swiper-button-next, .swiper-button-prev {
  color: #fff;
  background: rgba(52, 152, 219, 0.7);
  border-radius: 50%;
  width: 44px;
  height: 44px;
  top: 50%;
  transform: translateY(-50%);
  box-shadow: 0 2px 8px rgba(52, 152, 219, 0.18);
  transition: background 0.2s;
}
.swiper-button-next:hover, .swiper-button-prev:hover {
  background: #4e73df;
}
.swiper-pagination-bullet {
  background: #fff;
  opacity: 0.7;
}
.swiper-pagination-bullet-active {
  background: #4e73df;
  opacity: 1;
}
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar" role="complementary">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="user-avatar">
                    <img src="male.png" alt="User Avatar" class="rounded-circle" width="40" height="40">
                </div>
                <div class="user-details">
                    <h2 class="user-name">VNPT</h2>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
        </div>

        <nav role="navigation">
            <ul class="nav-list">
                <li class="nav-item active" data-menu-id="dashboard">
                    <a href="dashboard_admin_V1.php" class="nav-link">
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="employees">
                    <a href="employees/NhanVien_List.html" class="nav-link">
                        <span>Nhân viên</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="attendance">
                    <a href="attendance/attendance.html" class="nav-link">
                        <span>Chấm công</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="payroll">
                    <a href="payroll/payroll_List.html" class="nav-link">
                        <span>Lương thưởng</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="departments">
                    <a href="departments/departments.html" class="nav-link">
                        <span>Phòng ban</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="certificates">
                    <a href="degrees/degrees_list.html" class="nav-link">
                        <span>Bằng cấp</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="leave">
                    <a href="leave/leave_list.php" class="nav-link">
                        <span>Nghỉ phép</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="leave">
                    <a href="developing.html" class="nav-link">
                        <span>Quản lý Đào tạo</span>
                    </a>
                </li>
                  <li class="nav-item" data-menu-id="leave">
                    <a href="developing.html" class="nav-link">
                        <span>Quản lý phát triển</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="leave">
                    <a href="developing.html" class="nav-link">
                        <span>Quản lý Tài liệu</span>
                    </a>
                </li>
                  <li class="nav-item" data-menu-id="leave">
                    <a href="developing.html" class="nav-link">
                        <span>Hợp đồng Nhân sự</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="leave">
                    <a href="developing.html" class="nav-link">
                        <span>Báo cáo, Thống kê và Phân tích dữ liệu</span>
                    </a>
                </li>
                <li class="nav-item" data-menu-id="logout">
                    <a href="developing.html" class="nav-link">
                        <span>Đăng xuất</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
 
   <div style ="background-color: #f8f6f4;"class="wrapper">
        <!-- Header -->
        <header class="header">
            <div class="header-center">
                <input type="text" class="form-control search-box" placeholder="Tìm kiếm...">
            </div>
            <div class="header-right">
                <a href="check_data.php" class="btn btn-link data-btn" type="button">
                    <i class="fas fa-database"></i>
                    <span>Data</span>
                </a>
                <button class="btn btn-link notification-bell" type="button" id="notificationsDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </button>
                <button class="btn btn-link chat-bell" type="button" id="chatButton">
                    <img src="chat.png" alt="Chat" style="width:28px;height:28px;object-fit:cover;vertical-align:middle;">
                </button>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-link p-0" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <img src="male.png" alt="User" class="rounded-circle" width="32" height="32">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Hồ sơ</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Cài đặt</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                    </ul>
                </div>
                <select id="languageSwitch" class="form-select form-select-sm ms-2">
                    <option value="vi">Tiếng Việt</option>
                    <option value="en">English</option>
                </select>
                <button id="darkModeToggle" class="btn ms-2" aria-label="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
           
        </header>

        <!-- Main Content -->
        <main style ="background-color: #f8f6f4;" class="main-content" id="mainContent" role="main">
            <!-- Statistics Cards -->
            <!-- Statistics Cards (Swiper) -->
            <div class="swiper stats-swiper">
              <div class="swiper-wrapper">
                <!-- Card 1 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                  <div class="stat-info">
                    <h3>Tổng số nhân viên</h3>
                    <p class="stat-number" id="totalEmployees">0</p>
                    <p class="stat-change positive">+5% so với tháng trước</p>
                  </div>
                </div>
                <!-- Card 2 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                  <div class="stat-info">
                    <h3>Nhân viên mới</h3>
                    <p class="stat-number" id="newEmployees">0</p>
                    <p class="stat-change positive">Tỷ lệ hoàn thành thử việc: 85%</p>
                  </div>
                </div>
                <!-- Card 3 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-user-minus"></i></div>
                  <div class="stat-info">
                    <h3>Thôi việc</h3>
                    <p class="stat-number" id="resignedEmployees">0</p>
                    <p class="stat-change negative">Tỷ lệ thôi việc: 2.5%</p>
                  </div>
                </div>
                <!-- Card 4 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                  <div class="stat-info">
                    <h3>Quỹ lương</h3>
                    <p class="stat-number" id="totalSalary">0 VNĐ</p>
                    <p class="stat-change">So với ngân sách: 95%</p>
                  </div>
                </div>
                <!-- Card 5 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
                  <div class="stat-info">
                    <h3>Số phòng ban</h3>
                    <p class="stat-number" id="totalDepartments">0</p>
                    <p class="stat-change positive">+2 phòng ban mới</p>
                  </div>
                </div>
                <!-- Card 6 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-calendar-alt"></i></div>
                  <div class="stat-info">
                    <h3>Đang nghỉ phép</h3>
                    <p class="stat-number" id="onLeave">0</p>
                    <p class="stat-change">Trung bình: 3 ngày</p>
                  </div>
                </div>
                <!-- Card 7 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
                  <div class="stat-info">
                    <h3>Đánh giá</h3>
                    <p class="stat-number" id="avgPerformance">0/10</p>
                    <p class="stat-change positive">Tỷ lệ hoàn thành: 90%</p>
                  </div>
                </div>
                <!-- Card 8 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                  <div class="stat-info">
                    <h3>Đào tạo</h3>
                    <p class="stat-number" id="activeTrainings">0</p>
                    <p class="stat-change">Người tham gia: 45</p>
                  </div>
                </div>
                <!-- Card 9 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-gavel"></i></div>
                  <div class="stat-info">
                    <h3>Kỷ luật</h3>
                    <p class="stat-number" id="disciplinaryCases">0</p>
                    <p class="stat-change negative">Vụ nghiêm trọng: 2</p>
                  </div>
                </div>
                <!-- Card 10 -->
                <div class="swiper-slide stat-card">
                  <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
                  <div class="stat-info">
                    <h3>Tuyển dụng</h3>
                    <p class="stat-number" id="openPositions">0</p>
                    <p class="stat-change positive">Tỷ lệ chuyển đổi: 75%</p>
                  </div>
                </div>
              </div>
              <!-- Nút điều hướng -->
              <div class="swiper-button-next"></div>
              <div class="swiper-button-prev"></div>
              <!-- Thanh chỉ số -->
              <div class="swiper-pagination"></div>
            </div>

            <!-- Charts Section -->
            <div class="row">
                <!-- Department Overview -->
                <div class="col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h5 class="chart-title">Tổng Quan Phòng Ban</h5>
                        </div>
                        <div class="chart-body">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Employee Trends -->
                <div class="col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h5 class="chart-title">Xu Hướng Nhân Viên</h5>
                        </div>
                        <div class="chart-body">
                            <canvas id="trendsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Salary Costs -->
                <div class="col-md-12 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h5 class="chart-title">Chi Phí Lương Hàng Tháng</h5>
                        </div>
                        <div class="chart-body">
                            <canvas id="salaryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Age and Gender Distribution -->
                <div class="col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h5 class="chart-title">Phân Bố Tuổi và Giới Tính</h5>
                        </div>
                        <div class="chart-body">
                            <canvas id="demographicChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Performance Overview -->
                <div class="col-md-6 mb-4">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h5 class="chart-title">Tổng Quan Hiệu Suất</h5>
                        </div>
                        <div class="chart-body">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="feature-cards">
  <button class="feature-card active" style="--main-color: #F1C40F;" type="button">
    <div class="icon-circle">
      <i class="fas fa-calendar-check"></i>
    </div>
    <div class="feature-title">Duyệt đơn nghỉ phép</div>
    <div class="feature-detail">Xem, duyệt/từ chối đơn nghỉ phép, thêm ghi chú và xuất báo cáo nhanh chóng.</div>
  </button>
  <button class="feature-card" style="--main-color: #1ABC9C;" type="button">
    <div class="icon-circle">
      <i class="fas fa-money-bill-wave"></i>
    </div>
    <div class="feature-title">Phê duyệt lương</div>
    <div class="feature-detail">Xem bảng lương, phê duyệt, điều chỉnh và xuất file lương cho nhân viên.</div>
  </button>
  <button class="feature-card" style="--main-color: #9B59B6;" type="button">
    <div class="icon-circle">
      <i class="fas fa-star"></i>
    </div>
    <div class="feature-title">Đánh giá nhân viên</div>
    <div class="feature-detail">Tạo đánh giá, nhập điểm, xem lịch sử và xuất báo cáo đánh giá nhân viên.</div>
  </button>
  <button class="feature-card" style="--main-color: #3498DB;" type="button">
    <div class="icon-circle">
      <i class="fas fa-file-alt"></i>
    </div>
    <div class="feature-title">Báo cáo nhanh</div>
    <div class="feature-detail">Chọn loại báo cáo, tùy chỉnh thời gian, xuất PDF/Excel và lưu mẫu báo cáo.</div>
  </button>
</div>
            <style>
                .feature-cards {
                  display: flex;
                  gap: 32px;
                  justify-content: center;
                  background: #f5f7fa;
                  padding: 40px 0;
                }
                .feature-card {
                  background: #fff;
                  border-radius: 24px;
                  border: none;
                  width: 220px;
                  min-height: 260px;
                  display: flex;
                  flex-direction: column;
                  align-items: center;
                  position: relative;
                  transition: box-shadow 0.25s, transform 0.25s, background 0.25s;
                  cursor: pointer;
                  overflow: hidden;
                  box-shadow: none;
                  outline: none;
                }
                .feature-card.active {
                  background: var(--main-color, #F1C40F);
                  color: #fff;
                }
                .feature-card .icon-circle {
                  margin-top: 36px;
                  width: 80px;
                  height: 80px;
                  border-radius: 50%;
                  background: rgba(255,255,255,0.7);
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  font-size: 38px;
                  color: var(--main-color, #F1C40F);
                  margin-bottom: 24px;
                  border: 2.5px solid var(--main-color, #F1C40F);
                  transition: border 0.25s, background 0.25s, color 0.25s;
                }
                .feature-card:nth-child(2) .icon-circle {
                  color: #1ABC9C;
                  border-color: #1ABC9C;
                }
                .feature-card:nth-child(3) .icon-circle {
                  color: #9B59B6;
                  border-color: #9B59B6;
                }
                .feature-card:nth-child(4) .icon-circle {
                  color: #3498DB;
                  border-color: #3498DB;
                }
                .feature-title {
  font-size: 1.1rem;
  color: #666;
  margin-bottom: 0;
  margin-top: 8px;
  font-weight: 500;
  text-align: center;
  transition: color 0.3s, transform 0.4s cubic-bezier(.4,2,.6,1), opacity 0.4s;
  opacity: 1;
  transform: translateY(0);
}
                .feature-card.active .feature-title {
                  color: #fff;
                }
                .feature-card:hover .feature-title, .feature-card:focus .feature-title {
  color: var(--main-color, #F1C40F);
  transform: translateY(-10px) scale(1.04);
  opacity: 0.85;
}
                .feature-card:focus {
                  outline: 2px solid var(--main-color, #F1C40F);
                }
                .feature-card:hover, .feature-card:focus {
                  box-shadow: 0 8px 32px rgba(52, 152, 219, 0.18);
                  transform: translateY(-4px) scale(1.03);
                  z-index: 2;
                }
                .feature-card:nth-child(2):hover, .feature-card:nth-child(2):focus {
                  border-color: #1ABC9C;
                }
                .feature-card:nth-child(3):hover, .feature-card:nth-child(3):focus {
                  border-color: #9B59B6;
                }
                .feature-card:nth-child(4):hover, .feature-card:nth-child(4):focus {
                  border-color: #3498DB;
                }
                @media (max-width: 1200px) {
                  .feature-cards { gap: 18px; }
                  .feature-card { width: 180px; min-height: 200px; }
                }
                @media (max-width: 900px) {
                  .feature-cards { flex-wrap: wrap; justify-content: flex-start; }
                  .feature-card { width: 48%; margin-bottom: 24px; }
                }
                @media (max-width: 600px) {
                  .feature-cards { flex-direction: column; gap: 16px; padding: 16px 0; }
                  .feature-card { width: 100%; min-height: 140px; }
                }
                .feature-detail {
  opacity: 0;
  pointer-events: none;
  position: absolute;
  left: 0; right: 0; bottom: 0;
  background: rgba(255,255,255,0.98);
  color: #333;
  padding: 20px 16px 16px 16px;
  font-size: 0.98rem;
  border-radius: 0 0 24px 24px;
  min-height: 80px;
  text-align: center;
  transition: opacity 0.4s cubic-bezier(.4,2,.6,1), transform 0.4s cubic-bezier(.4,2,.6,1);
  z-index: 3;
  transform: translateY(20px);
}
.feature-card:hover .feature-detail, .feature-card:focus .feature-detail {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0);
}
            </style>

            <!-- Footer Section -->
            <footer class="dashboard-footer">
                <div class="footer-grid">
                    <!-- Thông tin hệ thống -->
                    <div class="footer-section">
                        <h6 class="footer-title">
                            <i class="fas fa-info-circle"></i>
                            Thông tin hệ thống
                        </h6>
                        <ul class="footer-list">
                            <li>
                                <i class="fas fa-code-branch"></i>
                                Phiên bản: v3.0.1
                            </li>
                            <li>
                                <i class="fas fa-clock"></i>
                                Cập nhật: 15/03/2024
                            </li>
                            <li>
                                <i class="fas fa-database"></i>
                                <span class="status-indicator status-active"></span>
                                Backup: Hoạt động
                            </li>
                            <li>
                                <i class="fas fa-server"></i>
                                Server: Online
                            </li>
                        </ul>
                    </div>

                    <!-- Cảnh báo quan trọng -->
                    <div class="footer-section">
                        <h6 class="footer-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Cảnh báo quan trọng
                        </h6>
                        <ul class="footer-list">
                            <li>
                                <i class="fas fa-file-contract"></i>
                                <span class="status-indicator status-warning"></span>
                                5 hợp đồng sắp hết hạn
                            </li>
                            <li>
                                <i class="fas fa-user-clock"></i>
                                <span class="status-indicator status-warning"></span>
                                3 nhân viên sắp nghỉ hưu
                            </li>
                            <li>
                                <i class="fas fa-calendar-alt"></i>
                                <span class="status-indicator status-active"></span>
                                2 sự kiện sắp tới
                            </li>
                            <li>
                                <i class="fas fa-exclamation-circle"></i>
                                <span class="status-indicator status-error"></span>
                                1 vấn đề cần xử lý
                            </li>
                        </ul>
                    </div>

                    <!-- Thống kê nhanh -->
                    <div class="footer-section">
                        <h6 class="footer-title">
                            <i class="fas fa-chart-line"></i>
                            Thống kê nhanh
                        </h6>
                        <ul class="footer-list">
                            <li>
                                <i class="fas fa-eye"></i>
                                Lượt truy cập: 1,234
                            </li>
                            <li>
                                <i class="fas fa-clock"></i>
                                Thời gian hoạt động: 99.9%
                            </li>
                            <li>
                                <i class="fas fa-history"></i>
                                Hoạt động gần đây: 5 phút trước
                            </li>
                            <li>
                                <i class="fas fa-bug"></i>
                                <span class="status-indicator status-active"></span>
                                Không có lỗi hệ thống
                            </li>
                        </ul>
                    </div>

                    <!-- Liên kết nhanh -->
                    <div class="footer-section">
                        <h6 class="footer-title">
                            <i class="fas fa-link"></i>
                            Liên kết nhanh
                        </h6>
                        <ul class="footer-list">
                            <li>
                                <i class="fas fa-book"></i>
                                <a href="#" class="text-decoration-none">Hướng dẫn sử dụng</a>
                            </li>
                            <li>
                                <i class="fas fa-headset"></i>
                                <a href="#" class="text-decoration-none">Hỗ trợ kỹ thuật</a>
                            </li>
                            <li>
                                <i class="fas fa-bug"></i>
                                <a href="#" class="text-decoration-none">Báo lỗi</a>
                            </li>
                            <li>
                                <i class="fas fa-file-alt"></i>
                                <a href="#" class="text-decoration-none">Tài liệu tham khảo</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </footer>

            <!-- Modern Footer Section V2 -->
            <footer class="modern-footer-v2">
                <div class="footer-waves">
                    <svg class="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none">
                        <defs>
                            <path id="wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
                        </defs>
                        <g class="wave1">
                            <use href="#wave" x="48" y="0" fill="rgba(255,255,255,0.1)" />
                        </g>
                        <g class="wave2">
                            <use href="#wave" x="48" y="3" fill="rgba(255,255,255,0.2)" />
                        </g>
                        <g class="wave3">
                            <use href="#wave" x="48" y="5" fill="rgba(255,255,255,0.3)" />
                        </g>
                    </svg>
                </div>
                
                <div class="footer-content">
                    <div class="footer-grid">
                        <!-- Brand Section -->
                        <div class="footer-brand">
                            <div class="brand-logo">
                                <img src="logo_vnpt.jpg" alt="QLNS Logo" class="logo-img">
                                <span class="brand-name">QLNS</span>
                            </div>
                            <p class="brand-description">
                                Hệ thống quản lý nhân sự thông minh, hiện đại và chuyên nghiệp
                            </p>
                            <div class="social-links">
                                <a href="#" class="social-link" data-tooltip="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-link" data-tooltip="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-link" data-tooltip="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-link" data-tooltip="YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="footer-links-section">
                            <h3 class="section-title">Liên kết nhanh</h3>
                            <ul class="footer-links-list">
                                <li><a href="#" class="footer-link-item">Trang chủ</a></li>
                                <li><a href="#" class="footer-link-item">Giới thiệu</a></li>
                                <li><a href="#" class="footer-link-item">Dịch vụ</a></li>
                                <li><a href="#" class="footer-link-item">Liên hệ</a></li>
                            </ul>
                        </div>

                        <!-- Services -->
                        <div class="footer-links-section">
                            <h3 class="section-title">Dịch vụ</h3>
                            <ul class="footer-links-list">
                                <li><a href="#" class="footer-link-item">Quản lý nhân sự</a></li>
                                <li><a href="#" class="footer-link-item">Tính lương</a></li>
                                <li><a href="#" class="footer-link-item">Chấm công</a></li>
                                <li><a href="#" class="footer-link-item">Đào tạo</a></li>
                            </ul>
                        </div>

                        <!-- Contact Info -->
                        <div class="footer-contact">
                            <h3 class="section-title">Liên hệ</h3>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>123 Đường ABC, Quận XYZ, TP.HCM</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <span>+84 123 456 789</span>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>contact@qlns.vn</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Newsletter Section -->
                    <div class="newsletter-section">
                        <div class="newsletter-content">
                            <h3>Đăng ký nhận tin</h3>
                            <p>Nhận thông tin mới nhất về sản phẩm và dịch vụ</p>
                            <form class="newsletter-form">
                                <input type="email" placeholder="Nhập email của bạn" required>
                                <button type="submit" class="subscribe-btn">
                                    <span>Đăng ký</span>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Copyright -->
                    <div class="footer-bottom">
                        <div class="copyright">
                            © 2024 QLNS. All rights reserved.
                        </div>
                        <div class="footer-bottom-links">
                            <a href="#">Chính sách bảo mật</a>
                            <a href="#">Điều khoản sử dụng</a>
                            <a href="#">Sơ đồ trang</a>
                        </div>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</div>

<!-- Chat Modal -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 1200px; margin: 1.75rem auto; max-height: 98vh;">
        <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
            <div class="modal-body p-0" style="background: transparent;">
                <iframe src="chat_widget.php" style="width: 100%; height: 800px; max-height: 90vh; border: none; border-radius: 16px; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Overlay for chat modal -->
<div id="chatOverlay" style="display:none;"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Chat functionality
    const chatButton = document.getElementById('chatButton');
    const chatModal = new bootstrap.Modal(document.getElementById('chatModal'));
    const chatOverlay = document.getElementById('chatOverlay');
    
    chatButton.addEventListener('click', function() {
        chatModal.show();
        chatOverlay.style.display = 'block';
    });

    // Khi modal đóng (bằng nút X hoặc sự kiện khác)
    document.getElementById('chatModal').addEventListener('hidden.bs.modal', function () {
        chatOverlay.style.display = 'none';
    });

    // Listen for close message from iframe
    window.addEventListener('message', function(event) {
        if(event.data === 'closeChatModal') {
            chatModal.hide();
            chatOverlay.style.display = 'none';
        }
    });

    // Quick Actions redirects
    document.querySelectorAll('.feature-card').forEach(card => {
        card.addEventListener('click', function() {
            window.location.href = 'developing.html';
        });
    });

    // Language switch redirect
    document.getElementById('languageSwitch').addEventListener('change', function() {
        window.location.href = 'developing.html';
    });

    // Dark mode toggle redirect
    document.getElementById('darkModeToggle').addEventListener('click', function() {
        window.location.href = 'developing.html';
    });

    // Search box redirect
    document.querySelector('.search-box').addEventListener('focus', function() {
        window.location.href = 'developing.html';
    });

    // Notification bell redirect
    document.getElementById('notificationsDropdown').addEventListener('click', function() {
        window.location.href = 'developing.html';
    });

    // Footer links redirects
    document.querySelectorAll('.footer-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'developing.html';
        });
    });

    // Footer section links redirects
    document.querySelectorAll('.footer-section a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'developing.html';
        });
    });

    // Modern footer links redirects
    document.querySelectorAll('.modern-footer .footer-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'developing.html';
        });
    });
});
</script>

<script>
// Function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Define chart colors
const chartColors = {
    yellow: '#FFD700',    // Vàng chanh
    coral: '#FF6F61',     // Cam san hô
    neonPink: '#FF1493',  // Hồng neon
    turquoise: '#00CED1', // Xanh ngọc
    neonGreen: '#39FF14', // Xanh lá neon
    purple: '#BA55D3',    // Tím hoa cà
    red: '#FF0000',       // Đỏ tươi
    blue: '#1E90FF',      // Xanh biển sáng
    pink: '#FFB6C1',      // Hồng đào
    orange: '#FFA500'     // Vàng nghệ
};

// Function to load data from API
async function loadChartData() {
    try {
        // Load Department Overview
        const deptResponse = await fetch('Charts_Section_api.php?action=top-departments');
        const deptData = await deptResponse.json();
        renderDepartmentChart(deptData);

        // Load Employee Trends
        const trendResponse = await fetch('Charts_Section_api.php?action=employee-trend');
        const trendData = await trendResponse.json();
        renderTrendsChart(trendData.data);

        // Load Monthly Salary Costs
        const salaryResponse = await fetch('Charts_Section_api.php?action=monthly-salary-costs');
        const salaryData = await salaryResponse.json();
        renderSalaryChart(salaryData);

        // Load Age Distribution
        const ageResponse = await fetch('Charts_Section_api.php?action=age-distribution');
        const ageData = await ageResponse.json();
        renderDemographicChart(ageData);

        // Load Performance Overview
        const perfResponse = await fetch('Charts_Section_api.php?action=performance-evaluation');
        const perfData = await perfResponse.json();
        renderPerformanceChart(perfData);
    } catch (error) {
        console.error('Error loading chart data:', error);
    }
}

// Render Department Chart
function renderDepartmentChart(data) {
    const ctx = document.getElementById('departmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.department),
            datasets: [{
                label: 'Số lượng nhân viên',
                data: data.map(item => item.employeeCount),
                backgroundColor: chartColors.blue,
                borderColor: chartColors.blue,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số lượng'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Phòng ban'
                    }
                }
            }
        }
    });
}

// Render Trends Chart
function renderTrendsChart(data) {
    const ctx = document.getElementById('trendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => `Tháng ${item.month}`),
            datasets: [{
                label: 'Nhân viên mới',
                data: data.map(item => item.new_hires),
                borderColor: chartColors.neonGreen,
                backgroundColor: chartColors.neonGreen + '20',
                tension: 0.1,
                fill: true
            }, {
                label: 'Nhân viên nghỉ việc',
                data: data.map(item => item.terminated),
                borderColor: chartColors.coral,
                backgroundColor: chartColors.coral + '20',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Số lượng'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Thời gian'
                    }
                }
            }
        }
    });
}

// Render Salary Chart
function renderSalaryChart(data) {
    const ctx = document.getElementById('salaryChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => `Tháng ${item.month}`),
            datasets: [{
                label: 'Lương cơ bản',
                data: data.map(item => item.base_salary),
                backgroundColor: chartColors.yellow
            }, {
                label: 'Phụ cấp',
                data: data.map(item => item.allowances),
                backgroundColor: chartColors.orange
            }, {
                label: 'Thưởng',
                data: data.map(item => item.bonuses),
                backgroundColor: chartColors.turquoise
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Số tiền (VNĐ)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Thời gian'
                    }
                }
            }
        }
    });
}

// Render Demographic Chart
function renderDemographicChart(data) {
    const ctx = document.getElementById('demographicChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [...new Set(data.map(item => item.ageGroup))],
            datasets: [{
                label: 'Nam',
                data: data.filter(item => item.gender === 'Male').map(item => item.count),
                backgroundColor: chartColors.blue
            }, {
                label: 'Nữ',
                data: data.filter(item => item.gender === 'Female').map(item => item.count),
                backgroundColor: chartColors.pink
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số lượng'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Nhóm tuổi'
                    }
                }
            }
        }
    });
}

// Render Performance Chart
function renderPerformanceChart(data) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Hiệu suất cao', 'Hiệu suất thấp', 'Trung bình'],
            datasets: [{
                data: [
                    data.highPerformers,
                    data.lowPerformers,
                    data.totalEvaluations - data.highPerformers - data.lowPerformers
                ],
                backgroundColor: [
                    chartColors.neonGreen,
                    chartColors.red,
                    chartColors.purple
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 12
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Phân bố hiệu suất nhân viên',
                    font: {
                        size: 14
                    }
                }
            }
        }
    });
}

// Load chart data when page loads
document.addEventListener('DOMContentLoaded', loadChartData);
</script>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
const statsSwiper = new Swiper('.stats-swiper', {
  slidesPerView: 3,
  spaceBetween: 0,
  centeredSlides: true,
  loop: true,
  speed: 700,
  autoplay: {
    delay: 3500,
    disableOnInteraction: false,
  },
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },
  pagination: {
    el: '.swiper-pagination',
    clickable: true,
  },
  breakpoints: {
    0: { slidesPerView: 1 },
    600: { slidesPerView: 1.2 },
    900: { slidesPerView: 3 }
  }
});
</script>

<style>
.sidebar {
    max-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.nav-list {
    flex: 1 1 auto;
    max-height: calc(100vh - 120px); /* trừ phần header sidebar */
    overflow-y: auto;
    padding-right: 4px; /* để không che mất thanh cuộn */
}
/* Tùy chỉnh thanh cuộn cho đẹp */
.nav-list::-webkit-scrollbar {
    width: 7px;
    background: #e3e6f0;
    border-radius: 4px;
}
.nav-list::-webkit-scrollbar-thumb {
    background: #b0b8c1;
    border-radius: 4px;
}
</style>

<script>
// Function to fetch statistics data
async function fetchStatistics() {
    try {
        // Show loading state
        document.querySelectorAll('.stat-number').forEach(el => {
            el.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        });

        const response = await fetch('Statistics_Cards.php?action=general-stats');
        const data = await response.json();
        
        // Update total employees card
        document.getElementById('totalEmployees').textContent = data.totalEmployees;
        const employeeChange = document.querySelector('#totalEmployees').nextElementSibling;
        employeeChange.textContent = `Đang làm việc: ${data.employeeStatus.active} | Nghỉ phép: ${data.employeeStatus.onLeave}`;
        
        // Update new employees card
        document.getElementById('newEmployees').textContent = data.newEmployees.count;
        const newEmployeeChange = document.querySelector('#newEmployees').nextElementSibling;
        newEmployeeChange.textContent = `Lương trung bình: ${data.newEmployees.avgSalary} VNĐ`;
        
        // Update terminated employees card
        document.getElementById('resignedEmployees').textContent = data.terminatedEmployees.total;
        const terminatedChange = document.querySelector('#resignedEmployees').nextElementSibling;
        terminatedChange.textContent = `Tỷ lệ thôi việc: ${((data.terminatedEmployees.total / data.totalEmployees) * 100).toFixed(1)}%`;
        
        // Update salary fund card
        document.getElementById('totalSalary').textContent = data.salaryInfo.total + ' VNĐ';
        const salaryChange = document.querySelector('#totalSalary').nextElementSibling;
        salaryChange.textContent = `Tổng lương cơ bản của ${data.employeeStatus.active} nhân viên`;
        
        // Update departments card
        document.getElementById('totalDepartments').textContent = data.departmentInfo.total;
        const deptChange = document.querySelector('#totalDepartments').nextElementSibling;
        deptChange.textContent = `Số quản lý: ${data.departmentInfo.managers}`;
        
        // Update leave card
        document.getElementById('onLeave').textContent = data.leaveInfo.total;
        const leaveChange = document.querySelector('#onLeave').nextElementSibling;
        leaveChange.textContent = `Nghỉ phép: ${data.leaveInfo.annual} | Nghỉ ốm: ${data.leaveInfo.sick} | Khác: ${data.leaveInfo.other}`;
        
        // Update performance card
        document.getElementById('avgPerformance').textContent = data.performanceInfo.avgScore + '/5';
        const perfChange = document.querySelector('#avgPerformance').nextElementSibling;
        perfChange.textContent = `Xuất sắc: ${data.performanceInfo.highPerformers} | Cần cải thiện: ${data.performanceInfo.lowPerformers}`;
        
        // Update training card
        document.getElementById('activeTrainings').textContent = data.trainingInfo.activeSessions;
        const trainingChange = document.querySelector('#activeTrainings').nextElementSibling;
        trainingChange.textContent = `Khóa học: ${data.trainingInfo.uniqueCourses} | Người tham gia: ${data.trainingInfo.participants}`;
        
        // Update recruitment card
        document.getElementById('openPositions').textContent = data.recruitmentInfo.openPositions;
        const recruitmentChange = document.querySelector('#openPositions').nextElementSibling;
        recruitmentChange.textContent = `Vị trí đang tuyển: ${data.recruitmentInfo.openPositions}`;
        
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu thống kê:', error);
        // Show error state
        document.querySelectorAll('.stat-number').forEach(el => {
            el.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
        });
    }
}

// Fetch statistics when page loads
document.addEventListener('DOMContentLoaded', fetchStatistics);

// Refresh statistics every 5 minutes
setInterval(fetchStatistics, 300000);
</script>

<style>
/* Modern Footer V2 - Khoa học, thu hút, hiện đại */
.modern-footer-v2 {
    background: linear-gradient(120deg, #181f36 60%, #233554 100%);
    color: #fff;
    border-bottom-right-radius: 80px;
    font-family: 'Roboto', Arial, sans-serif;
    margin-top: 0;
    padding-top: 48px;
    padding-bottom: 0;
    position: relative;
    overflow: hidden;
}
.modern-footer-v2 .footer-waves {
    position: absolute;
    top: -30px;
    left: 0;
    width: 100%;
    z-index: 1;
}
.modern-footer-v2 .footer-content {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px 24px 24px;
}
.modern-footer-v2 .footer-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 32px;
    margin-bottom: 32px;
}
.modern-footer-v2 .footer-brand {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.modern-footer-v2 .brand-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}
.modern-footer-v2 .logo-img {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.modern-footer-v2 .brand-name {
    font-size: 1.7rem;
    font-weight: bold;
    letter-spacing: 1px;
    color: #FFD700;
}
.modern-footer-v2 .brand-description {
    font-size: 1.05rem;
    color: #b0b8c1;
    margin-bottom: 18px;
    margin-top: 4px;
}
.modern-footer-v2 .social-links {
    display: flex;
    gap: 14px;
    margin-top: 8px;
}
.modern-footer-v2 .social-link {
    color: #b0b8c1;
    font-size: 20px;
    transition: color 0.2s, transform 0.2s;
}
.modern-footer-v2 .social-link:hover {
    color: #FFD700;
    transform: scale(1.18) translateY(-2px);
}
.modern-footer-v2 .footer-links-section {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.modern-footer-v2 .section-title {
    font-size: 1.13rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 14px;
    letter-spacing: 0.5px;
}
.modern-footer-v2 .footer-links-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.modern-footer-v2 .footer-link-item {
    color: #b0b8c1;
    text-decoration: none;
    font-size: 1rem;
    display: block;
    margin-bottom: 10px;
    transition: color 0.2s;
}
.modern-footer-v2 .footer-link-item:hover {
    color: #FFD700;
    text-decoration: underline;
}
.modern-footer-v2 .footer-contact {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.modern-footer-v2 .contact-info {
    margin-top: 8px;
}
.modern-footer-v2 .contact-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: #b0b8c1;
    font-size: 1rem;
}
.modern-footer-v2 .contact-item i {
    color: #FFD700;
    font-size: 1.1rem;
}
.modern-footer-v2 .newsletter-section {
    background: rgba(255,255,255,0.07);
    border-radius: 18px;
    padding: 24px 18px 18px 18px;
    margin: 0 auto 18px auto;
    max-width: 420px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    position: relative;
    z-index: 2;
}
.modern-footer-v2 .newsletter-content h3 {
    color: #FFD700;
    font-size: 1.18rem;
    font-weight: 600;
    margin-bottom: 8px;
}
.modern-footer-v2 .newsletter-content p {
    color: #b0b8c1;
    font-size: 1rem;
    margin-bottom: 12px;
}
.modern-footer-v2 .newsletter-form {
    display: flex;
    gap: 8px;
}
.modern-footer-v2 .newsletter-form input[type="email"] {
    border: none;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 1rem;
    outline: none;
    width: 180px;
    background: #fff;
    color: #222;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.modern-footer-v2 .subscribe-btn {
    background: #FFD700;
    color: #181f36;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.modern-footer-v2 .subscribe-btn:hover {
    background: #fff;
    color: #181f36;
}
.modern-footer-v2 .footer-bottom {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #232a45;
    padding: 18px 0 0 0;
    margin-top: 18px;
    font-size: 0.98rem;
    color: #b0b8c1;
}
.modern-footer-v2 .footer-bottom-links {
    display: flex;
    gap: 18px;
}
.modern-footer-v2 .footer-bottom-links a {
    color: #b0b8c1;
    text-decoration: none;
    font-size: 0.98rem;
    transition: color 0.2s;
}
.modern-footer-v2 .footer-bottom-links a:hover {
    color: #FFD700;
    text-decoration: underline;
}
@media (max-width: 1200px) {
    .modern-footer-v2 .footer-content { padding-left: 12px; padding-right: 12px; }
    .modern-footer-v2 .footer-grid { gap: 18px; }
}
@media (max-width: 992px) {
    .modern-footer-v2 .footer-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .modern-footer-v2 .footer-grid { grid-template-columns: 1fr; gap: 12px; }
    .modern-footer-v2 { border-bottom-right-radius: 32px; }
}
@media (max-width: 500px) {
    .modern-footer-v2 { border-bottom-right-radius: 12px; padding-top: 24px; }
    .modern-footer-v2 .brand-name { font-size: 1.1rem; }
    .modern-footer-v2 .footer-content { padding: 0 4px 12px 4px; }
}

/* Hiệu ứng chuyển động cho sóng footer */
.modern-footer-v2 .waves {
    width: 100%;
    height: 60px;
    min-height: 40px;
    max-height: 80px;
    display: block;
}
.modern-footer-v2 .wave1 {
    animation: waveMove1 10s linear infinite;
}
.modern-footer-v2 .wave2 {
    animation: waveMove2 16s linear infinite;
}
.modern-footer-v2 .wave3 {
    animation: waveMove3 22s linear infinite;
}
@keyframes waveMove1 {
    0% { transform: translateX(0); }
    100% { transform: translateX(-80px); }
}
@keyframes waveMove2 {
    0% { transform: translateX(0); }
    100% { transform: translateX(-120px); }
}
@keyframes waveMove3 {
    0% { transform: translateX(0); }
    100% { transform: translateX(-200px); }
}

/* Responsive tối ưu cho modern-footer-v2 */
@media (max-width: 992px) {
  .modern-footer-v2 .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: 18px;
  }
}
@media (max-width: 768px) {
  .modern-footer-v2 .footer-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }
  .modern-footer-v2 .footer-brand,
  .modern-footer-v2 .footer-links-section,
  .modern-footer-v2 .footer-contact {
    align-items: center;
    text-align: center;
  }
  .modern-footer-v2 .brand-logo {
    justify-content: center;
  }
  .modern-footer-v2 .social-links {
    justify-content: center;
  }
  .modern-footer-v2 .newsletter-section {
    max-width: 100%;
    margin: 18px 0;
    padding: 18px 8px 14px 8px;
  }
  .modern-footer-v2 .newsletter-form {
    flex-direction: column;
    gap: 8px;
    align-items: stretch;
  }
  .modern-footer-v2 .newsletter-form input[type="email"],
  .modern-footer-v2 .subscribe-btn {
    width: 100%;
    font-size: 1rem;
  }
  .modern-footer-v2 .footer-bottom {
    flex-direction: column;
    gap: 8px;
    text-align: center;
    padding: 12px 0 0 0;
  }
  .modern-footer-v2 .footer-bottom-links {
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
  }
}
@media (max-width: 500px) {
  .modern-footer-v2 {
    border-bottom-right-radius: 8px;
    padding-top: 16px;
  }
  .modern-footer-v2 .footer-content {
    padding: 0 2px 8px 2px;
  }
  .modern-footer-v2 .brand-name {
    font-size: 1rem;
  }
  .modern-footer-v2 .logo-img {
    width: 38px;
    height: 38px;
    border-radius: 8px;
  }
  .modern-footer-v2 .section-title {
    font-size: 1rem;
  }
  .modern-footer-v2 .footer-link-item,
  .modern-footer-v2 .contact-item {
    font-size: 0.97rem;
  }
  .modern-footer-v2 .newsletter-content h3 {
    font-size: 1rem;
  }
}
</style>

</body>
</html>