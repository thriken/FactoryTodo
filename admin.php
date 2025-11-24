<?php
// 管理页面
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 检查用户是否已登录
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    header('Location: login.php');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $email = $_POST['email'] ?? '';
                $fullName = $_POST['full_name'] ?? '';
                $role = $_POST['role'] ?? 'observer';
                $department = $_POST['department'] ?? 'cutting';
                $isMainManager = isset($_POST['is_main_manager']) ? true : false;
                
                if (!empty($email) && !empty($fullName)) {
                    $userId = addUser($email, $fullName, $role, $department, $isMainManager);
                    $message = $userId ? "用户添加成功" : "用户添加失败";
                } else {
                    $message = "请填写必填字段";
                }
                break;
                
            case 'add_task':
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $createdBy = 1; // 简化处理，实际应该从会话中获取
                $priority = $_POST['priority'] ?? 'medium';
                $processChainType = $_POST['process_chain_type'] ?? 'single';
                
                if (!empty($title)) {
                    $taskId = addTask($title, $description, $createdBy, null, $priority, $processChainType);
                    $message = $taskId ? "任务添加成功" : "任务添加失败";
                } else {
                    $message = "请填写任务标题";
                }
                break;
        }
    }
}

// 获取数据用于显示
$users = getAllUsers();
$tasks = getAllTasks();
$processingSteps = getAllProcessingSteps();
$processChains = getAllProcessChains();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - 工厂任务单系统</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">管理面板</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">欢迎, <?= htmlspecialchars($_SESSION['user_email'] ?? '管理员') ?></span>
                    <a href="index.php" class="text-blue-600 hover:underline">返回首页</a>
                    <a href="logout.php" class="text-blue-600 hover:underline">登出</a>
                </div>
            </div>
            <nav class="mt-4">
                <ul class="flex space-x-4">
                    <li><a href="#users" class="text-blue-600 hover:underline">用户管理</a></li>
                    <li><a href="#tasks" class="text-blue-600 hover:underline">任务管理</a></li>
                    <li><a href="#steps" class="text-blue-600 hover:underline">工序管理</a></li>
                    <li><a href="#chains" class="text-blue-600 hover:underline">工序链管理</a></li>
                </ul>
            </nav>
        </header>

        <?php if (isset($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <main class="space-y-8">
            <!-- 用户管理 -->
            <section id="users">
                <h2 class="text-2xl font-bold mb-4">用户管理</h2>
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">添加新用户</h3>
                    <form id="add-user-form" class="space-y-4">
                        <input type="hidden" name="action" value="add_user">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">邮箱 *</label>
                                <input type="email" id="email" name="email" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700">姓名 *</label>
                                <input type="text" id="full_name" name="full_name" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">角色</label>
                                <select id="role" name="role"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="observer">观察者</option>
                                    <option value="process-manager">工序负责人</option>
                                    <option value="customer-service">客服</option>
                                    <option value="boss">老板</option>
                                    <option value="super-admin">超级管理员</option>
                                </select>
                            </div>
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700">部门</label>
                                <select id="department" name="department"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="cutting">切割</option>
                                    <option value="tempering">钢化</option>
                                    <option value="laminating">夹层</option>
                                    <option value="insulating">中空</option>
                                    <option value="warehouse">库房</option>
                                    <option value="packing">打包</option>
                                    <option value="shipping">发货</option>
                                    <option value="qc">质检</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="is_main_manager" name="is_main_manager" value="1"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_main_manager" class="ml-2 block text-sm text-gray-900">
                                主负责人
                            </label>
                        </div>
                        <div>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                添加用户
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">姓名</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">邮箱</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">部门</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">主负责人</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="users-table-body">
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($user['full_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($user['role']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($user['department']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $user['is_main_manager'] ? '是' : '否' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 任务管理 -->
            <section id="tasks">
                <h2 class="text-2xl font-bold mb-4">任务管理</h2>
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-xl font-semibold mb-4">添加新任务</h3>
                    <form id="add-task-form" class="space-y-4">
                        <input type="hidden" name="action" value="add_task">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">任务标题 *</label>
                                <input type="text" id="title" name="title" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700">优先级</label>
                                <select id="priority" name="priority"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="low">低</option>
                                    <option value="medium" selected>中</option>
                                    <option value="high">高</option>
                                    <option value="urgent">紧急</option>
                                    <option value="critical">不惜代价</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">任务描述</label>
                                <textarea id="description" name="description" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                            <div>
                                <label for="process_chain_type" class="block text-sm font-medium text-gray-700">工序链类型</label>
                                <select id="process_chain_type" name="process_chain_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="single">单片</option>
                                    <option value="insulating">中空</option>
                                    <option value="laminating">夹层</option>
                                    <option value="laminating-insulating">夹层中空</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                添加任务
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">任务标题</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">优先级</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tasks-table-body">
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($task['title']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($task['status']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($task['priority']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($task['created_at']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 工序管理 -->
            <section id="steps">
                <h2 class="text-2xl font-bold mb-4">工序管理</h2>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">工序名称</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">工序类型</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">顺序</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">启用</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($processingSteps as $step): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($step['name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($step['process_type']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($step['order']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $step['enabled'] ? '是' : '否' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 工序链管理 -->
            <section id="chains">
                <h2 class="text-2xl font-bold mb-4">工序链管理</h2>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">工序链名称</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">类型</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">启用</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($processChains as $chain): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($chain['name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($chain['type']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $chain['enabled'] ? '是' : '否' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <footer class="mt-8 text-center text-gray-600">
            <p>工厂任务单系统 &copy; 2023</p>
        </footer>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        $(document).ready(function() {
            // 平滑滚动到锚点
            $('a[href^="#"]').on('click', function(event) {
                var target = $($(this).attr('href'));
                if (target.length) {
                    event.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 20
                    }, 500);
                }
            });
        });
    </script>
</body>
</html>