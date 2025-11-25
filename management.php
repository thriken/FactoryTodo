<?php
// 管理页面 - 管理人员使用
session_start();
require_once 'config/database.php';
require_once 'config/const.php';
require_once 'includes/functions.php';
require_once 'includes/task_renderer.php'; // 引入任务渲染器
require_once 'config/config.php';

// 检查用户是否已登录
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    header('Location: login.php');
    exit;
}

// 检查用户权限
if (!canManageTasks($_SESSION['role'] ?? '')) {
    header('Location: index.php');
    exit;
}

// 设置页面标题
$title = '管理任务 - 工厂任务单系统';

// 获取数据
$processChains = getAllProcessChains();
$pendingTasks = getAllPendingTasksSorted();
$inProgressTasks = getAllInProgressTasksSorted();
$completedTasks = getAllCompletedTasksSorted();

// 获取任务总数用于标签页显示
$pendingCount = count($pendingTasks);
$inProgressCount = count($inProgressTasks);
$completedCount = count($completedTasks);
?>
<?php include 'header.php'; ?>

    <div class="flex-grow container mx-auto px-2 py-4">
        <?php if (hasAdminAccess($_SESSION['role'] ?? '')): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between space-y-2 sm:space-y-0">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="text-yellow-800 font-medium text-sm sm:text-base">管理员功能</span>
                    </div>
                    <a href="admin/reset_tasks.php" class="inline-flex items-center px-3 py-1 border border-transparent text-xs sm:text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        重置任务数据
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 标签页导航 -->
        <div class="mb-4 border-b border-gray-200">
            <nav class="flex space-x-2 overflow-x-auto pb-2" aria-label="Tabs">
                <button data-tab="create" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg active">
                    创建任务
                </button>
                <button data-tab="pending" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg">
                    待处理 (<?= $pendingCount ?>)
                </button>
                <button data-tab="in-progress" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg">
                    进行中 (<?= $inProgressCount ?>)
                </button>
                <button data-tab="completed" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg">
                    已完成/中止/作废 (<?= $completedCount ?>)
                </button>
            </nav>
        </div>
        
        <!-- 创建任务表单 -->
        <div id="create-tab" class="tab-content">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h2 class="text-xl font-bold text-gray-800 mb-4">创建新任务</h2>
                
                <form id="taskForm">
                    <div class="mb-3">
                        <label for="task_title" class="block text-sm font-medium text-gray-700">任务标题 *</label>
                        <input type="text" id="task_title" name="task_title" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="block text-sm font-medium text-gray-700">任务描述</label>
                        <textarea id="description" name="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700">优先级</label>
                            <select id="priority" name="priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <?php foreach (PRIORITY as $key => $value): ?>
                                    <option value="<?= $key ?>"><?= $value ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="assignee_id" class="block text-sm font-medium text-gray-700">指派给工序链</label>
                            <select id="assignee_id" name="assignee_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">未指派</option>
                                <?php foreach ($processChains as $chain): ?>
                                    <?php if ($chain['enabled']): ?>
                                        <option value="<?= $chain['id'] ?>"><?= htmlspecialchars($chain['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        创建任务
                    </button>
                </form>
            </div>
        </div>
        
        <!-- 待处理任务 -->
        <div id="pending-tab" class="tab-content hidden">
            <?php renderPendingTasks($pendingTasks, ''); ?>
        </div>
        
        <!-- 进行中任务 -->
        <div id="in-progress-tab" class="tab-content hidden">
            <?php renderInProgressTasks($inProgressTasks, ''); ?>
        </div>
        
        <!-- 已完成/中止/作废任务 -->
        <div id="completed-tab" class="tab-content hidden">
            <?php 
            // 合并已完成、中止和作废的任务用于显示
            $allCompletedTasks = array_merge($completedTasks, []);
            renderCompletedTasks($allCompletedTasks); 
            ?>
        </div>
    </div>

    <script>
        // 标签页切换功能
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // 移除所有按钮的激活状态
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    // 隐藏所有内容
                    tabContents.forEach(content => content.classList.add('hidden'));
                    
                    // 激活当前按钮
                    button.classList.add('active');
                    
                    // 显示对应的内容
                    const tabId = button.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.remove('hidden');
                    
                    // 移动端优化：点击标签时滚动到顶部
                    if (window.innerWidth <= 640) {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });
            
            // 任务表单提交处理
            const taskForm = document.getElementById('taskForm');
            if (taskForm) {
                taskForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = {
                        title: document.getElementById('task_title').value,
                        description: document.getElementById('description').value,
                        priority: document.getElementById('priority').value,
                        process_chain_id: document.getElementById('assignee_id').value || null,
                        action: 'add_task'
                    };
                    
                    // 发送AJAX请求添加任务
                    $.ajax({
                        url: 'api.php',
                        method: 'POST',
                        data: formData,
                        success: function(response) {
                            if (response.success) {
                                alert('任务创建成功');
                                // 清空表单
                                taskForm.reset();
                                // 重新加载页面以显示新任务
                                location.reload();
                            } else {
                                alert('任务创建失败: ' + response.error);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('创建任务请求失败:', status, error);
                            console.error('响应内容:', xhr.responseText);
                            
                            // 尝试解析响应内容以获取错误信息
                            let errorMessage = '请求失败，请稍后重试';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response && response.error) {
                                    errorMessage = response.error;
                                }
                            } catch (e) {
                                // 如果无法解析JSON，检查是否包含特定错误信息
                                if (xhr.responseText.includes('Fatal error')) {
                                    // 从HTML错误信息中提取错误消息
                                    const match = xhr.responseText.match(/Uncaught Exception: (.+?) in/);
                                    if (match && match[1]) {
                                        errorMessage = match[1];
                                    } else {
                                        errorMessage = '系统错误，请稍后重试';
                                    }
                                }
                            }
                            
                            alert('任务创建失败: ' + errorMessage);
                        }
                    });
                });
            }
        });
    </script>

<?php include 'footer.php'; ?>