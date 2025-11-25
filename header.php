<?php
// 头部文件
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title ?? '工厂任务单系统') ?></title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/app.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 text-center sm:text-left">工厂任务单系统</h1>
            <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-4">
                    <?php 
                    // 获取用户部门信息
                    $userDepartment = '';
                    if (isset($_SESSION['user_id'])) {
                        $user = getUserById($_SESSION['user_id']);
                        if ($user) {
                            $userDepartment = DEPARTMENTS[$user['department']] ?? $user['department'];
                        }
                    }
                    ?>
                    <span class="text-gray-600 text-base sm:text-lg text-center sm:text-left">欢迎, <?= htmlspecialchars($_SESSION['full_name'] ?? '用户') ?> <?= $userDepartment ? "($userDepartment)" : '' ?></span>
                    <a href="logout.php" class="text-blue-600 hover:underline text-center sm:text-left">登出</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>