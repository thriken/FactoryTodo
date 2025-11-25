// 管理页面专用JavaScript文件

// 角色定义（与PHP中的常量对应）
const ROLES_ADMIN = {
    'super-admin': '超级管理员',
    'boss': '高管',
    'customer-service': '客服'
};

const ROLES_PROCESS = {
    'process-manager': '负责人',
    'observer': '员工'
};

const DEPARTMENTS = {
    'cutting': '切割',
    'tempering': '钢化',
    'laminating': '夹层',
    'insulating': '中空',
    'warehouse': '仓库',
    'packing': '包装',
    'shipping': '发货',
    'qc': '质检',
    'admin': '管理'
};

// 工序步骤定义（与PHP中的常量对应）
const PROCESSING_STEPS = {
    'cutting': '切割',
    'tempering': '钢化',
    'laminating': '夹层',
    'insulating': '中空',
    'warehouse': '仓库',
    'packing': '包装',
    'shipping': '发货',
    'qc': '质检'
};

// 根据部门获取角色列表
function getRolesByDepartment(department) {
    if (department === 'admin') {
        return ROLES_ADMIN;
    } else {
        return ROLES_PROCESS;
    }
}

// 更新角色下拉列表
function updateRoleOptions(selectElement = null) {
    const departmentSelect = selectElement || document.getElementById('department');
    const roleSelect = departmentSelect.id === 'department' ? 
        document.getElementById('role') : 
        document.getElementById('edit-role');
    const selectedDepartment = departmentSelect.value;
    
    // 清空现有选项
    roleSelect.innerHTML = '';
    
    // 获取该部门对应的角色
    const roles = getRolesByDepartment(selectedDepartment);
    
    // 添加角色选项
    for (const [roleKey, roleName] of Object.entries(roles)) {
        const option = document.createElement('option');
        option.value = roleKey;
        option.textContent = roleName;
        roleSelect.appendChild(option);
    }
}

// 添加用户
function addUser() {
    const formData = {
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
        full_name: document.getElementById('full_name').value,
        role: document.getElementById('role').value,
        department: document.getElementById('department').value,
        is_main_manager: document.getElementById('is_main_manager').checked ? 1 : 0,
        action: 'add_user'
    };
    
    // 发送AJAX请求添加用户
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: formData,
        success: function(response) {
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
}

// 更新用户
function updateUser() {
    const formData = {
        user_id: document.getElementById('edit-user-id').value,
        username: document.getElementById('edit-username').value,
        full_name: document.getElementById('edit-full_name').value,
        role: document.getElementById('edit-role').value,
        department: document.getElementById('edit-department').value,
        is_main_manager: document.getElementById('edit-is_main_manager').checked ? 1 : 0,
        action: 'update_user'
    };
    
    // 发送AJAX请求更新用户
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('用户更新成功');
                location.reload();
            } else {
                alert('用户更新失败: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('更新用户请求失败:', status, error);
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
            
            alert('用户更新失败: ' + errorMessage);
        }
    });
}

// 删除用户
function deleteUser(userId, username) {
    if (confirm('确定要删除用户 ' + username + ' 吗？')) {
        // 发送AJAX请求删除用户
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: {
                user_id: userId,
                action: 'delete_user'
            },
            success: function(response) {
                if (response.success) {
                    alert('用户已删除');
                    location.reload();
                } else {
                    alert('删除用户失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('删除用户请求失败:', status, error);
                alert('请求失败，请稍后重试');
            }
        });
    }
}

// 添加工序链
function addProcessChain() {
    const formData = {
        name: document.getElementById('chain_name').value,
        enabled: document.getElementById('chain_enabled').checked ? 1 : 0,
        action: 'add_process_chain'
    };
    
    // 发送AJAX请求添加工序链
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('工序链添加成功');
                location.reload();
            } else {
                alert('工序链添加失败: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('添加工序链请求失败:', status, error);
            alert('请求失败，请稍后重试');
        }
    });
}

