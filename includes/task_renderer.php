<?php
// 任务渲染器 - 用于在不同页面中复用任务显示逻辑

/**
 * 渲染任务卡片
 * @param array $task 任务数据
 * @param string $type 任务类型
 * @param string $userDepartment 当前用户部门
 * @param bool $showActions 是否显示操作按钮
 */
function renderTaskCard($task, $type, $userDepartment = '', $showActions = true) {
    $priorityClass = getPriorityClass($task['priority']);
    $priorityText = PRIORITY[$task['priority']] ?? $task['priority'];
    
    // 根据任务类型设置状态背景色
    $statusBgClass = 'bg-gray-100';
    $statusTextClass = 'text-gray-800';
    
    if ($type === 'in-progress') {
        $statusBgClass = 'bg-yellow-100';
        $statusTextClass = 'text-yellow-800';
    } elseif ($type === 'completed' || $type === 'finished') {
        $statusBgClass = 'bg-green-100';
        $statusTextClass = 'text-green-800';
    }
    
    echo '<div class="bg-white rounded-lg shadow-sm p-2 hover:bg-gray-50 transition">
            <div class="flex justify-between items-start">
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-sm text-gray-800 truncate">' . htmlspecialchars($task['title']) . '</h3>';
    
    if ($task['description']) {
        echo '<p class="text-gray-600 text-xs mt-1 truncate">' . htmlspecialchars($task['description']) . '</p>';
    }
    
    echo '        </div>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium ' . $priorityClass . ' ml-1 flex-shrink-0">
                    ' . $priorityText . '
                </span>
            </div>
            
            <div class="mt-2 flex flex-wrap gap-1">';
    
    if ($task['process_chain_type']) {
        $chain = getProcessChainById($task['process_chain_type']);
        if ($chain) {
            echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ' . htmlspecialchars($chain['name']) . '
                  </span>';
        }
    }
    
    echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium ' . $statusBgClass . ' ' . $statusTextClass . '">
            ' . (PROCESS_STATUS[$task['status']] ?? $task['status']) . '
          </span>
          <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            ' . date('m-d H:i', strtotime($task['created_at'])) . '
          </span>';
    
    // 对于已完成和已完结任务，显示完成/完结时间
    if ($type === 'completed' || $type === 'finished') {
        echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                ' . ($type === 'completed' ? '完成时间' : '完结时间') . ': ' . date('m-d H:i', strtotime($task['updated_at'])) . '
              </span>';
    }
    
    echo '        </div>';
    
    // 工序完成情况显示
    if ($task['process_chain_type']) {
        if ($type === 'completed' || $type === 'finished') {
            // 对于已完成和已完结任务，使用统一的进度显示
            echo '<div class="mt-2">
                    ' . displayTaskProcessProgress($task) . '
                  </div>';
        } else {
            // 对于待处理和进行中任务，显示详细工序步骤
            renderProcessSteps($task, $userDepartment, $type);
        }
    }
    
    echo '</div>';
}

/**
 * 渲染任务列表
 * @param array $tasks 任务数组
 * @param string $type 任务类型 (pending/in-progress/completed/finished)
 * @param string $userDepartment 当前用户部门
 * @param bool $showActions 是否显示操作按钮
 */
function renderTasks($tasks, $type, $userDepartment = '', $showActions = true) {
    $emptyMessages = [
        'pending' => '暂无待处理任务',
        'in-progress' => '暂无进行中任务',
        'completed' => '暂无已完成任务',
        'finished' => '暂无已完结任务'
    ];
    
    if (empty($tasks)) {
        echo '<div class="bg-white rounded-lg shadow-sm p-6">
                <p class="text-gray-500 text-center py-8">' . ($emptyMessages[$type] ?? '暂无任务') . '</p>
              </div>';
        return;
    }
    
    echo '<div class="space-y-2">';
    foreach ($tasks as $task) {
        renderTaskCard($task, $type, $userDepartment, $showActions);
    }
    echo '</div>';
}

/**
 * 渲染待处理任务列表
 * @param array $tasks 任务数组
 * @param string $userDepartment 当前用户部门
 */
function renderPendingTasks($tasks, $userDepartment) {
    renderTasks($tasks, 'pending', $userDepartment);
}

/**
 * 渲染进行中任务列表
 * @param array $tasks 任务数组
 * @param string $userDepartment 当前用户部门
 */
function renderInProgressTasks($tasks, $userDepartment) {
    renderTasks($tasks, 'in-progress', $userDepartment);
}

/**
 * 渲染已完成任务列表
 * @param array $tasks 任务数组
 */
