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

// 检查用户是否有管理权限
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
    return in_array($userRole, ['super-admin', 'boss', 'customer-service']);
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

// 获取最近任务
function getRecentTasks() {
    global $db;
    try {
        $stmt = $db->debugQuery("SELECT * FROM todos ORDER BY created_at DESC LIMIT 5");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    } catch (PDOException $e) {
        logError("获取最近任务失败: " . $e->getMessage());
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
            return $db->getPdo()->lastInsertId();
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
        
        $stmt = $db->debugQuery("UPDATE todos SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$status, $taskId]);
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
        
        $stmt = $db->debugQuery("UPDATE todo_steps SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$status, $stepId]);
        if ($stmt) {
            return $stmt->rowCount() > 0;
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
            
            // 查询是否有对应的任务步骤已完成
            $stmt = $db->debugQuery("SELECT * FROM todo_steps 
                                    WHERE todo_id = ? 
                                    AND step_key = ? 
                                    AND status = 'completed'", 
                                   [$taskId, $stepKey]);
            
            if ($stmt && $stmt->fetch()) {
                $stepStatuses[$stepKey] = 'completed';
            } else {
                // 检查是否有进行中的任务步骤
                $stmt = $db->debugQuery("SELECT * FROM todo_steps 
                                        WHERE todo_id = ? 
                                        AND step_key = ? 
                                        AND status = 'in-progress'", 
                                       [$taskId, $stepKey]);
                
                if ($stmt && $stmt->fetch()) {
                    $stepStatuses[$stepKey] = 'in-progress';
                } else {
                    $stepStatuses[$stepKey] = 'pending';
                }
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

// 错误日志记录函数
function logError($message) {
    if (defined('LOG_FILE')) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents(LOG_FILE, "[$timestamp] [FUNCTIONS] $message\n", FILE_APPEND | LOCK_EX);
    }
}