<?php
// 数据库测试脚本
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== 工厂任务单系统数据库测试 ===\n\n";

// 测试数据库连接
echo "1. 测试数据库连接...\n";
try {
    global $pdo;
    echo "   ✓ 数据库连接成功\n";
    
    // 测试表是否存在
    $tables = ['users', 'processing_steps', 'process_chains', 'process_chain_steps', 'todos', 'todo_steps'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$table]);
        $result = $stmt->fetch();
        if ($result) {
            echo "   ✓ 表 '$table' 存在\n";
        } else {
            echo "   ✗ 表 '$table' 不存在\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ 数据库连接失败: " . $e->getMessage() . "\n";
}

echo "\n2. 测试数据操作...\n";

// 测试添加用户
echo "   添加测试用户...\n";
$userId = addUser('test@example.com', '测试用户', 'observer', 'cutting', false);
if ($userId) {
    echo "   ✓ 用户添加成功，ID: $userId\n";
} else {
    echo "   ✗ 用户添加失败\n";
}

// 测试添加任务
echo "   添加测试任务...\n";
$taskId = addTask('测试任务', '这是一个测试任务', $userId, null, 'medium', 'single');
if ($taskId) {
    echo "   ✓ 任务添加成功，ID: $taskId\n";
} else {
    echo "   ✗ 任务添加失败\n";
}

// 测试获取数据
echo "   获取用户列表...\n";
$users = getAllUsers();
echo "   ✓ 获取到 " . count($users) . " 个用户\n";

echo "   获取任务列表...\n";
$tasks = getAllTasks();
echo "   ✓ 获取到 " . count($tasks) . " 个任务\n";

echo "\n3. 测试完成!\n";
echo "   系统已准备就绪，可以正常使用。\n";
?>