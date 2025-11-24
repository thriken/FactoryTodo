<?php
// 头部文件
require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '工厂任务单系统') ?></title>
    <link href="https://lib.baomitu.com/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">工厂任务单系统</h1>
            <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']): ?>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">欢迎, <?= htmlspecialchars($_SESSION['full_name'] ?? '用户') ?></span>
                    <a href="users.php" class="text-red-600 hover:underline">用户管理</a>
                    <a href="process-chains.php" class="text-red-600 hover:underline">工序链管理</a>
                    <a href="../logout.php" class="text-blue-600 hover:underline">登出</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>