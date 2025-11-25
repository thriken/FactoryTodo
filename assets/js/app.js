// 前端JavaScript文件
// 应用程序主对象
const App = {
    // 初始化应用
    init: function() {
        console.log("应用初始化完成");
        this.bindEvents();
    },
    
    // 绑定事件
    bindEvents: function() {
        // 表单提交事件
        $('#taskForm').on('submit', function(e) {
            e.preventDefault();
            App.addTask();
        });
        
        // 任务状态更新事件
        $(document).on('click', '.update-task-status', function() {
            const taskId = $(this).data('task-id');
            const status = $(this).data('status');
            App.updateTaskStatus(taskId, status);
        });
        
        // 任务删除事件
        $(document).on('click', '.delete-task', function() {
            const taskId = $(this).data('task-id');
            App.deleteTask(taskId);
        });
        
        // 任务步骤状态更新事件
        $(document).on('click', '.update-step-status', function() {
            const stepId = $(this).data('step-id');
            const status = $(this).data('status');
            App.updateTaskStepStatus(stepId, status);
        });
    },
    
    // 添加任务
    addTask: function() {
        const formData = {
            title: $('#task_title').val(),
            description: $('#description').val(),
            priority: $('#priority').val(),
            process_chain_id: $('#assignee_id').val(),
            action: 'add_task'
        };
        
        $.ajax({
            url: 'api.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('任务添加成功');
                    location.reload();
                } else {
                    alert('任务添加失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('添加任务请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
                alert('请求失败，请稍后重试');
            }
        });
    },
    
    // 更新任务状态
    updateTaskStatus: function(taskId, status) {
        $.ajax({
            url: 'api.php',
            method: 'POST',
            data: {
                task_id: taskId,
                status: status,
                action: 'update_task_status'
            },
            success: function(response) {
                if (response.success) {
                    alert('任务状态更新成功');
                    location.reload();
                } else {
                    alert('任务状态更新失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('更新任务状态请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
                
                // 尝试解析响应内容以获取错误信息
                let errorMessage = '请求失败，请稍后重试';
                
                try {
                    // 首先尝试解析JSON格式的响应
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    // 如果无法解析JSON，检查是否包含HTML格式的错误信息
                    if (xhr.responseText.includes('Fatal error')) {
                        // 从HTML错误信息中提取错误消息
                        const match = xhr.responseText.match(/Uncaught Exception: (.+?) in/);
                        if (match && match[1]) {
                            errorMessage = match[1];
                        } else {
                            errorMessage = '系统错误，请稍后重试';
                        }
                    } else if (xhr.responseText.includes('UNIQUE constraint failed')) {
                        errorMessage = '数据已存在，请检查输入';
                    }
                }
                
                alert('任务状态更新失败: ' + errorMessage);
            }
        });
    },
    
    // 删除任务
    deleteTask: function(taskId) {
        // 确认删除
        if (!confirm('确定要删除这个任务吗？此操作不可撤销。')) {
            return;
        }
        
        $.ajax({
            url: 'api.php',
            method: 'POST',
            data: {
                task_id: taskId,
                action: 'delete_task'
            },
            success: function(response) {
                if (response.success) {
                    alert('任务删除成功');
                    location.reload();
                } else {
                    alert('任务删除失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('删除任务请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
                
                // 尝试解析响应内容以获取错误信息
                let errorMessage = '请求失败，请稍后重试';
                
                try {
                    // 首先尝试解析JSON格式的响应
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    // 如果无法解析JSON，检查是否包含HTML格式的错误信息
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
                
                alert('任务删除失败: ' + errorMessage);
            }
        });
    },
    
    // 更新任务步骤状态
    updateTaskStepStatus: function(stepId, status) {
        $.ajax({
            url: 'api.php',
            method: 'POST',
            data: {
                step_id: stepId,
                status: status,
                action: 'update_task_step_status'
            },
            success: function(response) {
                if (response.success) {
                    // 重新加载页面以显示更新后的状态
                    location.reload();
                } else {
                    // 显示错误信息
                    alert('更新任务步骤状态请求失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('更新任务步骤状态请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
                
                // 尝试解析响应内容以获取错误信息
                let errorMessage = '请求失败，请稍后重试';
                
                try {
                    // 首先尝试解析JSON格式的响应
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    // 如果无法解析JSON，检查是否包含HTML格式的错误信息
                    if (xhr.responseText.includes('Fatal error')) {
                        // 从HTML错误信息中提取错误消息
                        const match = xhr.responseText.match(/Uncaught Exception: (.+?) in/);
                        if (match && match[1]) {
                            errorMessage = match[1];
                        } else {
                            errorMessage = '系统错误，请稍后重试';
                        }
                    } else if (xhr.responseText.includes('UNIQUE constraint failed')) {
                        errorMessage = '数据已存在，请检查输入';
                    }
                }
                
                alert('任务步骤状态更新失败: ' + errorMessage);
            }
        });
    }
};

// 应用初始化
$(document).ready(function() {
    App.init();
});