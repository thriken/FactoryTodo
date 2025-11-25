<?php
// 数据库操作函数文件

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/const.php';
require_once __DIR__ . '/../config/config.php';

// 初始化数据库连接
$db = new Database();
$pdo = $db->getPdo();

// 检查用户是否为超级管理员
function isSuperAdmin($userRole) {
    return $userRole === 'super-admin';
}

// 检查用户是否有超级管理权限
function hasAdminAccess($userRole) {
    // 只有超级管理员有管理权限
    return isSuperAdmin($userRole);
}

// 检查用户是否可以修改任务进度（只有员工和负责人才能修改进度）
function canUpdateTaskProgress($userRole) {
    return in_array($userRole, ['process-manager', 'observer']);
}

// 检查用户是否可以创建/中止/作废任务（管理角色可以）
function canManageTasks($userRole) {
    return in_array($userRole, [ 'boss', 'customer-service']);
}

// 获取所有任务
function getAllTasks() {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM todos ORDER BY created_at DESC");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取当前用户工序的任务（过滤掉已完成的任务）
function getTasksForCurrentUserProcess($userId) {
    global $db;
    try {
        // 获取用户信息
        $user = getUserById($userId);
        if (!$user) {
            return [];
        }
        
        $department = $user['department'];
        
        // 获取所有工序链
        $chains = getAllProcessChains();
        $filteredChainIds = [];
        
        // 筛选出包含当前部门工序的工序链
        foreach ($chains as $chain) {
            $steps = getProcessChainSteps($chain['id']);
            foreach ($steps as $step) {
                if ($step['step_key'] == $department) {
                    $filteredChainIds[] = $chain['id'];
                    break;
                }
            }
        }
        
        if (empty($filteredChainIds)) {
            return [];
        }
        
        // 构建查询，只获取当前工序的任务，并且状态不是已完成的
        $placeholders = str_repeat('?,', count($filteredChainIds) - 1) . '?';
        $sql = "SELECT * FROM todos 
                WHERE process_chain_type IN ($placeholders) 
                AND status != 'completed' 
                AND status != 'cancelled' 
                AND status != 'void'
                ORDER BY created_at DESC";
        
        $stmt = $db->debugQuery($sql, $filteredChainIds);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取当前用户工序任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取所有用户
function getAllUsers() {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM users ORDER BY created_at DESC");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取用户失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取所有工序链
function getAllProcessChains() {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM process_chains ORDER BY name ASC");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取工序链失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 添加新用户
function addUser($username, $password, $fullName, $role, $department, $isMainManager = false) {
    global $db;
    try {
        // 记录输入参数
        if (DEBUG_MODE) {
            logError("添加用户 - 输入参数: username=$username, fullName=$fullName, role=$role, department=$department, isMainManager=" . ($isMainManager ? 'true' : 'false'));
        }
        
        // 验证角色和部门是否有效
        if (!array_key_exists($role, ROLES)) {
            $error = "无效的角色: $role";
            logError("添加用户失败: $error");
            throw new Exception($error);
        }
        if (!array_key_exists($department, DEPARTMENTS)) {
            $error = "无效的部门: $department";
            logError("添加用户失败: $error");
            throw new Exception($error);
        }
        
        // 检查用户名是否已存在
        $stmt = $db->debugQuery("SELECT COUNT(*) FROM users WHERE username = ?", [$username]);
        if ($stmt && $stmt->fetchColumn() > 0) {
            $error = "用户名已存在: $username";
            logError("添加用户失败: $error");
            throw new Exception($error);
        }
        
        // 对密码进行哈希处理
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if (DEBUG_MODE) {
            logError("密码哈希处理完成");
        }
        
        $sql = "INSERT INTO users (username, password, full_name, role, department, is_main_manager) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$username, $hashedPassword, $fullName, $role, $department, $isMainManager ? 1 : 0];
        
        if (DEBUG_MODE) {
            logError("执行SQL: $sql " . json_encode($params));
        }
        
        $stmt = $db->debugQuery($sql, $params);
        if ($stmt) {
            $lastInsertId = $db->getPdo()->lastInsertId();
            if (DEBUG_MODE) {
                logError("用户添加成功，ID: $lastInsertId");
            }
            return $lastInsertId;
        }
        
        $error = "数据库操作返回false";
        logError("添加用户失败: $error");
        return false;
    } catch (PDOException $e) {
        $error = "数据库错误: " . $e->getMessage();
        logError("添加用户失败: $error");
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        $error = "异常: " . $e->getMessage();
        logError("添加用户失败: $error");
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 验证用户登录
function validateUser($username, $password) {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM users WHERE username = ?", [$username]);
        if ($stmt) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    } catch (PDOException $e) {
        logError("验证用户失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 添加新任务
function addTask($title, $description, $priority = 'medium', $processChainId = null) {
    global $db;
    try {
        // 验证优先级是否有效
        if (!array_key_exists($priority, PRIORITY)) {
            throw new Exception("无效的优先级: $priority");
        }
        
        $stmt = $db->debugQuery("INSERT INTO todos (title, description, priority, process_chain_type) VALUES (?, ?, ?, ?)", 
                               [$title, $description, $priority, $processChainId]);
        if ($stmt) {
            $taskId = $db->getPdo()->lastInsertId();
            
            // 如果指定了工序链，为每个工序创建对应的步骤
            if ($processChainId) {
                $steps = getProcessChainSteps($processChainId);
                foreach ($steps as $step) {
                    $stepKey = $step['step_key'];
                    $stepName = PROCESSING_STEPS[$stepKey] ?? $stepKey;
                    $order = $step['order'];
                    
                    // 为每个工序创建对应的步骤记录
                    $stmt = $db->debugQuery("INSERT INTO todo_steps (todo_id, step_key, title, description, 'order') VALUES (?, ?, ?, ?, ?)", 
                                           [$taskId, $stepKey, $stepName, '', $order]);
                }
            }
            
            return $taskId;
        }
        return false;
    } catch (PDOException $e) {
        logError("添加任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        logError("添加任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 更新任务状态
function updateTaskStatus($taskId, $status) {
    global $db;
    try {
        // 验证状态是否有效
        if (!array_key_exists($status, PROCESS_STATUS) && !in_array($status, ['cancelled', 'void'])) {
            throw new Exception("无效的状态: $status");
        }
        
        // 获取任务信息
        $stmt = $db->debugQuery("SELECT * FROM todos WHERE id = ?", [$taskId]);
        if (!$stmt || !($task = $stmt->fetch())) {
            throw new Exception("找不到指定的任务");
        }
        
        // 检查任务状态变更的逻辑
        if ($status === 'void') {
            // 作废操作：只能对未开工的任务进行
            // 检查任务主状态
            if ($task['status'] !== 'pending') {
                throw new Exception("只能对未开工的任务进行作废操作");
            }
            
            // 检查是否有工序步骤已经开始处理
            $stmt = $db->debugQuery("SELECT COUNT(*) as count FROM todo_steps 
                                    WHERE todo_id = ? 
                                    AND status IN ('in-progress', 'completed')", 
                                   [$taskId]);
            
            if ($stmt && ($row = $stmt->fetch()) && $row['count'] > 0) {
                throw new Exception("任务已经开始处理，不能进行作废操作，请使用中止功能");
            }
        } elseif ($status === 'cancelled') {
            // 中止操作：只能对已经开始处理的任务进行
            // 检查是否有工序步骤已经开始处理
            $stmt = $db->debugQuery("SELECT COUNT(*) as count FROM todo_steps 
                                    WHERE todo_id = ? 
                                    AND status IN ('in-progress', 'completed')", 
                                   [$taskId]);
            
            if ($stmt && ($row = $stmt->fetch()) && $row['count'] == 0) {
                throw new Exception("任务尚未开始处理，不能进行中止操作，请使用作废功能");
            }
        }
        
        $stmt = $db->debugQuery("UPDATE todos SET status = ?, updated_at = datetime('now', 'localtime') WHERE id = ?", [$status, $taskId]);
        if ($stmt) {
            return $stmt->rowCount() > 0;
        }
        return false;
    } catch (PDOException $e) {
        logError("更新任务状态失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        logError("更新任务状态失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 删除任务
function deleteTask($taskId) {
    global $db;
    try {
        $stmt = $db->debugQuery("DELETE FROM todos WHERE id = ?", [$taskId]);
        if ($stmt) {
            return $stmt->rowCount() > 0;
        }
        return false;
    } catch (PDOException $e) {
        logError("删除任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 获取任务的步骤
function getTaskSteps($taskId) {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM todo_steps WHERE todo_id = ? ORDER BY 'order' ASC", [$taskId]);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取任务步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 根据ID获取任务
function getTaskById($taskId) {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM todos WHERE id = ?", [$taskId]);
        if ($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    } catch (PDOException $e) {
        logError("获取任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 添加任务步骤
function addTaskStep($taskId, $stepKey, $title, $description, $order) {
    global $db;
    try {
        // 验证工序步骤是否有效
        if (!array_key_exists($stepKey, PROCESSING_STEPS)) {
            throw new Exception("无效的工序步骤: $stepKey");
        }
        
        $stmt = $db->debugQuery("INSERT INTO todo_steps (todo_id, step_key, title, description, 'order') VALUES (?, ?, ?, ?, ?)", 
                               [$taskId, $stepKey, $title, $description, $order]);
        if ($stmt) {
            return $db->getPdo()->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        logError("添加任务步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        logError("添加任务步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 更新任务步骤状态
function updateTaskStepStatus($stepId, $status) {
    global $db;
    try {
        // 验证状态是否有效
        if (!array_key_exists($status, PROCESS_STATUS)) {
            throw new Exception("无效的状态: $status");
        }
        
        // 获取任务步骤信息
        $stmt = $db->debugQuery("SELECT * FROM todo_steps WHERE id = ?", [$stepId]);
        if (!$stmt || !($step = $stmt->fetch())) {
            throw new Exception("找不到指定的任务步骤");
        }
        
        $todoId = $step['todo_id'];
        $stepKey = $step['step_key'];
        
        // 获取任务信息
        $stmt = $db->debugQuery("SELECT * FROM todos WHERE id = ?", [$todoId]);
        if (!$stmt || !($task = $stmt->fetch())) {
            throw new Exception("找不到指定的任务");
        }
        
        $chainId = $task['process_chain_type'];
        
        // 如果不是设置为"待处理"状态，需要检查工序依赖
        if ($status !== 'pending' && $chainId) {
            // 获取工序链步骤
            $chainSteps = getProcessChainSteps($chainId);
            
            // 找到当前步骤在工序链中的位置
            $currentStepOrder = null;
            $stepOrderMap = [];
            foreach ($chainSteps as $index => $chainStep) {
                $stepOrderMap[$chainStep['step_key']] = $index;
                if ($chainStep['step_key'] === $stepKey) {
                    $currentStepOrder = $index;
                }
            }
            
            // 检查所有前面的步骤是否已完成
            for ($i = 0; $i < $currentStepOrder; $i++) {
                $previousStepKey = $chainSteps[$i]['step_key'];
                
                // 检查前面的步骤是否已完成
                $stmt = $db->debugQuery("SELECT status FROM todo_steps 
                                        WHERE todo_id = ? 
                                        AND step_key = ?", 
                                       [$todoId, $previousStepKey]);
                
                if ($stmt && ($previousStep = $stmt->fetch())) {
                    if ($previousStep['status'] !== 'completed') {
                        throw new Exception("前面的工序步骤(" . (PROCESSING_STEPS[$previousStepKey] ?? $previousStepKey) . ")尚未完成，无法更新当前步骤状态");
                    }
                } else {
                    throw new Exception("找不到前面的工序步骤(" . (PROCESSING_STEPS[$previousStepKey] ?? $previousStepKey) . ")的信息");
                }
            }
        }
        
        $stmt = $db->debugQuery("UPDATE todo_steps SET status = ?, updated_at = datetime('now', 'localtime') WHERE id = ?", [$status, $stepId]);
        if ($stmt) {
            $result = $stmt->rowCount() > 0;
            
            // 更新任务主状态
            if ($chainId) {
                // 获取所有任务步骤
                $stmt = $db->debugQuery("SELECT * FROM todo_steps WHERE todo_id = ?", [$todoId]);
                if ($stmt) {
                    $allSteps = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // 计算任务的整体状态
                    $hasInProgress = false;
                    $hasCompleted = false;
                    $allCompleted = true;
                    
                    foreach ($allSteps as $taskStep) {
                        if ($taskStep['status'] === 'in-progress') {
                            $hasInProgress = true;
                            $allCompleted = false;
                        } elseif ($taskStep['status'] === 'completed') {
                            $hasCompleted = true;
                            if ($taskStep['step_key'] !== $stepKey) {  // 不是当前更新的步骤
                                $allCompleted = false;
                            }
                        } else {
                            $allCompleted = false;
                        }
                    }
                    
                    // 特殊处理：如果当前步骤是完成状态，检查是否所有步骤都完成了
                    if ($status === 'completed') {
                        $allCompleted = true;
                        foreach ($allSteps as $taskStep) {
                            // 更新当前步骤的状态为传入的状态
                            $stepStatus = ($taskStep['id'] == $stepId) ? $status : $taskStep['status'];
                            if ($stepStatus !== 'completed') {
                                $allCompleted = false;
                                break;
                            }
                        }
                    }
                    
                    // 确定任务主状态
                    $taskStatus = 'pending'; // 默认为待处理
                    if ($allCompleted) {
                        $taskStatus = 'completed'; // 所有步骤完成
                    } elseif ($hasInProgress || $hasCompleted || $status === 'completed') {
                        $taskStatus = 'in-progress'; // 有步骤在进行中或已完成
                    }
                    
                    // 更新任务主状态
                    $db->debugQuery("UPDATE todos SET status = ?, updated_at = datetime('now', 'localtime') WHERE id = ?", [$taskStatus, $todoId]);
                }
            }
            
            return $result;
        }
        return false;
    } catch (PDOException $e) {
        logError("更新任务步骤状态失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        logError("更新任务步骤状态失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 添加新的工序链
function addProcessChain($name, $enabled = true) {
    global $db;
    try {
        $stmt = $db->debugQuery("INSERT INTO process_chains (name, enabled) VALUES (?, ?)", [$name, $enabled ? 1 : 0]);
        if ($stmt) {
            return $db->getPdo()->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        logError("添加工序链失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 更新工序链
function updateProcessChain($id, $name, $enabled) {
    global $db;
    try {
        $stmt = $db->debugQuery("UPDATE process_chains SET name = ?, enabled = ? WHERE id = ?", [$name, $enabled ? 1 : 0, $id]);
        if ($stmt) {
            return $stmt->rowCount() > 0;
        }
        return false;
    } catch (PDOException $e) {
        logError("更新工序链失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 删除工序链
function deleteProcessChain($id) {
    global $db;
    try {
        $stmt = $db->debugQuery("DELETE FROM process_chains WHERE id = ?", [$id]);
        if ($stmt) {
            return $stmt->rowCount() > 0;
        }
        return false;
    } catch (PDOException $e) {
        logError("删除工序链失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 为工序链添加步骤
function addStepToProcessChain($chainId, $stepKey, $order) {
    global $db;
    try {
        // 验证工序步骤是否有效
        if (!array_key_exists($stepKey, PROCESSING_STEPS)) {
            throw new Exception("无效的工序步骤: $stepKey");
        }
        
        $stmt = $db->debugQuery("INSERT INTO process_chain_steps (chain_id, step_key, 'order') VALUES (?, ?, ?)", [$chainId, $stepKey, $order]);
        if ($stmt) {
            return $db->getPdo()->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        logError("为工序链添加步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        logError("为工序链添加步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 更新工序链步骤
function updateProcessChainStep($stepId, $stepKey, $order) {
    global $db;
    try {
        // 验证工序步骤是否有效
        if (!array_key_exists($stepKey, PROCESSING_STEPS)) {
            throw new Exception("无效的工序步骤: $stepKey");
        }
        
        $stmt = $db->debugQuery("UPDATE process_chain_steps SET step_key = ?, 'order' = ? WHERE id = ?", [$stepKey, $order, $stepId]);
        if ($stmt) {
            return $stmt->rowCount() > 0;
        }
        return false;
    } catch (PDOException $e) {
        logError("更新工序链步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        logError("更新工序链步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 删除工序链步骤
function deleteProcessChainStep($stepId) {
    global $db;
    try {
        $stmt = $db->debugQuery("DELETE FROM process_chain_steps WHERE id = ?", [$stepId]);
        if ($stmt) {
            return $stmt->rowCount() > 0;
        }
        return false;
    } catch (PDOException $e) {
        logError("删除工序链步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 获取工序链的步骤
function getProcessChainSteps($chainId) {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM process_chain_steps 
                              WHERE chain_id = ? 
                              ORDER BY 'order' ASC", [$chainId]);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取工序链步骤失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取已完成任务
function getCompletedTasks() {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM todos WHERE status = 'completed' ORDER BY updated_at DESC");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取已完成任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取工序链中各工序的完成状态
function getProcessChainStepStatuses($taskId, $chainId) {
    global $db;
    try {
        // 获取工序链步骤
        $steps = getProcessChainSteps($chainId);
        $stepStatuses = [];
        
        // 对于每个步骤，检查是否有对应的任务步骤已完成
        foreach ($steps as $step) {
            $stepKey = $step['step_key'];
            
            // 查询对应的任务步骤状态
            $stmt = $db->debugQuery("SELECT * FROM todo_steps 
                                    WHERE todo_id = ? 
                                    AND step_key = ?", 
                                   [$taskId, $stepKey]);
            
            if ($stmt && $row = $stmt->fetch()) {
                // 根据任务步骤的状态来设置显示状态
                switch ($row['status']) {
                    case 'completed':
                        $stepStatuses[$stepKey] = 'completed';
                        break;
                    case 'in-progress':
                        $stepStatuses[$stepKey] = 'in-progress';
                        break;
                    default:
                        $stepStatuses[$stepKey] = 'pending';
                }
            } else {
                // 如果没有找到对应的任务步骤，说明该工序还未处理
                $stepStatuses[$stepKey] = 'pending';
            }
        }
        
        return $stepStatuses;
    } catch (PDOException $e) {
        logError("获取工序链步骤状态失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 根据ID获取工序链
function getProcessChainById($chainId) {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM process_chains WHERE id = ?", [$chainId]);
        if ($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    } catch (PDOException $e) {
        logError("获取工序链失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 根据ID获取用户
function getUserById($userId) {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    } catch (PDOException $e) {
        logError("获取用户失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 更新用户信息
function updateUser($userId, $username, $fullName, $role, $department, $isMainManager = false) {
    global $db;
    try {
        // 记录输入参数
        if (DEBUG_MODE) {
            logError("更新用户 - 输入参数: userId=$userId, username=$username, fullName=$fullName, role=$role, department=$department, isMainManager=" . ($isMainManager ? 'true' : 'false'));
        }
        
        // 验证角色和部门是否有效
        if (!array_key_exists($role, ROLES)) {
            $error = "无效的角色: $role";
            logError("更新用户失败: $error");
            throw new Exception($error);
        }
        if (!array_key_exists($department, DEPARTMENTS)) {
            $error = "无效的部门: $department";
            logError("更新用户失败: $error");
            throw new Exception($error);
        }
        
        // 检查用户名是否已存在（排除当前用户）
        $stmt = $db->debugQuery("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?", [$username, $userId]);
        if ($stmt && $stmt->fetchColumn() > 0) {
            $error = "用户名已存在: $username";
            logError("更新用户失败: $error");
            throw new Exception($error);
        }
        
        $sql = "UPDATE users SET username = ?, full_name = ?, role = ?, department = ?, is_main_manager = ? WHERE id = ?";
        $params = [$username, $fullName, $role, $department, $isMainManager ? 1 : 0, $userId];
        
        if (DEBUG_MODE) {
            logError("执行SQL: $sql " . json_encode($params));
        }
        
        $stmt = $db->debugQuery($sql, $params);
        if ($stmt) {
            $result = $stmt->rowCount() > 0;
            if (DEBUG_MODE) {
                logError("用户更新" . ($result ? "成功" : "失败"));
            }
            return $result;
        }
        
        $error = "数据库操作返回false";
        logError("更新用户失败: $error");
        return false;
    } catch (PDOException $e) {
        $error = "数据库错误: " . $e->getMessage();
        logError("更新用户失败: $error");
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        $error = "异常: " . $e->getMessage();
        logError("更新用户失败: $error");
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 删除用户
function deleteUser($userId) {
    global $db;
    try {
        // 不能删除自己
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $error = "不能删除当前登录用户";
            logError("删除用户失败: $error");
            throw new Exception($error);
        }
        
        $stmt = $db->debugQuery("DELETE FROM users WHERE id = ?", [$userId]);
        if ($stmt) {
            $result = $stmt->rowCount() > 0;
            if (DEBUG_MODE) {
                logError("用户删除" . ($result ? "成功" : "失败"));
            }
            return $result;
        }
        
        $error = "数据库操作返回false";
        logError("删除用户失败: $error");
        return false;
    } catch (PDOException $e) {
        $error = "数据库错误: " . $e->getMessage();
        logError("删除用户失败: $error");
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    } catch (Exception $e) {
        $error = "异常: " . $e->getMessage();
        logError("删除用户失败: $error");
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

// 获取指定部门的待处理任务（按优先级和创建时间排序）
function getPendingTasksForDepartmentSorted($department) {
    global $db;
    try {
        // 获取所有工序链
        $chains = getAllProcessChains();
        $filteredChainIds = [];
        
        // 筛选出包含当前部门工序的工序链
        foreach ($chains as $chain) {
            $steps = getProcessChainSteps($chain['id']);
            foreach ($steps as $step) {
                if ($step['step_key'] == $department) {
                    $filteredChainIds[] = $chain['id'];
                    break;
                }
            }
        }
        
        if (empty($filteredChainIds)) {
            return [];
        }
        
        // 构建查询，只获取当前工序的任务，并且状态是待处理的
        // 按优先级降序，创建时间降序排序
        $placeholders = str_repeat('?,', count($filteredChainIds) - 1) . '?';
        $sql = "SELECT * FROM todos 
                WHERE process_chain_type IN ($placeholders) 
                AND status = 'pending' 
                ORDER BY 
                    CASE priority 
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END,
                    created_at DESC";
        
        $stmt = $db->debugQuery($sql, $filteredChainIds);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取指定部门待处理任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取指定部门的进行中任务（按优先级和更新时间排序）
function getInProgressTasksForDepartmentSorted($department) {
    global $db;
    try {
        // 获取所有工序链
        $chains = getAllProcessChains();
        $filteredChainIds = [];
        
        // 筛选出包含当前部门工序的工序链
        foreach ($chains as $chain) {
            $steps = getProcessChainSteps($chain['id']);
            foreach ($steps as $step) {
                if ($step['step_key'] == $department) {
                    $filteredChainIds[] = $chain['id'];
                    break;
                }
            }
        }
        
        if (empty($filteredChainIds)) {
            return [];
        }
        
        // 构建查询，只获取当前工序的任务，并且状态是进行中的
        // 同时排除当前工序已完成的任务
        $placeholders = str_repeat('?,', count($filteredChainIds) - 1) . '?';
        $sql = "SELECT DISTINCT t.* FROM todos t
                JOIN todo_steps ts ON t.id = ts.todo_id
                WHERE t.process_chain_type IN ($placeholders) 
                AND t.status = 'in-progress'
                AND ts.step_key = ?
                AND ts.status != 'completed'  -- 排除当前工序已完成的任务
                ORDER BY 
                    CASE t.priority 
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END,
                    t.updated_at DESC";
        
        $params = array_merge($filteredChainIds, [$department]);
        $stmt = $db->debugQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取指定部门进行中任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取指定部门的已完成任务（按优先级和完成时间排序）
// 这里指当前工序已完成的任务步骤
function getCompletedTasksForDepartmentSorted($department) {
    global $db;
    try {
        // 获取所有包含当前部门工序的工序链
        $chains = getAllProcessChains();
        $filteredChainIds = [];
        
        // 筛选出包含当前部门工序的工序链
        foreach ($chains as $chain) {
            $steps = getProcessChainSteps($chain['id']);
            foreach ($steps as $step) {
                if ($step['step_key'] == $department) {
                    $filteredChainIds[] = $chain['id'];
                    break;
                }
            }
        }
        
        if (empty($filteredChainIds)) {
            return [];
        }
        
        // 构建查询，获取当前工序链中当前工序已完成但整个任务未完成的任务
        // 这样可以确保已完成的工序任务不会显示在"进行中"列表中
        $placeholders = str_repeat('?,', count($filteredChainIds) - 1) . '?';
        $sql = "SELECT DISTINCT t.* FROM todos t
                JOIN todo_steps ts ON t.id = ts.todo_id
                WHERE t.process_chain_type IN ($placeholders) 
                AND ts.step_key = ?
                AND ts.status = 'completed'
                AND t.status != 'completed'  -- 排除整个任务已完成的情况
                ORDER BY 
                    CASE t.priority 
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END,
                    t.updated_at DESC";
        
        $params = array_merge($filteredChainIds, [$department]);
        $stmt = $db->debugQuery($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取指定部门已完成任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取指定部门的已完结任务（整个任务都完成的，按优先级和完成时间排序）
function getFinishedTasksForDepartmentSorted($department) {
    global $db;
    try {
        // 获取所有包含当前部门工序的工序链
        $chains = getAllProcessChains();
        $filteredChainIds = [];
        
        // 筛选出包含当前部门工序的工序链
        foreach ($chains as $chain) {
            $steps = getProcessChainSteps($chain['id']);
            foreach ($steps as $step) {
                if ($step['step_key'] == $department) {
                    $filteredChainIds[] = $chain['id'];
                    break;
                }
            }
        }
        
        if (empty($filteredChainIds)) {
            return [];
        }
        
        // 构建查询，获取当前工序链中整个任务已完成的任务
        $placeholders = str_repeat('?,', count($filteredChainIds) - 1) . '?';
        $sql = "SELECT * FROM todos 
                WHERE process_chain_type IN ($placeholders) 
                AND status = 'completed' 
                ORDER BY 
                    CASE priority 
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END,
                    updated_at DESC";
        
        $stmt = $db->debugQuery($sql, $filteredChainIds);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取指定部门已完结任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取指定部门的中止/作废任务
function getCancelledVoidTasksForDepartment($department) {
    global $db;
    try {
        // 获取所有工序链
        $chains = getAllProcessChains();
        $filteredChainIds = [];
        
        // 筛选出包含当前部门工序的工序链
        foreach ($chains as $chain) {
            $steps = getProcessChainSteps($chain['id']);
            foreach ($steps as $step) {
                if ($step['step_key'] == $department) {
                    $filteredChainIds[] = $chain['id'];
                    break;
                }
            }
        }
        
        if (empty($filteredChainIds)) {
            return [];
        }
        
        // 构建查询，只获取当前工序的任务，并且状态是中止或作废的
        $placeholders = str_repeat('?,', count($filteredChainIds) - 1) . '?';
        $sql = "SELECT * FROM todos 
                WHERE process_chain_type IN ($placeholders) 
                AND status IN ('cancelled', 'void') 
                ORDER BY updated_at DESC";
        
        $stmt = $db->debugQuery($sql, $filteredChainIds);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取指定部门中止/作废任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取所有待处理任务
function getAllPendingTasks() {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM todos WHERE status = 'pending' ORDER BY created_at DESC");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取所有待处理任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取所有待处理任务（按优先级和创建时间排序）
function getAllPendingTasksSorted() {
    global $db;
    try {
        // 按优先级降序，创建时间降序排序
        $sql = "SELECT * FROM todos 
                WHERE status = 'pending' 
                ORDER BY 
                    CASE priority 
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END,
                    created_at DESC";
        
        $stmt = $db->debugQuery($sql);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取所有待处理任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取所有进行中任务（按优先级和更新时间排序）
function getAllInProgressTasksSorted() {
    global $db;
    try {
        // 按优先级降序，更新时间降序排序
        $sql = "SELECT * FROM todos 
                WHERE status = 'in-progress' 
                ORDER BY 
                    CASE priority 
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END,
                    updated_at DESC";
        
        $stmt = $db->debugQuery($sql);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取所有进行中任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取所有已完成任务（包括完成、中止、作废，按优先级和更新时间排序）
function getAllCompletedTasksSorted() {
    global $db;
    try {
        // 按优先级降序，更新时间降序排序
        $sql = "SELECT * FROM todos 
                WHERE status IN ('completed', 'cancelled', 'void') 
                ORDER BY 
                    CASE priority 
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'medium' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END,
                    updated_at DESC";
        
        $stmt = $db->debugQuery($sql);
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取所有已完成任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 获取所有中止/作废任务
function getCancelledVoidTasks() {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM todos WHERE status IN ('cancelled', 'void') ORDER BY updated_at DESC");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取所有中止/作废任务失败: " . $e->getMessage());
        if (DEBUG_MODE) {
            throw $e;
        }
        return [];
    }
}

// 显示任务工序进度情况
function displayTaskProcessProgress($task) {
    // 只有存在工序链的任务才显示工序进度
    if (!$task['process_chain_type']) {
        return '';
    }
    
    $chain = getProcessChainById($task['process_chain_type']);
    if (!$chain) {
        return '';
    }
    
    $steps = getProcessChainSteps($chain['id']);
    $stepStatuses = getProcessChainStepStatuses($task['id'], $chain['id']);
    
    // 构建工序进度HTML
    $html = '<div class="mt-2 text-sm text-gray-600 flex flex-wrap gap-2">';
    $html .= '<div class="font-medium">工序进度：</div>';
    
    // 对于已中止或作废的任务，只显示中止前已完成和进行中的工序
    $showOnlyActiveSteps = in_array($task['status'], ['cancelled', 'void']);
    
    foreach ($steps as $step) {
        $stepKey = $step['step_key'];
        $stepName = PROCESSING_STEPS[$stepKey] ?? $stepKey;
        $status = $stepStatuses[$stepKey] ?? 'pending';
        
        // 如果任务已中止或作废，只显示已完成和进行中的工序
        if ($showOnlyActiveSteps && !in_array($status, ['completed', 'in-progress'])) {
            continue;
        }
        
        // 获取对应的任务步骤信息
        global $db;
        $stmt = $db->debugQuery("SELECT * FROM todo_steps WHERE todo_id = ? AND step_key = ?", [$task['id'], $stepKey]);
        $taskStep = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        
        // 根据状态设置颜色和文本
        $statusClass = '';
        $statusText = '';
        $statusTime = '';
        
        switch ($status) {
            case 'completed':
                $statusClass = 'text-green-600';
                $statusText = '已完成';
                if ($taskStep && $taskStep['updated_at']) {
                    $statusTime = date('m-d H:i', strtotime($taskStep['updated_at']));
                }
                break;
            case 'in-progress':
                $statusClass = 'text-yellow-600';
                $statusText = '进行中';
                if ($taskStep && $taskStep['updated_at']) {
                    $statusTime = date('m-d H:i', strtotime($taskStep['updated_at']));
                }
                break;
            default:
                $statusClass = 'text-gray-500';
                $statusText = '待处理';
        }
        
        // 构建单个工序步骤的显示HTML
        $stepHtml = '<div class="' . $statusClass . ' whitespace-nowrap">';
        $stepHtml .= htmlspecialchars($stepName) . ': ' . $statusText;
        if ($statusTime) {
            $stepHtml .= ' (' . $statusTime . ')';
        }
        $stepHtml .= '</div>';
        
        $html .= $stepHtml;
    }
    
    $html .= '</div>';
    
    return $html;
}

// 检查工序步骤是否可以操作（检查前面的工序是否已完成）
function canProcessStep($todoId, $stepKey) {
    global $db;
    
    try {
        // 获取任务信息
        $stmt = $db->debugQuery("SELECT process_chain_type FROM todos WHERE id = ?", [$todoId]);
        $task = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        
        if (!$task || !$task['process_chain_type']) {
            logError("canProcessStep: 任务或工序链类型不存在, todoId=$todoId, stepKey=$stepKey");
            return false;
        }
        
        $chainId = $task['process_chain_type'];
        
        // 获取工序链步骤
        $chainSteps = getProcessChainSteps($chainId);
        
        // 找到当前步骤在工序链中的位置
        $currentStepOrder = null;
        foreach ($chainSteps as $index => $chainStep) {
            if ($chainStep['step_key'] === $stepKey) {
                $currentStepOrder = $index;
                break;
            }
        }
        
        // 如果找不到当前步骤，返回false
        if ($currentStepOrder === null) {
            logError("canProcessStep: 找不到当前工序步骤, todoId=$todoId, stepKey=$stepKey");
            return false;
        }
        
        // 如果是第一个步骤，可以直接操作
        if ($currentStepOrder === 0) {
            logError("canProcessStep: 第一个工序步骤，可以直接操作, todoId=$todoId, stepKey=$stepKey");
            return true;
        }
        
        // 检查所有前面的步骤是否已完成
        for ($i = 0; $i < $currentStepOrder; $i++) {
            $previousStepKey = $chainSteps[$i]['step_key'];
            
            // 检查前面的步骤是否已完成
            $stmt = $db->debugQuery("SELECT status FROM todo_steps 
                                    WHERE todo_id = ? 
                                    AND step_key = ?", 
                                   [$todoId, $previousStepKey]);
            
            if ($stmt && ($previousStep = $stmt->fetch())) {
                if ($previousStep['status'] !== 'completed') {
                    logError("canProcessStep: 前面的工序步骤未完成, todoId=$todoId, stepKey=$stepKey, previousStepKey=$previousStepKey, status=".$previousStep['status']);
                    return false; // 前面的步骤未完成
                }
            } else {
                // 如果找不到前面的步骤信息，说明前面的工序还没到
                logError("canProcessStep: 前面的工序步骤不存在，说明前面工序还没到, todoId=$todoId, stepKey=$stepKey, previousStepKey=$previousStepKey");
                return false;
            }
        }
        
        logError("canProcessStep: 所有前面的工序步骤都已完成, todoId=$todoId, stepKey=$stepKey");
        return true; // 所有前面的步骤都已完成
    } catch (Exception $e) {
        logError("检查工序步骤可操作性失败: " . $e->getMessage());
        return false;
    }
}

// 错误日志记录函数
function logError($message) {
    if (defined('LOG_FILE')) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents(LOG_FILE, "[$timestamp] [FUNCTIONS] $message\n", FILE_APPEND | LOCK_EX);
    }
}