function renderCompletedTasks($tasks) {
    renderTasks($tasks, 'completed');
}

/**
 * 渲染已完结任务列表
 * @param array $tasks 任务数组
 */
function renderFinishedTasks($tasks) {
    renderTasks($tasks, 'finished');
}

/**
 * 渲染待处理任务卡片
 * @param array $task 任务数据
 * @param string $userDepartment 当前用户部门
 */
function renderPendingTaskCard($task, $userDepartment) {
    $priorityClass = getPriorityClass($task['priority']);
    $priorityText = PRIORITY[$task['priority']] ?? $task['priority'];
    
    echo '<div class="bg-white rounded-lg shadow-sm p-2 hover:bg-gray-50 transition">
            <div class="flex justify-between items-start">
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-sm text-gray-800 truncate">' . htmlspecialchars($task['title']) . '</h3>';
    
    if ($task['description']) {
        echo '<p class="text-gray-600 text-xs mt-1 truncate">' . htmlspecialchars($task['description']) . '</p>';
    }
    
    echo '        </div>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium ' . $priorityClass . ' ml-1 flex-shrink-0">
                    ' . $priorityText . '
                </span>
            </div>
            
            <div class="mt-2 flex flex-wrap gap-1">';
    
    if ($task['process_chain_type']) {
        $chain = getProcessChainById($task['process_chain_type']);
        if ($chain) {
            echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ' . htmlspecialchars($chain['name']) . '
                  </span>';
        }
    }
    
    echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            ' . (PROCESS_STATUS[$task['status']] ?? $task['status']) . '
          </span>
          <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            ' . date('m-d H:i', strtotime($task['created_at'])) . '
          </span>
        </div>';
    
    // 工序完成情况显示
    if ($task['process_chain_type']) {
        renderProcessSteps($task, $userDepartment, 'pending');
    }
    
    echo '</div>';
}

/**
 * 渲染进行中任务卡片
 * @param array $task 任务数据
 * @param string $userDepartment 当前用户部门
 */
function renderInProgressTaskCard($task, $userDepartment) {
    $priorityClass = getPriorityClass($task['priority']);
    $priorityText = PRIORITY[$task['priority']] ?? $task['priority'];
    
    echo '<div class="bg-white rounded-lg shadow-sm p-2 hover:bg-gray-50 transition">
            <div class="flex justify-between items-start">
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-sm text-gray-800 truncate">' . htmlspecialchars($task['title']) . '</h3>';
    
    if ($task['description']) {
        echo '<p class="text-gray-600 text-xs mt-1 truncate">' . htmlspecialchars($task['description']) . '</p>';
    }
    
    echo '        </div>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium ' . $priorityClass . ' ml-1 flex-shrink-0">
                    ' . $priorityText . '
                </span>
            </div>
            
            <div class="mt-2 flex flex-wrap gap-1">';
    
    if ($task['process_chain_type']) {
        $chain = getProcessChainById($task['process_chain_type']);
        if ($chain) {
            echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ' . htmlspecialchars($chain['name']) . '
                  </span>';
        }
    }
    
    echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
            ' . (PROCESS_STATUS[$task['status']] ?? $task['status']) . '
          </span>
          <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            ' . date('m-d H:i', strtotime($task['created_at'])) . '
          </span>
        </div>';
    
    // 工序完成情况显示
    if ($task['process_chain_type']) {
        renderProcessSteps($task, $userDepartment, 'in-progress');
    }
    
    echo '</div>';
}

/**
 * 渲染已完成任务卡片
 * @param array $task 任务数据
 */
function renderCompletedTaskCard($task) {
    echo '<div class="bg-white rounded-lg shadow-sm p-2 hover:bg-gray-50 transition">
            <div class="flex justify-between items-start">
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-sm text-gray-800 truncate">' . htmlspecialchars($task['title']) . '</h3>';
    
    if ($task['description']) {
        echo '<p class="text-gray-600 text-xs mt-1 truncate">' . htmlspecialchars($task['description']) . '</p>';
    }
    
    echo '        </div>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-1 flex-shrink-0">
                    ' . (PROCESS_STATUS[$task['status']] ?? $task['status']) . '
                </span>
            </div>
            
            <div class="mt-2 flex flex-wrap gap-1">';
    
    if ($task['process_chain_type']) {
        $chain = getProcessChainById($task['process_chain_type']);
        if ($chain) {
            echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ' . htmlspecialchars($chain['name']) . '
                  </span>';
        }
    }
    
    echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            ' . date('m-d H:i', strtotime($task['created_at'])) . '
          </span>
          <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            完成时间: ' . date('m-d H:i', strtotime($task['updated_at'])) . '
          </span>
        </div>
        
        <div class="mt-2">
            ' . displayTaskProcessProgress($task) . '
        </div>
    </div>';
}

