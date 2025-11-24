# 工厂任务单系统使用说明

## 系统访问

1. 本地服务器一直运行着，php8.0+mysql5.7+nginx1.27
2. 通过浏览器访问：https://factorytodo/

## 用户登录

系统会自动跳转到登录页面。由于这是一个演示系统，您可以输入任意邮箱和密码进行登录。

## 主要功能

### 1. 首页
- 显示最近创建的任务
- 提供导航到其他页面的链接

### 2. 任务管理
- 查看所有任务列表
- 添加新任务
- 更新任务状态（进行中、完成）

### 3. 用户管理
- 查看所有用户列表
- 添加新用户

### 4. 管理面板
- 用户管理：添加和查看用户
- 任务管理：添加和查看任务
- 工序管理：查看工序步骤
- 工序链管理：查看工序链

## 系统管理

### 初始化系统数据
运行安装脚本以初始化默认的工序步骤和工序链：
```
php install.php
```

### 测试系统功能
运行测试脚本以验证数据库连接和基本功能：
```
php test_db.php
```

## 技术说明

### 数据库
- 使用SQLite数据库，文件位于`database/factory_todo.sqlite`
- 系统会自动创建所需的表结构

### API端点
- `GET /api.php?action=tasks` - 获取所有任务
- `GET /api.php?action=users` - 获取所有用户
- `POST /api.php?action=add_user` - 添加用户
- `POST /api.php?action=add_task` - 添加任务
- `POST /api.php?action=update_task_status` - 更新任务状态

### 前端交互
- 使用jQuery处理AJAX请求
- 使用TailwindCSS进行样式设计
- 响应式布局，适配不同设备

## 开发指南

### 添加新功能
1. 在`includes/functions.php`中添加数据库操作函数
2. 在`api.php`中添加相应的API端点
3. 在前端页面中添加用户界面元素
4. 在`assets/js/app.js`中添加相应的JavaScript处理逻辑

### 数据库修改
1. 修改`config/database.php`中的表结构定义
2. 更新`includes/functions.php`中的相关函数
3. 如果需要迁移数据，创建相应的迁移脚本

## 常见问题

### 1. 数据库文件权限问题
确保`database`目录具有写权限，以便系统可以创建和修改SQLite数据库文件。

### 2. PHP版本兼容性
系统需要PHP 8.0或更高版本。较低版本的PHP可能不支持某些语法特性。

### 3. JavaScript功能不工作
确保浏览器启用了JavaScript，并且可以访问jQuery库（通过CDN加载）。