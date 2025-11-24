<?php
// 系统安装脚本
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== 工厂任务单系统安装脚本 ===\n\n";

// 添加默认工序步骤
echo "1. 添加默认工序步骤...\n";

$defaultSteps = [
    ['切割', 'cutting', '原材料切割工序', 1, true],
    ['钢化', 'tempering', '玻璃钢化工序', 2, true],
    ['夹层', 'laminating', '夹层玻璃制作工序', 3, true],
    ['中空', 'insulating', '中空玻璃制作工序', 4, true],
    ['库房', 'warehouse', '产品入库工序', 5, true],
    ['打包', 'packing', '产品打包工序', 6, true],
    ['发货', 'shipping', '产品发货工序', 7, true],
    ['质检', 'qc', '产品质量检验工序', 8, true]
];

foreach ($defaultSteps as $step) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO processing_steps (name, process_type, description, 'order', enabled) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($step);
        echo "   ✓ 添加工序步骤: {$step[0]}\n";
    } catch (Exception $e) {
        echo "   ✗ 添加工序步骤 {$step[0]} 失败: " . $e->getMessage() . "\n";
    }
}

// 添加默认工序链
echo "\n2. 添加默认工序链...\n";

// 单片玻璃工序链: 切割 -> 钢化 -> 库房 -> 发货
$singleChainSteps = [
    ['切割', 0],
    ['钢化', 1],
    ['库房', 2],
    ['发货', 3]
];

// 中空玻璃工序链: 切割 -> 钢化 -> 中空 -> 库房 -> 发货
$insulatingChainSteps = [
    ['切割', 0],
    ['钢化', 1],
    ['中空', 2],
    ['库房', 3],
    ['发货', 4]
];

// 夹层玻璃工序链: 切割 -> 钢化 -> 夹层 -> 库房 -> 发货
$laminatingChainSteps = [
    ['切割', 0],
    ['钢化', 1],
    ['夹层', 2],
    ['库房', 3],
    ['发货', 4]
];

// 夹层中空玻璃工序链: 切割 -> 钢化 -> 夹层 -> 中空 -> 库房 -> 发货
$laminatingInsulatingChainSteps = [
    ['切割', 0],
    ['钢化', 1],
    ['夹层', 2],
    ['中空', 3],
    ['库房', 4],
    ['发货', 5]
];

$defaultChains = [
    ['单片玻璃', 'single', $singleChainSteps],
    ['中空玻璃', 'insulating', $insulatingChainSteps],
    ['夹层玻璃', 'laminating', $laminatingChainSteps],
    ['夹层中空玻璃', 'laminating-insulating', $laminatingInsulatingChainSteps]
];

foreach ($defaultChains as $chain) {
    try {
        global $pdo;
        
        // 添加工序链
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO process_chains (name, type, enabled) VALUES (?, ?, ?)");
        $stmt->execute([$chain[0], $chain[1], true]);
        
        // 获取工序链ID
        $stmt = $pdo->prepare("SELECT id FROM process_chains WHERE name = ?");
        $stmt->execute([$chain[0]]);
        $chainData = $stmt->fetch();
        
        if ($chainData) {
            $chainId = $chainData['id'];
            
            // 删除现有的工序链步骤关联
            $stmt = $pdo->prepare("DELETE FROM process_chain_steps WHERE chain_id = ?");
            $stmt->execute([$chainId]);
            
            // 添加工序链步骤关联
            foreach ($chain[2] as $step) {
                // 获取工序步骤ID
                $stmt = $pdo->prepare("SELECT id FROM processing_steps WHERE name = ?");
                $stmt->execute([$step[0]]);
                $stepData = $stmt->fetch();
                
                if ($stepData) {
                    $stepId = $stepData['id'];
                    $stmt = $pdo->prepare("INSERT INTO process_chain_steps (chain_id, step_id, 'order') VALUES (?, ?, ?)");
                    $stmt->execute([$chainId, $stepId, $step[1]]);
                }
            }
            
            echo "   ✓ 添加工序链: {$chain[0]}\n";
        }
    } catch (Exception $e) {
        echo "   ✗ 添加工序链 {$chain[0]} 失败: " . $e->getMessage() . "\n";
    }
}

echo "\n3. 安装完成!\n";
echo "   系统已初始化默认数据，可以开始使用。\n";
echo "   请通过浏览器访问 http://localhost:8000 登录系统。\n";
?>