/**
 * 渲染已完结任务卡片
 * @param array $task 任务数据
 */
function renderFinishedTaskCard($task) {
    echo '<div class="bg-white rounded-lg shadow-sm p-2 hover:bg-gray-50 transition">
            <div class="flex justify-between items-start">
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-sm text-gray-800 truncate">' . htmlspecialchars($task['title']) . '</h3>';
    
    if ($task['description']) {
        echo '<p class="text-gray-600 text-xs mt-1 truncate">' . htmlspecialchars($task['description']) . '</p>';
    }
    
    echo '        </div>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-1 flex-shrink-0">
                    ' . (PROCESS_STATUS[$task['status']] ?? $task['status']) . '
                </span>
            </div>
            
            <div class="mt-2 flex flex-wrap gap-1">';
    
    if ($task['process_chain_type']) {
        $chain = getProcessChainById($task['process_chain_type']);
        if ($chain) {
            echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ' . htmlspecialchars($chain['name']) . '
                  </span>';
        }
    }
    
    echo '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            ' . date('m-d H:i', strtotime($task['created_at'])) . '
          </span>
          <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            完结时间: ' . date('m-d H:i', strtotime($task['updated_at'])) . '
          </span>
        </div>
        
        <div class="mt-2">
            ' . displayTaskProcessProgress($task) . '
        </div>
    </div>';
}

/**
 * 渲染工序步骤
 * @param array $task 任务数据
 * @param string $userDepartment 当前用户部门
 * @param string $taskType 任务类型 (pending/in-progress)
 */
function renderProcessSteps($task, $userDepartment, $taskType) {
    $chain = getProcessChainById($task['process_chain_type']);
    if (!$chain) return;
    
    $steps = getProcessChainSteps($chain['id']);
    $stepStatuses = getProcessChainStepStatuses($task['id'], $chain['id']);
    
    echo '<div class="mt-2 text-xs">';
    
    foreach ($steps as $step) {
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
        
        // 将用户部门中文名转换为英文键进行比较
        $userDepartmentKey = array_search($userDepartment, DEPARTMENTS);
        if ($userDepartmentKey === false) {
            $userDepartmentKey = $userDepartment;
        }
        
        echo '<div class="flex items-center justify-between py-0.5">
                <span class="inline-block px-1.5 py-0.5 rounded ' . $statusClass . '">
                    ' . htmlspecialchars($stepName) . ': <span class="font-medium">' . $statusText . '</span>
                </span>';
        
        // 只有在待处理任务中才显示操作按钮
        if ($taskType === 'pending' && $stepKey === $userDepartmentKey && $status !== 'completed' && $taskStepId) {
            if (canProcessStep($task['id'], $stepKey)) {
                echo '<div class="flex space-x-1">
                        <button data-step-id="' . $taskStepId . '" data-status="in-progress"
                            class="update-step-status text-xs px-1.5 py-0.5 bg-yellow-500 text-white rounded">
                            开始
                        </button>
                        <button data-step-id="' . $taskStepId . '" data-status="completed"
                            class="update-step-status text-xs px-1.5 py-0.5 bg-green-500 text-white rounded">
                            完成
                        </button>
                      </div>';
            } else {
                echo '<span class="text-xs px-1.5 py-0.5 bg-gray-300 text-gray-600 rounded">
                        未到达
                      </span>';
            }
        }
        // 在进行中任务中只显示完成按钮
        else if ($taskType === 'in-progress' && $stepKey === $userDepartmentKey && $status !== 'completed' && $taskStepId) {
            if (canProcessStep($task['id'], $stepKey)) {
                echo '<button data-step-id="' . $taskStepId . '" data-status="completed"
                        class="update-step-status text-xs px-1.5 py-0.5 bg-green-500 text-white rounded">
                        完成
                      </button>';
            } else {
                echo '<span class="text-xs px-1.5 py-0.5 bg-gray-300 text-gray-600 rounded">
                        未到达
                      </span>';
            }
        }
        
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * 获取优先级CSS类
 * @param string $priority 优先级
 * @return string CSS类名
 */
function getPriorityClass($priority) {
    switch ($priority) {
        case 'critical':
            return 'bg-red-100 text-red-800';
        case 'urgent':
            return 'bg-orange-100 text-orange-800';
        case 'high':
            return 'bg-yellow-100 text-yellow-800';
        case 'medium':
            return 'bg-green-100 text-green-800';
        case 'low':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}