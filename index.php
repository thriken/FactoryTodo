<?php
// 主入口文件
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 检查用户是否已登录
$isLoggedIn = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'];

// 如果未登录且不是访问登录页面，则重定向到登录页面
if (!$isLoggedIn && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}

// 简单的路由处理
$page = $_GET['page'] ?? 'home';

// 页面逻辑处理
switch ($page) {
    case 'tasks':
        $tasks = getAllTasks();
        break;
    case 'users':
        $users = getAllUsers();
        break;
    default:
        $tasks = getRecentTasks();
        break;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工厂任务单系统</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">工厂任务单系统</h1>
                <?php if ($isLoggedIn): ?>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">欢迎, <?= htmlspecialchars($_SESSION['user_email'] ?? '用户') ?></span>
                        <a href="admin.php" class="text-blue-600 hover:underline">管理面板</a>
                        <a href="logout.php" class="text-blue-600 hover:underline">登出</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($isLoggedIn): ?>
                <nav class="mt-4">
                    <ul class="flex space-x-4">
                        <li><a href="index.php" class="text-blue-600 hover:underline">首页</a></li>
                        <li><a href="index.php?page=tasks" class="text-blue-600 hover:underline">任务管理</a></li>
                        <li><a href="index.php?page=users" class="text-blue-600 hover:underline">用户管理</a></li>
                    </ul>
                </nav>
            <?php endif; ?>
        </header>

        <main>
            <?php if (!$isLoggedIn): ?>
                <div class="text-center py-12">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">欢迎使用工厂任务单系统</h2>
                    <p class="text-gray-600 mb-6">请登录以访问系统功能</p>
                    <a href="login.php" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        登录系统
                    </a>
                </div>
            <?php elseif ($page === 'tasks'): ?>
                <h2 class="text-2xl font-bold mb-4">任务管理</h2>
                <div id="tasks-list">
                    <?php foreach ($tasks as $task): ?>
                        <div class="bg-white rounded-lg shadow p-4 mb-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-semibold"><?= htmlspecialchars($task['title']) ?></h3>
                                    <p class="text-gray-600"><?= htmlspecialchars($task['description']) ?></p>
                                </div>
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                    <?= htmlspecialchars($task['status']) ?>
                                </span>
                            </div>
                            <div class="mt-3 flex space-x-2">
                                <button class="update-task-status text-xs bg-green-100 text-green-800 px-2 py-1 rounded hover:bg-green-200" 
                                        data-task-id="<?= $task['id'] ?>" data-status="completed">
                                    完成
                                </button>
                                <button class="update-task-status text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded hover:bg-yellow-200" 
                                        data-task-id="<?= $task['id'] ?>" data-status="in-progress">
                                    进行中
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($page === 'users'): ?>
                <h2 class="text-2xl font-bold mb-4">用户管理</h2>
                <div id="users-list">
                    <?php foreach ($users as $user): ?>
                        <div class="bg-white rounded-lg shadow p-4 mb-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-semibold"><?= htmlspecialchars($user['full_name']) ?></h3>
                                    <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                                        <?= htmlspecialchars($user['role']) ?>
                                    </span>
                                    <p class="text-xs text-gray-500 mt-1"><?= $user['is_main_manager'] ? '主负责人' : '普通员工' ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <h2 class="text-2xl font-bold mb-4">最新任务</h2>
                <div id="recent-tasks">
                    <?php foreach ($tasks as $task): ?>
                        <div class="bg-white rounded-lg shadow p-4 mb-4">
                            <h3 class="text-xl font-semibold"><?= htmlspecialchars($task['title']) ?></h3>
                            <p class="text-gray-600"><?= htmlspecialchars($task['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

        <footer class="mt-8 text-center text-gray-600">
            <p>工厂任务单系统 &copy; 2023</p>
        </footer>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>