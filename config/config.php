<?php
// 项目配置文件

// 设置时区为北京时间
date_default_timezone_set('Asia/Shanghai');

// 开发模式开关
const DEBUG_MODE = true;  // 设置为false可关闭调试模式

// 错误报告设置
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// 数据库调试
const DB_DEBUG = DEBUG_MODE;

// 日志文件路径
const LOG_FILE = __DIR__ . '/../logs/app.log';

// 确保日志目录存在
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

?>