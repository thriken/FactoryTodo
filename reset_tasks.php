<?php
// 重置任务脚本 - 将除测试中止和取消外的所有任务及其子工序初始化
session_start();
require_once 'config/database.php';
require_once 'config/const.php';
require_once 'includes/functions.php';
require_once 'config/config.php';

// 检查用户是否已登录并且是管理员
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in'] || !canManageTasks($_SESSION['role'] ?? '')) {
    header('Location: login.php');
    exit;
}

// 设置页面标题
$title = '重置任务 - 工厂任务单系统';

// 处理重置请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_tasks'])) {
    try {
        // 开始事务
        $db->getPdo()->beginTransaction();
        
        // 获取所有任务（排除状态为 cancelled 和 void 的任务）
        $stmt = $db->debugQuery("SELECT id FROM todos WHERE status NOT IN ('cancelled', 'void')");
        $tasks = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        $resetCount = 0;
        foreach ($tasks as $task) {
            $taskId = $task['id'];
            
            // 重置任务主状态为待处理
            $db->debugQuery("UPDATE todos SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$taskId]);
            
            // 重置该任务的所有子工序状态为待处理
            $db->debugQuery("UPDATE todo_steps SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE todo_id = ?", [$taskId]);
            
            $resetCount++;
        }
        
        // 提交事务
        $db->getPdo()->commit();
        
        $successMessage = "成功重置 {$resetCount} 个任务及其所有子工序！";
    } catch (Exception $e) {
        // 回滚事务
        $db->getPdo()->rollBack();
        $errorMessage = "重置任务失败: " . $e->getMessage();
        logError($errorMessage);
    }
}

// 获取当前任务状态统计
$stmt = $db->debugQuery("SELECT status, COUNT(*) as count FROM todos GROUP BY status");
$taskStats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$stmt = $db->debugQuery("SELECT ts.status, COUNT(*) as count FROM todo_steps ts GROUP BY ts.status");
$stepStats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<?php include 'header.php'; ?>

<div class="flex-grow container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">重置任务数据</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= $successMessage ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>
        
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-4">当前任务状态统计</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($taskStats as $stat): ?>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-lg font-medium"><?= PROCESS_STATUS[$stat['status']] ?? $stat['status'] ?></div>
                        <div class="text-2xl font-bold text-blue-600"><?= $stat['count'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-4">当前工序步骤状态统计</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($stepStats as $stat): ?>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-lg font-medium"><?= PROCESS_STATUS[$stat['status']] ?? $stat['status'] ?></div>
                        <div class="text-2xl font-bold text-blue-600"><?= $stat['count'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>注意：</strong>此操作将重置除状态为"中止"和"作废"外的所有任务及其子工序状态为"待处理"。
                        这些测试任务将被保留用于测试中止和取消功能。
                    </p>
                </div>
            </div>
        </div>
        
        <form method="POST" onsubmit="return confirm('确定要重置所有任务数据吗？此操作不可撤销。')">
            <button type="submit" name="reset_tasks" 
                    class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                重置所有任务数据
            </button>
        </form>
        
        <div class="mt-6">
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                返回首页
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>