// 加载工序链步骤
function loadChainSteps(chainId) {
    // 发送AJAX请求获取工序链步骤
    $.ajax({
        url: '../api.php',
        method: 'GET',
        data: {
            action: 'get_chain_steps',
            chain_id: chainId
        },
        success: function(response) {
            if (response.success) {
                displayChainStepsInTable(response.data, chainId);
            } else {
                alert('获取工序链步骤失败: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('获取工序链步骤请求失败:', status, error);
            alert('请求失败，请稍后重试');
        }
    });
}

// 显示编辑工序链表单
function editProcessChain(chainId, chainName, chainEnabled) {
    document.getElementById('edit_chain_id').value = chainId;
    document.getElementById('edit_chain_name').value = chainName;
    document.getElementById('edit_chain_enabled').checked = chainEnabled;
    document.getElementById('edit-process-chain-form').classList.remove('hidden');
}

// 取消编辑工序链
function cancelEditProcessChain() {
    document.getElementById('edit-process-chain-form').classList.add('hidden');
}

// 更新工序链
function updateProcessChain() {
    const formData = {
        id: document.getElementById('edit_chain_id').value,
        name: document.getElementById('edit_chain_name').value,
        enabled: document.getElementById('edit_chain_enabled').checked ? 1 : 0,
        action: 'update_process_chain'
    };
    
    // 发送AJAX请求更新工序链
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('工序链已更新');
                location.reload();
            } else {
                alert('更新工序链失败: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('更新工序链请求失败:', status, error);
            alert('请求失败，请稍后重试');
        }
    });
}

// 删除工序链
function deleteProcessChain(chainId) {
    if (confirm('确定要删除该工序链吗？')) {
        // 发送AJAX请求删除工序链
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: {
                action: 'delete_process_chain',
                id: chainId
            },
            success: function(response) {
                if (response.success) {
                    alert('工序链已删除');
                    location.reload();
                } else {
                    alert('删除工序链失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('删除工序链请求失败:', status, error);
                alert('请求失败，请稍后重试');
            }
        });
    }
}

// 在表格中显示工序链步骤
function displayChainStepsInTable(steps, chainId) {
    const container = document.getElementById('chain-steps-management');
    const tableBody = document.getElementById('chain-steps-table-body');
    const chainIdInput = document.getElementById('chain_id_input');
    
    // 设置当前工序链ID
    chainIdInput.value = chainId;
    
    // 清空现有内容
    tableBody.innerHTML = '';
    
    if (steps.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="3" class="px-6 py-4 text-center text-gray-500">该工序链暂无步骤</td>';
        tableBody.appendChild(row);
    } else {
        // 添加步骤数据
        steps.forEach(step => {
            const stepName = PROCESSING_STEPS[step.step_key] || step.step_key;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${stepName}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${step.order}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button onclick="editChainStep(${step.id}, '${step.step_key}', ${step.order})" class="text-green-600 hover:text-green-900 mr-2">编辑</button>
                    <button onclick="deleteChainStep(${step.id})" class="text-red-600 hover:text-red-900">删除</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }
    
    // 显示管理表格
    container.classList.remove('hidden');
    
    // 重置表单
    resetStepForm();
}

// 重置步骤表单
function resetStepForm() {
    document.getElementById('step_id').value = '';
    document.getElementById('step_key_select').value = '';
    document.getElementById('step_order_input').value = '0';
    document.getElementById('step-form-title').textContent = '添加新步骤';
    document.querySelector('#step-management-form button[type="submit"]').textContent = '添加步骤';
}

// 编辑工序链步骤
function editChainStep(stepId, stepKey, order) {
    // 填充表单数据
    document.getElementById('step_id').value = stepId;
    document.getElementById('step_key_select').value = stepKey;
    document.getElementById('step_order_input').value = order;
    
    // 更改表单标题和按钮文本
    document.getElementById('step-form-title').textContent = '编辑步骤';
    document.querySelector('#step-management-form button[type="submit"]').textContent = '更新步骤';
}

// 删除工序链步骤
function deleteChainStep(stepId) {
    if (confirm('确定要删除该工序步骤吗？')) {
        // 发送AJAX请求删除工序链步骤
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: {
                action: 'delete_chain_step',
                step_id: stepId
            },
            success: function(response) {
                if (response.success) {
                    alert('工序步骤已删除');
                    // 重新加载当前工序链的步骤
                    const chainId = document.getElementById('chain_id_input').value;
                    if (chainId) {
                        loadChainSteps(chainId);
                    }
                } else {
                    alert('删除工序步骤失败: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('删除工序步骤请求失败:', status, error);
                alert('请求失败，请稍后重试');
            }
        });
    }
}

// 处理步骤表单提交
function handleStepFormSubmit(event) {
    event.preventDefault();
    
    const stepId = document.getElementById('step_id').value;
    const chainId = document.getElementById('chain_id_input').value;
    const stepKey = document.getElementById('step_key_select').value;
    const order = document.getElementById('step_order_input').value;
    
    if (!chainId) {
        alert('请先选择一个工序链');
        return;
    }
    
    if (!stepKey) {
        alert('请选择工序步骤');
        return;
    }
    
    const formData = {
        chain_id: chainId,
        step_key: stepKey,
        order: order,
        action: stepId ? 'update_chain_step' : 'add_step_to_chain'
    };
    
    // 如果是更新操作，添加step_id
    if (stepId) {
        formData.step_id = stepId;
    }
    
    // 发送AJAX请求
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert(stepId ? '工序步骤更新成功' : '工序步骤添加成功');
                // 重新加载当前工序链的步骤
                loadChainSteps(chainId);
            } else {
                alert((stepId ? '更新' : '添加') + '工序步骤失败: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error((stepId ? '更新' : '添加') + '工序步骤请求失败:', status, error);
            alert('请求失败，请稍后重试');
        }
    });
}

