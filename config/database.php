<?php
// 数据库配置文件

class Database {
    private $pdo;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            // SQLite数据库文件路径
            $dbPath = __DIR__ . '/../database/factory_todo.sqlite';
            
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
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        // 创建用户表
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL CHECK (role IN ('super-admin', 'boss', 'customer-service', 'process-manager', 'observer')),
            department TEXT NOT NULL CHECK (department IN ('cutting', 'tempering', 'laminating', 'insulating', 'warehouse', 'packing', 'shipping', 'qc')),
            is_main_manager BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        
        // 创建工序步骤表
        $sql = "CREATE TABLE IF NOT EXISTS processing_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            process_type TEXT NOT NULL CHECK (process_type IN ('cutting', 'tempering', 'laminating', 'insulating', 'warehouse', 'packing', 'shipping', 'qc')),
            description TEXT,
            'order' INTEGER NOT NULL,
            enabled BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        
        // 创建工序链表
        $sql = "CREATE TABLE IF NOT EXISTS process_chains (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            type TEXT NOT NULL CHECK (type IN ('single', 'insulating', 'laminating', 'laminating-insulating')),
            enabled BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        
        // 创建工序链步骤关联表
        $sql = "CREATE TABLE IF NOT EXISTS process_chain_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chain_id INTEGER NOT NULL,
            step_id INTEGER NOT NULL,
            'order' INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (chain_id) REFERENCES process_chains(id) ON DELETE CASCADE,
            FOREIGN KEY (step_id) REFERENCES processing_steps(id) ON DELETE CASCADE,
            UNIQUE(chain_id, step_id),
            UNIQUE(chain_id, 'order')
        )";
        $this->pdo->exec($sql);
        
        // 创建任务优先级枚举（通过CHECK约束实现）
        // SQLite不支持ENUM类型，所以我们使用CHECK约束
        
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
            process_chain_type TEXT CHECK (process_chain_type IN ('single', 'insulating', 'laminating', 'laminating-insulating')) DEFAULT 'single',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        
        // 创建任务步骤表
        $sql = "CREATE TABLE IF NOT EXISTS todo_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            todo_id INTEGER NOT NULL REFERENCES todos(id) ON DELETE CASCADE,
            step_id INTEGER REFERENCES processing_steps(id) ON DELETE SET NULL,
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
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_processing_steps_order ON processing_steps('order')");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_processing_steps_process_type ON processing_steps(process_type)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_process_chain_steps_chain_id ON process_chain_steps(chain_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_process_chain_steps_step_id ON process_chain_steps(step_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todo_steps_todo_id ON todo_steps(todo_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_todo_steps_status ON todo_steps(status)");
    }
    
    public function getPdo() {
        return $this->pdo;
    }
}