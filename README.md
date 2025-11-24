# 工厂任务单系统 (PHP版本)

使用PHP 8.0 + SQLite + HTML5 + TailwindCSS + jQuery重构的工厂任务单系统。

## 项目结构

```
php-project/
├── assets/
│   └── js/
│       └── app.js          # 前端JavaScript文件
├── config/
│   └── database.php        # 数据库配置文件
├── includes/
│   └── functions.php       # 数据库操作函数
├── database/
│   └── factory_todo.db     # SQLite数据库文件（自动生成）
├── index.php               # 主入口文件
├── login.php               # 登录页面
├── logout.php              # 登出页面
├── admin.php               # 管理页面
├── api.php                 # API端点文件
├── header.php              # 头部文件
└── footer.php              # 尾部文件

```

## 技术栈

- **后端**: PHP 8.0
- **数据库**: SQLite
- **前端**: HTML5, TailwindCSS, jQuery
- **架构**: 简单的视图和逻辑分离（非MVC模式）

## 运行环境
- PHP 8.0
- SQLite
- Web服务器（如Apache或Nginx）
- PHP扩展（如PDO SQLite）

## 功能特性

1. 用户认证（登录/登出）
2. 任务管理（创建、查看、更新状态）
3. 用户管理（创建、查看）
4. 工序管理（查看）
5. 工序链管理（查看）
6. 响应式设计
7. AJAX交互

## 安装和运行

1. 确保服务器环境支持PHP 8.0和SQLite
2. 将项目文件放置在Web服务器目录中
3. 确保`database`目录具有写权限
4. 通过浏览器访问`index.php`

## 数据库结构

系统会自动创建以下表：
- `users`: 用户表
- `processing_steps`: 工序步骤表
- `process_chains`: 工序链表
- `process_chain_steps`: 工序链步骤关联表
- `todos`: 任务表
- `todo_steps`: 任务步骤表

## API端点

- `GET /api.php?action=tasks` - 获取所有任务
- `GET /api.php?action=users` - 获取所有用户
- `POST /api.php?action=add_user` - 添加用户
- `POST /api.php?action=add_task` - 添加任务
- `POST /api.php?action=update_task_status` - 更新任务状态

## 开发说明

1. 数据库操作函数位于`includes/functions.php`
2. 数据库配置位于`config/database.php`
3. 前端交互逻辑位于`assets/js/app.js`
4. 页面路由通过`index.php`的`page`参数控制

### 数据流向：
 - 用户在前端页面进行操作
 - app.js捕获用户操作并发送AJAX请求到api.php
 - api.php验证请求参数并调用functions.php处理业务逻辑
 - functions.php通过database.php操作数据库
 - 处理结果通过api.php返回JSON格式响应
 - app.js接收响应并更新前端界面

## 注意事项

1. 这是一个简化的实现，生产环境需要加强安全措施
2. 用户权限验证是简化的，实际使用中需要完善
3. 错误处理和日志记录可以进一步增强