<?php
// 管理入口文件
session_start();

// 检查用户是否已登录且具有管理员权限
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    header('Location: login.php');
    exit;
}

// 重定向到管理页面
header('Location: admin/index.php');
exit;
?>