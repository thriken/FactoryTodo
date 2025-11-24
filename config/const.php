<?php
// 系统常量定义文件

// 用户角色-管理组，发布任务 仅管理部门 DEPARTMENTS=admin
const ROLES_ADMIN = [
    'super-admin' => '超级管理员',
    'boss' => '高管',
    'customer-service' => '客服'
];
// 角色-工人组，完成任务
const ROLES_PROCESS = [
    'process-manager' => '负责人',
    'observer' => '员工'
];

// 用户角色
const ROLES = [
    'super-admin' => '超级管理员',
    'boss' => '高管',
    'customer-service' => '客服',
    'process-manager' => '负责人',
    'observer' => '员工'
];

// 部门
const DEPARTMENTS = [
    'cutting' => '切割',
    'tempering' => '钢化',
    'laminating' => '夹层',
    'insulating' => '中空',
    'warehouse' => '仓库',
    'packing' => '包装',
    'shipping' => '发货',
    'qc' => '质检',
    'admin' => '管理'
];

// 工序步骤
const PROCESSING_STEPS = [
    'cutting' => '切割',
    'tempering' => '钢化',
    'laminating' => '夹层',
    'insulating' => '中空',
    'warehouse' => '仓库',
    'packing' => '包装',
    'shipping' => '发货',
    'qc' => '质检'
];

// 任务状态
const PROCESS_STATUS = [
    'pending' => '待处理',
    'in-progress' => '进行中',
    'completed' => '已完成',
    'cancelled' => '已中止',
    'void' => '已作废'
];

// 优先级
const PRIORITY = [
    'critical' => '不惜一切代价',
    'urgent' => '特急',
    'high' => '优先',
    'medium' => '加急',
    'low' => '普通'
];

?>