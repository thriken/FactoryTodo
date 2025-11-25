<?php
// 生产页面 - 工人和负责人使用
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
if (!canUpdateTaskProgress($_SESSION['role'] ?? '')) {
    header('Location: index.php');
    exit;
}

// 设置页面标题
$title = '生产任务 - 工厂任务单系统';

// 获取当前用户信息
$currentUser = getUserById($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';
$userDepartment = $currentUser['department'] ?? '';

// 获取当前工序的任务
// 按优先级和创建时间排序
$pendingTasks = getPendingTasksForDepartmentSorted($userDepartment);
$inProgressTasks = getInProgressTasksForDepartmentSorted($userDepartment);
$completedTasks = getCompletedTasksForDepartmentSorted($userDepartment);
$finishedTasks = getFinishedTasksForDepartmentSorted($userDepartment);

// 获取任务总数用于标签页显示
$pendingCount = count($pendingTasks);
$inProgressCount = count($inProgressTasks);
$completedCount = count($completedTasks);
$finishedCount = count($finishedTasks);
?>
<?php include 'header.php'; ?>

    <div class="flex-grow container mx-auto px-2 py-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center">
                <div class="flex items-center mb-2 sm:mb-0">
                    <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-blue-800 text-base font-medium">当前工序：
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-base font-medium"><?= DEPARTMENTS[$userDepartment] ?? $userDepartment ?></span>
                    </span>
                </div>
                <span class="text-blue-700 text-base ml-0 sm:ml-4 mt-1 sm:mt-0">操作员：<?= htmlspecialchars($currentUser['full_name']) ?></span>
            </div>
        </div>
        
        <!-- 标签页导航 -->
        <div class="mb-4 border-b border-gray-200">
            <nav class="flex space-x-2 overflow-x-auto pb-2" aria-label="Tabs">
                <button data-tab="pending" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg active">
                    待处理 (<?= $pendingCount ?>)
                </button>
                <button data-tab="in-progress" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg">
                    进行中 (<?= $inProgressCount ?>)
                </button>
                <button data-tab="completed" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg">
                    已完成 (<?= $completedCount ?>)
                </button>
                <button data-tab="finished" 
                        class="tab-button whitespace-nowrap py-2 px-3 text-sm font-medium rounded-t-lg">
                    已完结 (<?= $finishedCount ?>)
                </button>
            </nav>
        </div>
        
        <!-- 待处理任务【工序级】 对于首工序，优先显示本tab -->
        <div id="pending-tab" class="tab-content">
            <?php renderPendingTasks($pendingTasks, $userDepartment); ?>
        </div>
        
        <!-- 进行中任务【工序级】 对于后工序，优先显示本tab -->
        <div id="in-progress-tab" class="tab-content hidden">
            <?php renderInProgressTasks($inProgressTasks, $userDepartment); ?>
        </div>
        
        <!-- 已完成任务【工序级】 -->
        <div id="completed-tab" class="tab-content hidden">
            <?php renderCompletedTasks($completedTasks); ?>
        </div>
        
        <!-- 已完结任务【任务级】 -->
        <div id="finished-tab" class="tab-content hidden">
            <?php renderFinishedTasks($finishedTasks); ?>
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
                });
            });
            
            // 移动端优化：点击标签时滚动到顶部
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        });
    </script>

<?php include 'footer.php'; ?>