<?php
// 数据库配置文件
require_once __DIR__ . '/config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            // SQLite数据库文件路径
            $dbPath = __DIR__ . '/../database/factory_todo.db';
            
            // 确保数据库目录存在
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            // 连接到SQLite数据库
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建表结构（如果不存在）
            $this->createTables();
        } catch (PDOException $e) {
            $this->logError("数据库连接失败: " . $e->getMessage());
            if (DEBUG_MODE) {
                die("数据库连接失败: " . $e->getMessage());
            } else {
                die("系统错误，请稍后重试");
            }
        }
    }
    
    private function createTables() {
        try {
            // 创建用户表（如果不存在）
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                full_name TEXT NOT NULL,
                role TEXT NOT NULL CHECK (role IN ('super-admin', 'boss', 'customer-service', 'process-manager', 'observer')),
                department TEXT NOT NULL CHECK (department IN ('cutting', 'tempering', 'laminating', 'insulating', 'warehouse', 'packing', 'shipping', 'qc', 'admin')),
                is_main_manager BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->exec($sql);
            
            // 检查是否已存在默认管理员用户，如果不存在则创建
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
            $stmt->execute();
            $adminExists = $stmt->fetchColumn();
            
            if (!$adminExists) {
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, full_name, role, department, is_main_manager) VALUES ('admin', ?, 'Adminer', 'super-admin', 'admin', 1)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$hashedPassword]);
            }
            
            // 创建工序链表
            $sql = "CREATE TABLE IF NOT EXISTS process_chains (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                enabled BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->exec($sql);
            
            // 创建工序链步骤关联表（使用常量键而不是外键）
            $sql = "CREATE TABLE IF NOT EXISTS process_chain_steps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                chain_id INTEGER NOT NULL,
                step_key TEXT NOT NULL,
                'order' INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (chain_id) REFERENCES process_chains(id) ON DELETE CASCADE,
                UNIQUE(chain_id, step_key),
                UNIQUE(chain_id, 'order')
            )";
            $this->pdo->exec($sql);
        
            
            // 创建任务表
            $sql = "CREATE TABLE IF NOT EXISTS todos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                description TEXT,
                status TEXT CHECK (status IN ('pending', 'in-progress', 'completed')) DEFAULT 'pending',
                created_by INTEGER REFERENCES users(id),
                assigned_to INTEGER REFERENCES users(id),
                due_date DATETIME,
                priority TEXT CHECK (priority IN ('critical', 'urgent', 'high', 'medium', 'low')) DEFAULT 'medium',
                process_chain_type TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->exec($sql);
            
            // 创建任务步骤表
            $sql = "CREATE TABLE IF NOT EXISTS todo_steps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                todo_id INTEGER NOT NULL REFERENCES todos(id) ON DELETE CASCADE,
                step_key TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                status TEXT CHECK (status IN ('pending', 'in-progress', 'completed')) DEFAULT 'pending',
                assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
                completed_at DATETIME,
                completed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                'order' INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $this->pdo->exec($sql);
            
            // 创建索引
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todos_priority ON todos(priority)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todos_process_chain_type ON todos(process_chain_type)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todos_status ON todos(status)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todos_created_by ON todos(created_by)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todos_assigned_to ON todos(assigned_to)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_department ON users(department)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_process_chain_steps_chain_id ON process_chain_steps(chain_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_process_chain_steps_step_key ON process_chain_steps(step_key)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todo_steps_todo_id ON todo_steps(todo_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todo_steps_status ON todo_steps(status)");
        } catch (PDOException $e) {
            $this->logError("创建表结构失败: " . $e->getMessage());
            if (DEBUG_MODE) {
                throw $e;
            }
        }
    }
    
    public function getPdo() {
        return $this->pdo;
    }
    
    // 错误日志记录
    private function logError($message) {
        if (defined('LOG_FILE')) {
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents(LOG_FILE, "[$timestamp] [DATABASE] $message\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    // 执行查询并记录调试信息
    public function debugQuery($sql, $params = []) {
        if (DB_DEBUG) {
            $this->logError("执行SQL: $sql " . json_encode($params));
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError("SQL执行失败: " . $e->getMessage() . " SQL: $sql " . json_encode($params));
            if (DEBUG_MODE) {
                throw $e;
            }
            return false;
        }
    }
}