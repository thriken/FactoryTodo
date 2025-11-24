<?php
// 工序链管理页面
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
$title = '工序链管理 - 工厂任务单系统';

// 获取数据用于表单和显示
$processChains = getAllProcessChains();

// 获取所有工序链数据
$allProcessChains = getAllProcessChains();
?>
<?php include 'header.php'; ?>

    <div class="flex-grow container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">工序链管理</h2>
                        
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
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button onclick="loadChainSteps(<?= $chain['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-2">管理步骤</button>
                                        <button onclick="editProcessChain(<?= $chain['id'] ?>, '<?= htmlspecialchars($chain['name']) ?>', <?= $chain['enabled'] ?>)" class="text-green-600 hover:text-green-900 mr-2">编辑</button>
                                        <button onclick="deleteProcessChain(<?= $chain['id'] ?>)" class="text-red-600 hover:text-red-900">删除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 编辑工序链表单 (默认隐藏) -->
            <div id="edit-process-chain-form" class="mb-12 hidden">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">编辑工序链</h3>
                <form id="update-process-chain-form" class="space-y-4">
                    <input type="hidden" id="edit_chain_id" name="id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_chain_name" class="block text-sm font-medium text-gray-700">工序链名称 *</label>
                            <input type="text" id="edit_chain_name" name="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="edit_chain_enabled" name="enabled" value="1" checked
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <label for="edit_chain_enabled" class="ml-2 block text-sm text-gray-900">启用</label>
                        </div>
                    </div>
                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            更新工序链
                        </button>
                        <button type="button" onclick="cancelEditProcessChain()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ml-2">
                            取消
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- 工序链步骤管理表格 -->
            <div id="chain-steps-management" class="mb-12 hidden">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">工序链步骤管理</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">工序</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">顺序号</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody id="chain-steps-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- 步骤将通过JavaScript动态添加 -->
                        </tbody>
                    </table>
                </div>
                
                <!-- 添加/编辑步骤表单 -->
                <div class="mt-6 p-4 bg-white rounded-lg shadow">
                    <h4 class="text-lg font-medium text-gray-800 mb-3" id="step-form-title">添加新步骤</h4>
                    <form id="step-management-form" class="space-y-4">
                        <input type="hidden" id="step_id" name="step_id">
                        <input type="hidden" id="chain_id_input" name="chain_id">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="step_key_select" class="block text-sm font-medium text-gray-700">选择工序步骤 *</label>
                                <select id="step_key_select" name="step_key" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">请选择工序步骤</option>
                                    <?php foreach (PROCESSING_STEPS as $stepKey => $stepName): ?>
                                        <option value="<?= $stepKey ?>"><?= $stepName ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="step_order_input" class="block text-sm font-medium text-gray-700">顺序</label>
                                <input type="number" id="step_order_input" name="order" value="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                添加步骤
                            </button>
                            <button type="button" id="cancel-step-edit"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ml-2">
                                取消
                            </button>
                        </div>
                    </form>
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
        // 绑定表单提交事件
        document.getElementById('add-process-chain-form').addEventListener('submit', function(e) {
            e.preventDefault();
            addProcessChain();
        });
        
        document.getElementById('update-process-chain-form').addEventListener('submit', function(e) {
            e.preventDefault();
            updateProcessChain();
        });
    });
    
    // 添加工序链
    function addProcessChain() {
        const formData = {
            name: document.getElementById('chain_name').value,
            enabled: document.getElementById('chain_enabled').checked ? 1 : 0,
            action: 'add_process_chain'
        };
        
        // 发送AJAX请求添加工序链
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('工序链添加成功');
                    location.reload();
                } else {
                    alert('工序链添加失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('添加工序链请求失败:', status, error);
                alert('请求失败，请稍后重试');
            }
        });
    }
    </script>
</body>
</html>