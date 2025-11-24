<?php
// API入口文件

require_once 'config/database.php';
require_once 'config/const.php';
require_once 'includes/functions.php';
require_once 'config/config.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 启用CORS（如果需要跨域访问）
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 处理OPTIONS请求（预检请求）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 从GET或POST参数中获取action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 记录请求日志
if (DEBUG_MODE) {
    logError("API请求: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " Action: $action");
}

// 根据请求方法分发处理
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetRequest($action);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePostRequest($action);
} else {
    http_response_code(405);
    echo json_encode(['error' => '不支持的请求方法']);
    if (DEBUG_MODE) {
        logError("不支持的请求方法: " . $_SERVER['REQUEST_METHOD']);
    }
}

// 处理GET请求
function handleGetRequest($action) {
    switch ($action) {
        case 'tasks':
            $tasks = getAllTasks();
            echo json_encode(['success' => true, 'data' => $tasks]);
            break;
            
        case 'users':
            $users = getAllUsers();
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'process_chains':
            $chains = getAllProcessChains();
            echo json_encode(['success' => true, 'data' => $chains]);
            break;
            
        case 'clear_logs':
            // 清空日志文件
            if (defined('LOG_FILE') && file_exists(LOG_FILE)) {
                file_put_contents(LOG_FILE, '');
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => '日志文件不存在']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作']);
            if (DEBUG_MODE) {
                logError("无效的GET操作: $action");
            }
            break;
    }
}

// 处理POST请求
function handlePostRequest($action) {
    switch ($action) {
        case 'add_user':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $role = $_POST['role'] ?? 'observer';
            $department = $_POST['department'] ?? 'cutting';
            // 不再处理is_main_manager参数，因为已经移除了相关UI元素
            
            // 验证必填字段
            if (empty($username) || empty($password) || empty($fullName)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '请填写必填字段']);
                if (DEBUG_MODE) {
                    logError("添加用户失败: 必填字段为空");
                }
                return;
            }
            
            // 验证角色和部门是否有效
            if (!array_key_exists($role, ROLES)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '无效的角色']);
                if (DEBUG_MODE) {
                    logError("添加用户失败: 无效的角色 $role");
                }
                return;
            }
            
            if (!array_key_exists($department, DEPARTMENTS)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '无效的部门']);
                if (DEBUG_MODE) {
                    logError("添加用户失败: 无效的部门 $department");
                }
                return;
            }
            
            // 调用添加用户函数，isMainManager参数默认为false
            $userId = addUser($username, $password, $fullName, $role, $department);
            if ($userId) {
                echo json_encode(['success' => true, 'data' => ['id' => $userId]]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '添加用户失败']);
                if (DEBUG_MODE) {
                    logError("添加用户失败: 数据库操作失败");
                }
            }
            break;
            
        case 'add_task':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $priority = $_POST['priority'] ?? 'medium';
            $assigneeId = $_POST['assignee_id'] ?? null;
            
            // 验证必填字段
            if (empty($title)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '请填写任务标题']);
                if (DEBUG_MODE) {
                    logError("添加任务失败: 任务标题为空");
                }
                return;
            }
            
            // 验证优先级是否有效
            if (!array_key_exists($priority, PRIORITY)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '无效的优先级']);
                if (DEBUG_MODE) {
                    logError("添加任务失败: 无效的优先级 $priority");
                }
                return;
            }
            
            $taskId = addTask($title, $description, $priority, $assigneeId);
            if ($taskId) {
                echo json_encode(['success' => true, 'data' => ['id' => $taskId]]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '添加任务失败']);
                if (DEBUG_MODE) {
                    logError("添加任务失败: 数据库操作失败");
                }
            }
            break;
            
        case 'add_process_chain':
            $name = $_POST['name'] ?? '';
            $enabled = isset($_POST['enabled']) ? true : false;
            
            // 验证必填字段
            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '请填写工序链名称']);
                if (DEBUG_MODE) {
                    logError("添加工序链失败: 工序链名称为空");
                }
                return;
            }
            
            $chainId = addProcessChain($name, $enabled);
            if ($chainId) {
                echo json_encode(['success' => true, 'data' => ['id' => $chainId]]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '添加工序链失败']);
                if (DEBUG_MODE) {
                    logError("添加工序链失败: 数据库操作失败");
                }
            }
            break;
            
        case 'add_step_to_chain':
            $chainId = $_POST['chain_id'] ?? '';
            $stepKey = $_POST['step_key'] ?? '';
            $order = $_POST['order'] ?? 0;
            
            // 验证必填字段
            if (empty($chainId) || empty($stepKey)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '请选择工序链和工序步骤']);
                if (DEBUG_MODE) {
                    logError("添加工序步骤失败: 工序链或工序步骤为空");
                }
                return;
            }
            
            // 验证工序步骤是否有效
            if (!array_key_exists($stepKey, PROCESSING_STEPS)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '无效的工序步骤']);
                if (DEBUG_MODE) {
                    logError("添加工序步骤失败: 无效的工序步骤 $stepKey");
                }
                return;
            }
            
            $stepId = addStepToProcessChain($chainId, $stepKey, $order);
            if ($stepId) {
                echo json_encode(['success' => true, 'data' => ['id' => $stepId]]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '添加工序步骤失败']);
                if (DEBUG_MODE) {
                    logError("添加工序步骤失败: 数据库操作失败");
                }
            }
            break;
            
        case 'update_task_status':
            $taskId = $_POST['task_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            // 验证必填字段
            if (empty($taskId) || empty($status)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '缺少必要参数']);
                if (DEBUG_MODE) {
                    logError("更新任务状态失败: 缺少必要参数");
                }
                return;
            }
            
            // 验证状态是否有效
            if (!array_key_exists($status, PROCESS_STATUS)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '无效的状态']);
                if (DEBUG_MODE) {
                    logError("更新任务状态失败: 无效的状态 $status");
                }
                return;
            }
            
            $result = updateTaskStatus($taskId, $status);
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '更新任务状态失败']);
                if (DEBUG_MODE) {
                    logError("更新任务状态失败: 数据库操作失败");
                }
            }
            break;
            
        case 'clear_logs':
            // 清空日志文件
            if (defined('LOG_FILE') && file_exists(LOG_FILE)) {
                file_put_contents(LOG_FILE, '');
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => '日志文件不存在']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作']);
            if (DEBUG_MODE) {
                logError("无效的POST操作: $action");
            }
            break;
    }
}
?>