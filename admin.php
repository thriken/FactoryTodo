<?php
// 管理页面
session_start();
require_once 'config/database.php';
require_once 'config/const.php';
require_once 'includes/functions.php';
require_once 'config/config.php';

// 检查用户是否已登录
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    header('Location: login.php');
    exit;
}

// 设置页面标题
$title = '管理面板 - 工厂任务单系统';

// 获取数据用于表单和显示
$users = getAllUsers();
$departments = DEPARTMENTS;
$processChains = getAllProcessChains();

// 获取数据显示
$allUsers = getAllUsers();
$allTasks = getAllTasks();
$allProcessChains = getAllProcessChains();
?>

<?php include 'header.php'; ?>

<div class="flex-grow container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">管理面板</h2>
        
        <!-- Tab导航 -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-8">
                <button onclick="showTab('users')" id="tab-users" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    用户管理
                </button>
                <button onclick="showTab('process-chains')" id="tab-process-chains" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    工序链管理
                </button>
            </nav>
        </div>
        
        <!-- 用户管理 Tab -->
        <div id="tab-content-users" class="tab-content">
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
        
        <!-- 工序链管理 Tab -->
        <div id="tab-content-process-chains" class="tab-content hidden">
            <!-- 添加工序链表单 -->
            <div class="mb-12">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">添加新工序链</h3>
                <form id="add-process-chain-form" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="chain_name" class="block text-sm font-medium text-gray-700">工序链名称 *</label>
                            <input type="text" id="chain_name" name="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="chain_enabled" name="enabled" value="1" checked
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <label for="chain_enabled" class="ml-2 block text-sm text-gray-900">启用</label>
                        </div>
                    </div>
                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            添加工序链
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- 工序链列表 -->
            <div class="mb-12">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">工序链列表</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">工序链名称</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">启用状态</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($allProcessChains as $chain): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($chain['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $chain['enabled'] ? '启用' : '禁用' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $chain['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 为工序链添加步骤 -->
            <div>
                <h3 class="text-xl font-semibold mb-4 text-gray-700">为工序链添加步骤</h3>
                <form id="add-step-to-chain-form" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="chain_id" class="block text-sm font-medium text-gray-700">选择工序链 *</label>
                            <select id="chain_id" name="chain_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">请选择工序链</option>
                                <?php foreach ($allProcessChains as $chain): ?>
                                    <option value="<?= $chain['id'] ?>"><?= htmlspecialchars($chain['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="step_key" class="block text-sm font-medium text-gray-700">选择工序步骤 *</label>
                            <select id="step_key" name="step_key" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">请选择工序步骤</option>
                                <?php foreach (PROCESSING_STEPS as $stepKey => $stepName): ?>
                                    <option value="<?= $stepKey ?>"><?= $stepName ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="step_order" class="block text-sm font-medium text-gray-700">顺序</label>
                            <input type="number" id="step_order" name="order" value="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            添加步骤到工序链
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// 角色定义（与PHP中的常量对应）
const ROLES_ADMIN = {
    'super-admin': '超级管理员',
    'boss': '高管',
    'customer-service': '客服'
};

const ROLES_PROCESS = {
    'process-manager': '负责人',
    'observer': '员工'
};

const DEPARTMENTS = {
    'cutting': '切割',
    'tempering': '钢化',
    'laminating': '夹层',
    'insulating': '中空',
    'warehouse': '仓库',
    'packing': '包装',
    'shipping': '发货',
    'qc': '质检',
    'admin': '管理'
};

// 根据部门获取角色列表
function getRolesByDepartment(department) {
    if (department === 'admin') {
        return ROLES_ADMIN;
    } else {
        return ROLES_PROCESS;
    }
}

// 更新角色下拉列表
function updateRoleOptions() {
    const departmentSelect = document.getElementById('department');
    const roleSelect = document.getElementById('role');
    const selectedDepartment = departmentSelect.value;
    
    // 清空现有选项
    roleSelect.innerHTML = '';
    
    // 获取该部门对应的角色
    const roles = getRolesByDepartment(selectedDepartment);
    
    // 添加角色选项
    for (const [roleKey, roleName] of Object.entries(roles)) {
        const option = document.createElement('option');
        option.value = roleKey;
        option.textContent = roleName;
        roleSelect.appendChild(option);
    }
}

// Tab切换功能
function showTab(tabName) {
    // 隐藏所有tab内容
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // 移除所有tab按钮的激活状态
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // 显示选中的tab内容
    document.getElementById('tab-content-' + tabName).classList.remove('hidden');
    
    // 激活选中的tab按钮
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tabName).classList.add('border-blue-500', 'text-blue-600');
}

// 默认显示第一个tab
document.addEventListener('DOMContentLoaded', function() {
    showTab('users');
    
    // 初始化角色下拉列表
    updateRoleOptions();
    
    // 监听部门选择变化
    document.getElementById('department').addEventListener('change', updateRoleOptions);
    
    // 注意：表单提交事件绑定已移至app.js中统一处理
    // 这里不再重复绑定，避免重复提交问题
});
</script>
</body>
</html>