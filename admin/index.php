<?php
// 管理页面导航
session_start();
require_once '../config/database.php';
require_once '../config/const.php';
require_once '../includes/functions.php';
require_once '../config/config.php';

// 检查用户是否已登录
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    header('Location: ../login.php');
    exit;
}

// 检查用户是否有管理权限（只有超级管理员可以访问）
if (!hasAdminAccess($_SESSION['role'] ?? '')) {
    header('Location: ../index.php');
    exit;
}

// 设置页面标题
$title = '管理面板 - 工厂任务单系统';
?>
<?php include 'header.php'; ?>

    <div class="flex-grow container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">管理面板</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="users.php" class="block p-6 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <h3 class="text-xl font-semibold text-blue-700 mb-2">用户管理</h3>
                    <p class="text-gray-600">管理系统用户，包括添加、编辑和删除用户</p>
                </a>
                
                <a href="process-chains.php" class="block p-6 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <h3 class="text-xl font-semibold text-green-700 mb-2">工序链管理</h3>
                    <p class="text-gray-600">管理系统工序链，配置业务流程和步骤</p>
                </a>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>