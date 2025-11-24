<?php
// 主页
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
$title = '主页 - 工厂任务单系统';

// 获取数据
$recentTasks = getRecentTasks();
$completedTasks = getCompletedTasks();
$users = getAllUsers();
$processChains = getAllProcessChains();

// 如果是工人或负责人，获取其工序的任务
if (canUpdateTaskProgress($_SESSION['role'] ?? '')) {
    $processTasks = getTasksForCurrentUserProcess($_SESSION['user_id']);
}
?>
<?php include 'header.php'; ?>

    <div class="flex-grow container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 左侧：任务创建表单 -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">创建新任务</h2>
                    <form id="taskForm">
                        <div class="mb-4">
                            <label for="task_title" class="block text-sm font-medium text-gray-700">任务标题</label>
                            <input type="text" id="task_title" name="task_title"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">任务描述</label>
                            <textarea id="description" name="description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="priority" class="block text-sm font-medium text-gray-700">优先级</label>
                            <select id="priority" name="priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php foreach (PRIORITY as $key => $value): ?>
                                    <option value="<?= $key ?>"><?= $value ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="assignee_id" class="block text-sm font-medium text-gray-700">指派给工序链</label>
                            <select id="assignee_id" name="assignee_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">未指派</option>
                                <?php foreach ($processChains as $chain): ?>
                                    <?php if ($chain['enabled']): ?>
                                        <option value="<?= $chain['id'] ?>"><?= htmlspecialchars($chain['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit"
                            class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            创建任务
                        </button>
                    </form>
                </div>
                
                <?php if(canUpdateTaskProgress($_SESSION['role'] ?? '')): 
                    $currentUser = getUserById($_SESSION['user_id']);
                    $processName = $currentUser['department'];
                    ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-blue-800 font-medium">
                                当前工序：<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">
                                    <?= DEPARTMENTS[$processName] ?? $processName ?>
                                </span>
                            </span>
                        </div>
                        <div class="mt-2 text-sm text-blue-700">
                            操作员：<?= htmlspecialchars($currentUser['full_name']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 右侧：最近任务 -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">最近任务</h2>
                        <?php if (hasAdminAccess($_SESSION['role'] ?? '')): ?>
                            <a href="admin/index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                管理面板
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // 根据用户角色决定显示哪些任务
                    $displayTasks = $recentTasks;
                    if (canUpdateTaskProgress($_SESSION['role'] ?? '')) {
                        $displayTasks = $processTasks ?? [];
                    }
                    ?>
                    
                    <?php if (empty($displayTasks)): ?>
                        <p class="text-gray-500 text-center py-8">暂无任务</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($displayTasks as $task): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($task['title']) ?></h3>
                                            <?php if ($task['description']): ?>
                                                <p class="text-gray-600 mt-1"><?= htmlspecialchars($task['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= 
                                            $task['priority'] === 'critical' ? 'red' : 
                                            ($task['priority'] === 'urgent' ? 'orange' : 
                                            ($task['priority'] === 'high' ? 'yellow' : 
                                            ($task['priority'] === 'medium' ? 'green' : 'blue'))) 
                                        ?>-100 text-<?= 
                                            $task['priority'] === 'critical' ? 'red' : 
                                            ($task['priority'] === 'urgent' ? 'orange' : 
                                            ($task['priority'] === 'high' ? 'yellow' : 
                                            ($task['priority'] === 'medium' ? 'green' : 'blue'))) 
                                        ?>-800">
                                            <?= PRIORITY[$task['priority']] ?? $task['priority'] ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <?php if ($task['process_chain_type']): ?>
                                            <?php 
                                            $chain = getProcessChainById($task['process_chain_type']);
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                工序链: <?= $chain ? htmlspecialchars($chain['name']) : '未知工序链' ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?= PROCESS_STATUS[$task['status']] ?? $task['status'] ?>
                                        </span>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?= date('Y-m-d H:i', strtotime($task['created_at'])) ?>
                                        </span>
                                    </div>
                                    
                                    <!-- 工序完成情况显示 -->
                                    <?php if ($task['process_chain_type']): ?>
                                        <?php 
                                        $chain = getProcessChainById($task['process_chain_type']);
                                        if ($chain): 
                                            $steps = getProcessChainSteps($chain['id']);
                                            $stepStatuses = getProcessChainStepStatuses($task['id'], $chain['id']);
                                            ?>
                                            <div class="mt-2 text-xs">
                                                <?php foreach ($steps as $step): 
                                                    $stepName = PROCESSING_STEPS[$step['step_key']] ?? $step['step_key'];
                                                    $status = $stepStatuses[$step['step_key']] ?? 'pending';
                                                    
                                                    // 根据状态设置颜色
                                                    $statusClass = '';
                                                    $statusText = '';
                                                    switch ($status) {
                                                        case 'completed':
                                                            $statusClass = 'bg-green-100 text-green-800';
                                                            $statusText = '已完成';
                                                            break;
                                                        case 'in-progress':
                                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                                            $statusText = '进行中';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-gray-100 text-gray-800';
                                                            $statusText = '待处理';
                                                    }
                                                    ?>
                                                    <span class="inline-block mr-2 px-1 py-0.5 rounded <?= $statusClass ?>">
                                                        <?= htmlspecialchars($stepName) ?>: <span class="font-medium"><?= $statusText ?></span>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <div class="mt-4 flex space-x-2">
                                        <?php if ($task['status'] !== 'completed'): ?>
                                            <?php if (canUpdateTaskProgress($_SESSION['role'] ?? '')): ?>
                                                <button data-task-id="<?= $task['id'] ?>" data-status="in-progress"
                                                    class="update-task-status inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                    开始处理
                                                </button>
                                                <button data-task-id="<?= $task['id'] ?>" data-status="completed"
                                                    class="update-task-status inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                    完成
                                                </button>
                                            <?php elseif (canManageTasks($_SESSION['role'] ?? '')): ?>
                                                <button data-task-id="<?= $task['id'] ?>" data-status="cancelled"
                                                    class="update-task-status inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                    中止
                                                </button>
                                                <button data-task-id="<?= $task['id'] ?>" data-status="void"
                                                    class="update-task-status inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                                    作废
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 已完成任务列表 -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-8">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">已完成任务</h2>
                    
                    <?php if (empty($completedTasks)): ?>
                        <p class="text-gray-500 text-center py-8">暂无已完成任务</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($completedTasks as $task): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($task['title']) ?></h3>
                                            <?php if ($task['description']): ?>
                                                <p class="text-gray-600 mt-1"><?= htmlspecialchars($task['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?= PROCESS_STATUS[$task['status']] ?? $task['status'] ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <?php if ($task['process_chain_type']): ?>
                                            <?php 
                                            $chain = getProcessChainById($task['process_chain_type']);
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                工序链: <?= $chain ? htmlspecialchars($chain['name']) : '未知工序链' ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?= date('Y-m-d H:i', strtotime($task['created_at'])) ?>
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            完成时间: <?= date('Y-m-d H:i', strtotime($task['updated_at'])) ?>
                                        </span>
                                    </div>
                                    
                                    <!-- 工序完成情况显示 -->
                                    <?php if ($task['process_chain_type']): ?>
                                        <?php 
                                        $chain = getProcessChainById($task['process_chain_type']);
                                        if ($chain): 
                                            $steps = getProcessChainSteps($chain['id']);
                                            $stepStatuses = getProcessChainStepStatuses($task['id'], $chain['id']);
                                            ?>
                                            <div class="mt-2 text-xs">
                                                <?php foreach ($steps as $step): 
                                                    $stepName = PROCESSING_STEPS[$step['step_key']] ?? $step['step_key'];
                                                    $status = $stepStatuses[$step['step_key']] ?? 'pending';
                                                    
                                                    // 根据状态设置颜色
                                                    $statusClass = '';
                                                    $statusText = '';
                                                    switch ($status) {
                                                        case 'completed':
                                                            $statusClass = 'bg-green-100 text-green-800';
                                                            $statusText = '已完成';
                                                            break;
                                                        case 'in-progress':
                                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                                            $statusText = '进行中';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-gray-100 text-gray-800';
                                                            $statusText = '待处理';
                                                    }
                                                    ?>
                                                    <span class="inline-block mr-2 px-1 py-0.5 rounded <?= $statusClass ?>">
                                                        <?= htmlspecialchars($stepName) ?>: <span class="font-medium"><?= $statusText ?></span>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>