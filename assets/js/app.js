// 前端JavaScript文件

$(document).ready(function() {
    // 页面加载完成后的初始化操作
    console.log("应用初始化完成");
    
    // 绑定表单提交事件
    $('#add-user-form').on('submit', function(e) {
        e.preventDefault();
        addUser();
    });
    
    $('#add-task-form').on('submit', function(e) {
        e.preventDefault();
        addTask();
    });
    
    // 绑定任务状态更新事件
    $('.update-task-status').on('click', function() {
        const taskId = $(this).data('task-id');
        const status = $(this).data('status');
        updateTaskStatus(taskId, status);
    });
    
    // 加载初始数据
    loadTasks();
    loadUsers();
});

// 添加用户
function addUser() {
    const formData = {
        email: $('#email').val(),
        full_name: $('#full_name').val(),
        role: $('#role').val(),
        department: $('#department').val(),
        is_main_manager: $('#is_main_manager').is(':checked') ? 1 : 0
    };
    
    $.ajax({
        url: 'api.php?action=add_user',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showMessage('用户添加成功', 'success');
                // 清空表单
                $('#add-user-form')[0].reset();
                // 重新加载用户列表
                loadUsers();
            } else {
                showMessage('用户添加失败: ' + response.error, 'error');
            }
        },
        error: function(xhr, status, error) {
            showMessage('请求失败: ' + error, 'error');
        }
    });
}

// 添加任务
function addTask() {
    const formData = {
        title: $('#title').val(),
        description: $('#description').val(),
        priority: $('#priority').val(),
        process_chain_type: $('#process_chain_type').val()
    };
    
    $.ajax({
        url: 'api.php?action=add_task',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showMessage('任务添加成功', 'success');
                // 清空表单
                $('#add-task-form')[0].reset();
                // 重新加载任务列表
                loadTasks();
            } else {
                showMessage('任务添加失败: ' + response.error, 'error');
            }
        },
        error: function(xhr, status, error) {
            showMessage('请求失败: ' + error, 'error');
        }
    });
}

// 更新任务状态
function updateTaskStatus(taskId, status) {
    const formData = {
        task_id: taskId,
        status: status
    };
    
    $.ajax({
        url: 'api.php?action=update_task_status',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showMessage('任务状态更新成功', 'success');
                // 重新加载任务列表
                loadTasks();
            } else {
                showMessage('任务状态更新失败: ' + response.error, 'error');
            }
        },
        error: function(xhr, status, error) {
            showMessage('请求失败: ' + error, 'error');
        }
    });
}

// 加载任务列表
function loadTasks() {
    $.ajax({
        url: 'api.php?action=tasks',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderTasks(response.data);
            } else {
                showMessage('加载任务失败: ' + response.error, 'error');
            }
        },
        error: function(xhr, status, error) {
            showMessage('请求失败: ' + error, 'error');
        }
    });
}

// 加载用户列表
function loadUsers() {
    $.ajax({
        url: 'api.php?action=users',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderUsers(response.data);
            } else {
                showMessage('加载用户失败: ' + response.error, 'error');
            }
        },
        error: function(xhr, status, error) {
            showMessage('请求失败: ' + error, 'error');
        }
    });
}

// 渲染任务列表
function renderTasks(tasks) {
    const tasksContainer = $('#tasks-list');
    tasksContainer.empty();
    
    if (tasks.length === 0) {
        tasksContainer.html('<p class="text-gray-500">暂无任务</p>');
        return;
    }
    
    tasks.forEach(function(task) {
        const taskElement = `
            <div class="bg-white rounded-lg shadow p-4 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-semibold">${escapeHtml(task.title)}</h3>
                        <p class="text-gray-600">${escapeHtml(task.description)}</p>
                    </div>
                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                        ${escapeHtml(task.status)}
                    </span>
                </div>
                <div class="mt-3 flex space-x-2">
                    <button class="update-task-status text-xs bg-green-100 text-green-800 px-2 py-1 rounded hover:bg-green-200" 
                            data-task-id="${task.id}" data-status="completed">
                        完成
                    </button>
                    <button class="update-task-status text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded hover:bg-yellow-200" 
                            data-task-id="${task.id}" data-status="in-progress">
                        进行中
                    </button>
                </div>
            </div>
        `;
        tasksContainer.append(taskElement);
    });
    
    // 重新绑定事件
    $('.update-task-status').on('click', function() {
        const taskId = $(this).data('task-id');
        const status = $(this).data('status');
        updateTaskStatus(taskId, status);
    });
}

// 渲染用户列表
function renderUsers(users) {
    const usersContainer = $('#users-list');
    usersContainer.empty();
    
    if (users.length === 0) {
        usersContainer.html('<p class="text-gray-500">暂无用户</p>');
        return;
    }
    
    users.forEach(function(user) {
        const userElement = `
            <div class="bg-white rounded-lg shadow p-4 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-semibold">${escapeHtml(user.full_name)}</h3>
                        <p class="text-gray-600">${escapeHtml(user.email)}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                            ${escapeHtml(user.role)}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">${user.is_main_manager ? '主负责人' : '普通员工'}</p>
                    </div>
                </div>
            </div>
        `;
        usersContainer.append(userElement);
    });
}

// 显示消息
function showMessage(message, type) {
    const messageClass = type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    const messageElement = `
        <div class="fixed top-4 right-4 px-4 py-3 rounded ${messageClass} shadow-lg z-50" id="flash-message">
            ${escapeHtml(message)}
        </div>
    `;
    
    $('body').append(messageElement);
    
    // 3秒后自动消失
    setTimeout(function() {
        $('#flash-message').fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}

// 转义HTML特殊字符
function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}