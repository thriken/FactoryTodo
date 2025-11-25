<?php
// 主页 - 根据用户角色重定向到相应页面
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

// 获取用户角色
$userRole = $_SESSION['role'] ?? '';

// 根据用户角色重定向到相应页面
if (hasAdminAccess($userRole)) {
    // 超管直接跳转到后台管理页面
    header('Location: admin/index.php');
    exit;
} elseif (canUpdateTaskProgress($userRole)) {
    // 工人重定向到生产页面
    header('Location: production.php');
    exit;
} elseif (canManageTasks($userRole)) {
    // 管理人员(非超管)重定向到管理页面
    header('Location: management.php');
    exit;
} else {
    // 其他角色显示简单欢迎页面
    $title = '主页 - 工厂任务单系统';
    include 'header.php';
    ?>
    <div class="flex-grow container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">欢迎使用工厂任务单系统</h1>
            <p class="text-gray-600">您的账户没有足够的权限访问系统功能。</p>
            <p class="text-gray-600 mt-2">请联系管理员获取更多权限。</p>
            <a href="logout.php" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">登出</a>
        </div>
    </div>
    <?php
    include 'footer.php';
}
?>
<?php include 'header.php'; ?>

    <div class="flex-grow container mx-auto px-4 py-8">
        <?php if(canUpdateTaskProgress($userRole)): 
            $processName = $userDepartment;
            ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-blue-800 text-lg font-medium">当前工序：
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-lg font-medium"><?= DEPARTMENTS[$processName] ?? $processName ?></span>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 待处理任务 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">待处理任务</h2>
            
            <?php if (empty($pendingTasks)): ?>
                <p class="text-gray-500 text-center py-8">暂无待处理任务</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pendingTasks as $task): ?>
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
                                    
                                    // 确定需要显示的步骤
                                    $stepsToShow = [];
                                    
                                    // 确定要显示的步骤
                                    foreach ($steps as $index => $step) {
                                        $stepKey = $step['step_key'];
                                        $status = $stepStatuses[$stepKey] ?? 'pending';
                                        
                                        // 显示已完成、进行中和待处理的步骤
                                        // 但对于待处理任务，显示所有步骤
                                        $stepsToShow[] = $step;
                                    }
                                    ?>
                                    <div class="mt-2 text-xs">
                                        <?php foreach ($stepsToShow as $step): 
                                            $stepKey = $step['step_key'];
                                            $stepName = PROCESSING_STEPS[$stepKey] ?? $stepKey;
                                            $status = $stepStatuses[$stepKey] ?? 'pending';
                                            
                                            // 获取对应的任务步骤ID
                                            global $db;
                                            $stmt = $db->debugQuery("SELECT id FROM todo_steps WHERE todo_id = ? AND step_key = ?", [$task['id'], $stepKey]);
                                            $taskStep = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                                            $taskStepId = $taskStep ? $taskStep['id'] : null;
                                            
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
                                                <?php if (canUpdateTaskProgress($userRole) && $status !== 'completed' && $taskStepId): ?>
                                                    <button data-step-id="<?= $taskStepId ?>" data-status="in-progress"
                                                        class="update-step-status ml-1 text-xs px-1 py-0.5 bg-yellow-500 text-white rounded">
                                                        开始
                                                    </button>
                                                    <button data-step-id="<?= $taskStepId ?>" data-status="completed"
                                                        class="update-step-status ml-1 text-xs px-1 py-0.5 bg-green-500 text-white rounded">
                                                        完成
                                                    </button>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="mt-4 flex space-x-2">
                                <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled' && $task['status'] !== 'void'): ?>
                                    <?php if (canUpdateTaskProgress($userRole)): ?>
                                        <button data-task-id="<?= $task['id'] ?>" data-status="in-progress"
                                            class="update-task-status inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                            开始处理
                                        </button>
                                        <button data-task-id="<?= $task['id'] ?>" data-status="completed"
                                            class="update-task-status inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            完成
                                        </button>
                                    <?php elseif (canManageTasks($userRole)): ?>
                                        <?php 
                                        // 检查是否有工序步骤已经开始处理
                                        $hasStartedSteps = false;
                                        if ($task['process_chain_type']) {
                                            global $db;
                                            $stmt = $db->debugQuery("SELECT COUNT(*) as count FROM todo_steps 
                                                                    WHERE todo_id = ? 
                                                                    AND status IN ('in-progress', 'completed')", 
                                                                   [$task['id']]);
                                            if ($stmt && ($row = $stmt->fetch()) && $row['count'] > 0) {
                                                $hasStartedSteps = true;
                                            }
                                        }
                                        ?>
                                        <?php if (!$hasStartedSteps): ?>
                                            <!-- 未开工的任务显示删除按钮 -->
                                            <button data-task-id="<?= $task['id'] ?>" data-action="delete"
                                                class="delete-task inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                                删除
                                            </button>
                                        <?php else: ?>
                                            <!-- 已开始处理的任务显示中止按钮 -->
                                            <button data-task-id="<?= $task['id'] ?>" data-status="cancelled"
                                                class="update-task-status inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                中止
                                            </button>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 已完成/中止/作废任务 -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">已完成/中止/作废任务</h2>
            
            <?php if (empty($completedTasks) && empty($cancelledVoidTasks)): ?>
                <p class="text-gray-500 text-center py-8">暂无已完成/中止/作废任务</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php 
                    // 合并已完成、中止和作废的任务
                    $allCompletedTasks = array_merge($completedTasks, $cancelledVoidTasks);
                    // 按更新时间排序
                    usort($allCompletedTasks, function($a, $b) {
                        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
                    });
                    
                    foreach ($allCompletedTasks as $task): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($task['title']) ?></h3>
                                    <?php if ($task['description']): ?>
                                        <p class="text-gray-600 mt-1"><?= htmlspecialchars($task['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= 
                                    $task['status'] === 'completed' ? 'green' : 
                                    ($task['status'] === 'cancelled' ? 'red' : 'gray') 
                                ?>-100 text-<?= 
                                    $task['status'] === 'completed' ? 'green' : 
                                    ($task['status'] === 'cancelled' ? 'red' : 'gray') 
                                ?>-800">
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
                                    <?= $task['status'] === 'completed' ? '完成时间' : '处理时间' ?>: <?= date('Y-m-d H:i', strtotime($task['updated_at'])) ?>
                                </span>
                            </div>
                            
                            <!-- 工序完成情况显示 -->
                            <?php if ($task['process_chain_type']): ?>
                                <?php 
                                $chain = getProcessChainById($task['process_chain_type']);
                                if ($chain): 
                                    $steps = getProcessChainSteps($chain['id']);
                                    $stepStatuses = getProcessChainStepStatuses($task['id'], $chain['id']);
                                    
                                    // 确定需要显示的步骤
                                    $stepsToShow = [];
                                    
                                    // 对于已完成/中止/作废任务，只显示已完成和进行中的步骤
                                    foreach ($steps as $index => $step) {
                                        $stepKey = $step['step_key'];
                                        $status = $stepStatuses[$stepKey] ?? 'pending';
                                        
                                        if ($status === 'completed' || $status === 'in-progress') {
                                            $stepsToShow[] = $step;
                                        }
                                    }
                                    ?>
                                    <div class="mt-2 text-xs">
                                        <?php foreach ($stepsToShow as $step): 
                                            $stepName = PROCESSING_STEPS[$step['step_key']] ?? $step['step_key'];
                                            $status = $stepStatuses[$step['step_key']] ?? 'pending';
                                            
                                            // 获取对应的任务步骤ID
                                            global $db;
                                            $stmt = $db->debugQuery("SELECT id FROM todo_steps WHERE todo_id = ? AND step_key = ?", [$task['id'], $step['step_key']]);
                                            $taskStep = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                                            $taskStepId = $taskStep ? $taskStep['id'] : null;
                                            
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

<?php include 'footer.php'; ?>