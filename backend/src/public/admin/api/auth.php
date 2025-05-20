<?php
// Kiểm tra quyền admin
if ($_GET['action'] === 'checkRole') {
    header('Content-Type: application/json');
    echo json_encode([
        'isAdmin' => isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'
    ]);
    exit;
} 