<?php
// 用户管理页面
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
$title = '用户管理 - 工厂任务单系统';

// 获取数据用于表单和显示
$users = getAllUsers();
$departments = DEPARTMENTS;

// 获取所有用户数据
$allUsers = getAllUsers();
?>
<?php include 'header.php'; ?>

    <div class="flex-grow container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">用户管理</h2>
            
            <!-- 添加用户表单 -->
            <div class="mb-12">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">添加新用户</h3>
                <form id="add-user-form" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">用户名 *</label>
                            <input type="text" id="username" name="username" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">密码 *</label>
                            <input type="password" id="password" name="password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700">姓名 *</label>
                            <input type="text" id="full_name" name="full_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700">部门</label>
                            <select id="department" name="department"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php foreach (DEPARTMENTS as $deptKey => $deptName): ?>
                                    <option value="<?= $deptKey ?>"><?= $deptName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">角色</label>
                            <select id="role" name="role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <!-- 角色将根据部门动态填充 -->
                            </select>
                        </div>
                    </div>
                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            添加用户
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- 用户列表 -->
            <div>
                <h3 class="text-xl font-semibold mb-4 text-gray-700">用户列表</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户名</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">姓名</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">部门</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">主要负责人</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($allUsers as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= ROLES[$user['role']] ?? $user['role'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= DEPARTMENTS[$user['department']] ?? $user['department'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $user['is_main_manager'] ? '是' : '否' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>
    
    <!-- 引入管理页面专用的JavaScript文件 -->
    <script src="js/admin.js"></script>
    <script>
    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化角色下拉列表
        updateRoleOptions();
        
        // 监听部门选择变化
        document.getElementById('department').addEventListener('change', updateRoleOptions);
        
        // 绑定表单提交事件
        document.getElementById('add-user-form').addEventListener('submit', function(e) {
            e.preventDefault();
            addUser();
        });
    });
    
    // 添加用户
    function addUser() {
        const formData = {
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            full_name: document.getElementById('full_name').value,
            role: document.getElementById('role').value,
            department: document.getElementById('department').value,
            action: 'add_user'
        };
        
        // 发送AJAX请求添加用户
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('用户添加成功');
                    location.reload();
                } else {
                    alert('用户添加失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('添加用户请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
                alert('请求失败，请稍后重试');
            }
        });
    }
    </script>
</body>
</html>