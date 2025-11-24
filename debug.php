<?php
// 调试页面
require_once 'config/config.php';

// 检查是否启用调试模式
if (!DEBUG_MODE) {
    header('HTTP/1.0 403 Forbidden');
    die('调试模式未启用');
}

// 检查用户是否已登录
session_start();
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    header('Location: login.php');
    exit;
}

// 获取日志内容
$logContent = '';
if (file_exists(LOG_FILE)) {
    $logContent = file_get_contents(LOG_FILE);
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>调试信息 - 工厂任务单系统</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">调试信息</h1>
                <a href="index.php" class="text-blue-600 hover:underline">返回主页</a>
            </div>
            
            <div class="mb-4">
                <h2 class="text-xl font-semibold mb-2">系统状态</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded">
                        <h3 class="font-medium">调试模式</h3>
                        <p class="text-sm text-gray-600"><?= DEBUG_MODE ? '已启用' : '已禁用' ?></p>
                    </div>
                    <div class="bg-green-50 p-4 rounded">
                        <h3 class="font-medium">数据库调试</h3>
                        <p class="text-sm text-gray-600"><?= DB_DEBUG ? '已启用' : '已禁用' ?></p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded">
                        <h3 class="font-medium">日志文件</h3>
                        <p class="text-sm text-gray-600"><?= LOG_FILE ?></p>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xl font-semibold">日志内容</h2>
                    <button onclick="clearLogs()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">清空日志</button>
                </div>
                <div class="bg-gray-900 text-green-400 p-4 rounded font-mono text-sm overflow-auto max-h-96">
                    <pre><?= htmlspecialchars($logContent) ?></pre>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function clearLogs() {
            if (confirm('确定要清空日志吗？')) {
                fetch('api.php?action=clear_logs', {
                    method: 'POST'
                }).then(() => {
                    location.reload();
                });
            }
        }
    </script>
</body>
</html>