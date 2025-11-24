<?php
// API端点文件
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'includes/functions.php';

// 获取请求方法和参数
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 根据请求方法和动作处理请求
switch ($method) {
    case 'GET':
        handleGetRequest($action);
        break;
    case 'POST':
        handlePostRequest($action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => '不支持的请求方法']);
        break;
}

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
            
        case 'processing_steps':
            $steps = getAllProcessingSteps();
            echo json_encode(['success' => true, 'data' => $steps]);
            break;
            
        case 'process_chains':
            $chains = getAllProcessChains();
            echo json_encode(['success' => true, 'data' => $chains]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作']);
            break;
    }
}

function handlePostRequest($action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'add_user':
            if (empty($input['email']) || empty($input['full_name'])) {
                http_response_code(400);
                echo json_encode(['error' => '邮箱和姓名是必填字段']);
                return;
            }
            
            $userId = addUser(
                $input['email'],
                $input['full_name'],
                $input['role'] ?? 'observer',
                $input['department'] ?? 'cutting',
                $input['is_main_manager'] ?? false
            );
            
            if ($userId) {
                echo json_encode(['success' => true, 'message' => '用户添加成功', 'user_id' => $userId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '用户添加失败']);
            }
            break;
            
        case 'add_task':
            if (empty($input['title'])) {
                http_response_code(400);
                echo json_encode(['error' => '任务标题是必填字段']);
                return;
            }
            
            $taskId = addTask(
                $input['title'],
                $input['description'] ?? '',
                $input['created_by'] ?? 1,
                $input['assigned_to'] ?? null,
                $input['priority'] ?? 'medium',
                $input['process_chain_type'] ?? 'single'
            );
            
            if ($taskId) {
                echo json_encode(['success' => true, 'message' => '任务添加成功', 'task_id' => $taskId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '任务添加失败']);
            }
            break;
            
        case 'update_task_status':
            if (empty($input['task_id']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => '任务ID和状态是必填字段']);
                return;
            }
            
            $result = updateTaskStatus($input['task_id'], $input['status']);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => '任务状态更新成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '任务状态更新失败']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => '无效的操作']);
            break;
    }
}
?>