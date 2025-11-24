<?php
// 数据库操作函数文件

require_once __DIR__ . '/../config/database.php';

// 初始化数据库连接
$db = new Database();
$pdo = $db->getPdo();

// 获取所有任务
function getAllTasks() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM todos ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取任务失败: " . $e->getMessage());
        return [];
    }
}

// 获取最近任务
function getRecentTasks() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM todos ORDER BY created_at DESC LIMIT 5");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取最近任务失败: " . $e->getMessage());
        return [];
    }
}

// 获取所有用户
function getAllUsers() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取用户失败: " . $e->getMessage());
        return [];
    }
}

// 获取所有工序步骤
function getAllProcessingSteps() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM processing_steps ORDER BY 'order' ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取工序步骤失败: " . $e->getMessage());
        return [];
    }
}

// 获取所有工序链
function getAllProcessChains() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM process_chains ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取工序链失败: " . $e->getMessage());
        return [];
    }
}

// 添加新用户
function addUser($email, $fullName, $role, $department, $isMainManager = false) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, role, department, is_main_manager) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $fullName, $role, $department, $isMainManager ? 1 : 0]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("添加用户失败: " . $e->getMessage());
        return false;
    }
}

// 添加新任务
function addTask($title, $description, $createdBy, $assignedTo = null, $priority = 'medium', $processChainType = 'single') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO todos (title, description, created_by, assigned_to, priority, process_chain_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $createdBy, $assignedTo, $priority, $processChainType]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("添加任务失败: " . $e->getMessage());
        return false;
    }
}

// 更新任务状态
function updateTaskStatus($taskId, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE todos SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $taskId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("更新任务状态失败: " . $e->getMessage());
        return false;
    }
}

// 删除任务
function deleteTask($taskId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
        $stmt->execute([$taskId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("删除任务失败: " . $e->getMessage());
        return false;
    }
}

// 获取任务的步骤
function getTaskSteps($taskId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM todo_steps WHERE todo_id = ? ORDER BY 'order' ASC");
        $stmt->execute([$taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("获取任务步骤失败: " . $e->getMessage());
        return [];
    }
}

// 添加任务步骤
function addTaskStep($taskId, $stepId, $title, $description, $order) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO todo_steps (todo_id, step_id, title, description, 'order') VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$taskId, $stepId, $title, $description, $order]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("添加任务步骤失败: " . $e->getMessage());
        return false;
    }
}

// 更新任务步骤状态
function updateTaskStepStatus($stepId, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE todo_steps SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $stepId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("更新任务步骤状态失败: " . $e->getMessage());
        return false;
    }
}