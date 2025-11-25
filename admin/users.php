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

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_user') {
        $userId = $_POST['user_id'] ?? '';
        if ($userId) {
            if (deleteUser($userId)) {
                $_SESSION['message'] = '用户删除成功';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = '用户删除失败';
                $_SESSION['message_type'] = 'error';
            }
        }
        header('Location: users.php');
        exit;
    } elseif ($action === 'update_user') {
        $userId = $_POST['user_id'] ?? '';
        $username = $_POST['username'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? '';
        $department = $_POST['department'] ?? '';
        $isMainManager = isset($_POST['is_main_manager']) ? 1 : 0;
        
        if ($userId && $username && $fullName && $role && $department) {
            if (updateUser($userId, $username, $fullName, $role, $department, $isMainManager)) {
                $_SESSION['message'] = '用户更新成功';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = '用户更新失败';
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = '请填写所有必填字段';
            $_SESSION['message_type'] = 'error';
        }
        header('Location: users.php');
        exit;
    }
}

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
            
            <!-- 显示消息 -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-4 p-4 rounded <?= $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                    <?php unset($_SESSION['message']); ?>
                    <?php unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>
            
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
                        <div class="flex items-center">
                            <input type="checkbox" id="is_main_manager" name="is_main_manager" value="1"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_main_manager" class="ml-2 block text-sm text-gray-700">
                                主要负责人
                            </label>
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
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['full_name']) ?>', '<?= $user['role'] ?>', '<?= $user['department'] ?>', <?= $user['is_main_manager'] ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-2">
                                            编辑
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除用户 <?= htmlspecialchars($user['username']) ?> 吗？')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                删除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 编辑用户模态框 -->
    <div id="edit-user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">编辑用户</h3>
                <form id="edit-user-form" method="POST">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <div class="mb-4">
                        <label for="edit-username" class="block text-sm font-medium text-gray-700">用户名 *</label>
                        <input type="text" id="edit-username" name="username" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="edit-full_name" class="block text-sm font-medium text-gray-700">姓名 *</label>
                        <input type="text" id="edit-full_name" name="full_name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="edit-department" class="block text-sm font-medium text-gray-700">部门</label>
                        <select id="edit-department" name="department"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <?php foreach (DEPARTMENTS as $deptKey => $deptName): ?>
                                <option value="<?= $deptKey ?>"><?= $deptName ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit-role" class="block text-sm font-medium text-gray-700">角色</label>
                        <select id="edit-role" name="role"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <!-- 角色将根据部门动态填充 -->
                        </select>
                    </div>
                    <div class="mb-4 flex items-center">
                        <input type="checkbox" id="edit-is_main_manager" name="is_main_manager" value="1"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="edit-is_main_manager" class="ml-2 block text-sm text-gray-700">
                            主要负责人
                        </label>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeEditModal()"
                            class="mr-2 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            取消
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            保存
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>
    
    <!-- 引入管理页面专用的JavaScript文件 -->
    <script src="js/admin.js"></script>
    
    <script>
        // 编辑用户函数
        function editUser(userId, username, fullName, role, department, isMainManager) {
            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-full_name').value = fullName;
            document.getElementById('edit-department').value = department;
            document.getElementById('edit-is_main_manager').checked = isMainManager == 1;
            
            // 触发部门选择改变事件以更新角色选项
            const departmentSelect = document.getElementById('edit-department');
            departmentSelect.dispatchEvent(new Event('change'));
            
            // 设置角色选项
            setTimeout(() => {
                document.getElementById('edit-role').value = role;
            }, 100);
            
            // 显示模态框
            document.getElementById('edit-user-modal').classList.remove('hidden');
        }
        
        // 关闭编辑模态框
        function closeEditModal() {
            document.getElementById('edit-user-modal').classList.add('hidden');
        }
        
        // 点击模态框外部关闭
        document.getElementById('edit-user-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>