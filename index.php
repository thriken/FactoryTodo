<?php
// 主入口文件
session_start();
require_once 'config/database.php';
require_once 'config/const.php';
require_once 'includes/functions.php';

// 检查用户是否已登录
$isLoggedIn = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'];

// 如果未登录且不是访问登录页面，则重定向到登录页面
if (!$isLoggedIn && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}

// 简单的路由处理 - 前台只显示任务相关功能
$page = $_GET['page'] ?? 'home';

// 设置页面标题
switch ($page) {
    case 'tasks':
        $title = '任务管理 - 工厂任务单系统';
        break;
    default:
        $title = '首页 - 工厂任务单系统';
        break;
}

// 页面逻辑处理 - 仅限任务相关
switch ($page) {
    case 'tasks':
        $tasks = getAllTasks();
        break;
    default:
        $tasks = getRecentTasks();
        break;
}

// 获取部门信息用于显示
$departments = DEPARTMENTS;
?>

<?php include 'header.php'; ?>

<div class="flex-grow container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <?php if ($page === 'tasks'): ?>
            <!-- 任务列表页面 -->
            <h2 class="text-2xl font-bold mb-6 text-gray-800">任务管理</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">任务标题</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">描述</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">优先级</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分配给</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($task['title']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($task['description']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= PRIORITY[$task['priority']] ?? $task['priority'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= PROCESS_STATUS[$task['status']] ?? $task['status'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $task['assignee_id'] ? getUserById($task['assignee_id'])['full_name'] ?? '未知用户' : '未分配' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $task['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- 首页 -->
            <h2 class="text-2xl font-bold mb-6 text-gray-800">欢迎使用工厂任务单系统</h2>
            <p class="text-gray-600 mb-6">这是一个简单的任务管理系统，用于跟踪工厂生产任务。</p>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">快速导航</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="index.php?page=tasks" class="block p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                        <h4 class="font-medium text-blue-700">任务管理</h4>
                        <p class="text-sm text-gray-600 mt-1">查看和管理所有任务</p>
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <a href="admin.php" class="block p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                            <h4 class="font-medium text-green-700">管理面板</h4>
                            <p class="text-sm text-gray-600 mt-1">管理用户和系统设置</p>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="block p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                            <h4 class="font-medium text-green-700">登录</h4>
                            <p class="text-sm text-gray-600 mt-1">登录系统进行管理</p>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <h3 class="text-xl font-semibold mb-4 text-gray-700">最近任务</h3>
                <?php if (!empty($tasks)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">任务标题</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">优先级</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($task['title']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= PRIORITY[$task['priority']] ?? $task['priority'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= PROCESS_STATUS[$task['status']] ?? $task['status'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $task['created_at'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">暂无任务</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>