// 更新任务状态
function updateTaskStatus(taskId, status) {
    $.ajax({
        url: '../api.php',
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
                const response = JSON.parse(xhr.responseText);
                if (response && response.error) {
                    errorMessage = response.error;
                }
            } catch (e) {
                // 如果无法解析JSON，检查是否包含特定错误信息
                if (xhr.responseText.includes('Fatal error')) {
                    // 从HTML错误信息中提取错误消息
                    const match = xhr.responseText.match(/Uncaught Exception: (.+?) in/);
                    if (match && match[1]) {
                        errorMessage = match[1];
                    } else {
                        errorMessage = '系统错误，请稍后重试';
                    }
                } else if (xhr.responseText.includes('UNIQUE constraint failed')) {
                    errorMessage = '用户名已存在，请选择其他用户名';
                }
            }
            
            alert('任务状态更新失败: ' + errorMessage);
        }
    });
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 检查当前页面是否存在工序链步骤管理表单
    const stepManagementForm = document.getElementById('step-management-form');
    const cancelStepEditButton = document.getElementById('cancel-step-edit');
    
    if (stepManagementForm) {
        // 绑定步骤表单提交事件
        stepManagementForm.addEventListener('submit', handleStepFormSubmit);
    }
    
    if (cancelStepEditButton) {
        // 绑定取消编辑按钮事件
        cancelStepEditButton.addEventListener('click', resetStepForm);
    }
    
    // 检查是否存在用户管理表单
    const addUserForm = document.getElementById('add-user-form');
    const departmentSelect = document.getElementById('department');
    const editUserForm = document.getElementById('edit-user-form');
    const editDepartmentSelect = document.getElementById('edit-department');
    
    if (addUserForm) {
        // 绑定用户表单提交事件
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addUser();
        });
    }
    
    if (departmentSelect) {
        // 初始化角色下拉列表
        updateRoleOptions();
        
        // 监听部门选择变化
        departmentSelect.addEventListener('change', function() {
            updateRoleOptions(departmentSelect);
        });
    }
    
    if (editUserForm) {
        // 绑定编辑用户表单提交事件
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateUser();
        });
    }
    
    if (editDepartmentSelect) {
        // 监听编辑表单中部门选择变化
        editDepartmentSelect.addEventListener('change', function() {
            updateRoleOptions(editDepartmentSelect);
        });
    }
    
    // 检查是否存在工序链管理表单
    const addProcessChainForm = document.getElementById('add-process-chain-form');
    const updateProcessChainForm = document.getElementById('update-process-chain-form');
    
    if (addProcessChainForm) {
        // 绑定工序链表单提交事件
        addProcessChainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addProcessChain();
        });
    }
    
    if (updateProcessChainForm) {
        // 绑定工序链更新表单提交事件
        updateProcessChainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateProcessChain();
        });
    }
});