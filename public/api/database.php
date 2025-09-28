<?php
class Database {
    private $db;
    
    public function __construct() {
        // Для SQLite
        $this->db = new PDO('sqlite:' . __DIR__ . '/eclipse.db');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTables();
    }
    
    private function createTables() {
        // Таблица лицензионных ключей
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS license_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                license_key TEXT UNIQUE NOT NULL,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME,
                hwid TEXT NULL,
                used BOOLEAN DEFAULT 0,
                app_id TEXT DEFAULT 'default'
            )
        ");
        
        // Таблица пользователей (для админки)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Вставь тестового пользователя (логин: admin, пароль: admin123)
        $this->db->exec("
            INSERT OR IGNORE INTO users (username, password_hash, email) 
            VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@eclipse.com')
        ");
    }
    
    public function getConnection() {
        return $this->db;
    }
}
?>
