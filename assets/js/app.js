// 前端JavaScript文件
// 应用程序主对象
const App = {
    // 初始化应用
    init: function() {
        console.log("应用初始化完成");
        this.bindEvents();
        this.loadInitialData();
    },
    
    // 绑定事件
    bindEvents: function() {
        // 表单提交事件
        $('#add-user-form').on('submit', function(e) {
            e.preventDefault();
            App.addUser();
        });
        
        $('#add-task-form').on('submit', function(e) {
            e.preventDefault();
            App.addTask();
        });
        
        // 任务状态更新事件
        $('.update-task-status').on('click', function() {
            const taskId = $(this).data('task-id');
            const status = $(this).data('status');
            App.updateTaskStatus(taskId, status);
        });
    },
    
    // 加载初始数据
    loadInitialData: function() {
        this.loadTasks();
        this.loadUsers();
    },
    
    // 添加用户
    addUser: function() {
        const formData = {
            username: $('#username').val(),
            password: $('#password').val(),
            full_name: $('#full_name').val(),
            role: $('#role').val(),
            department: $('#department').val(),
            action: 'add_user'
        };
        
        console.log('发送添加用户请求:', formData);
        
        $.ajax({
            url: 'api.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                console.log('添加用户响应:', response);
                if (response.success) {
                    alert('用户添加成功');
                    location.reload();
                } else {
                    alert('用户添加失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('添加用户请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
                
                // 尝试解析响应内容以获取错误信息
                let errorMessage = '请求失败，请稍后重试';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    // 如果无法解析JSON，检查是否包含特定错误信息
                    if (xhr.responseText.includes('UNIQUE constraint failed')) {
                        errorMessage = '用户名已存在，请选择其他用户名';
                    }
                }
                
                alert('用户添加失败: ' + errorMessage);
            }
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
                alert('请求失败，请稍后重试');
            }
        });
    },
    
    // 加载任务
    loadTasks: function() {
        $.ajax({
            url: 'api.php',
            method: 'GET',
            data: { action: 'tasks' },
            success: function(response) {
                if (response.success) {
                    console.log('任务数据加载成功', response.data);
                } else {
                    console.error('任务数据加载失败', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('加载任务请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
            }
        });
    },
    
    // 加载用户
    loadUsers: function() {
        $.ajax({
            url: 'api.php',
            method: 'GET',
            data: { action: 'users' },
            success: function(response) {
                if (response.success) {
                    console.log('用户数据加载成功', response.data);
                } else {
                    console.error('用户数据加载失败', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('加载用户请求失败:', status, error);
                console.error('响应内容:', xhr.responseText);
            }
        });
    },
    
    // 显示名称转换函数
    getDisplayName: function(value, type) {
        const mappings = {
            roles: {
                'super-admin': '超级管理员',
                'boss': '高管',
                'customer-service': '客服',
                'process-manager': '负责人',
                'observer': '员工'
            },
            departments: {
                'cutting': '切割',
                'tempering': '钢化',
                'laminating': '夹层',
                'insulating': '中空',
                'warehouse': '仓库',
                'packing': '包装',
                'shipping': '发货',
                'qc': '质检',
                'admin': '管理'
            },
            processingSteps: {
                'cutting': '切割',
                'tempering': '钢化',
                'laminating': '夹层',
                'insulating': '中空',
                'warehouse': '仓库',
                'packing': '包装',
                'shipping': '发货',
                'qc': '质检'
            },
            status: {
                'pending': '待处理',
                'in-progress': '进行中',
                'completed': '已完成',
                'cancelled': '已中止',
                'void': '已作废'
            },
            priority: {
                'critical': '不惜一切代价',
                'urgent': '特急',
                'high': '优先',
                'medium': '加急',
                'low': '普通'
            }
        };
        
        return mappings[type] && mappings[type][value] ? mappings[type][value] : value;
    }
};

// 页面加载完成后初始化应用
$(document).ready(function() {
    App.init();
});