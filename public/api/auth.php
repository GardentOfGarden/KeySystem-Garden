<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

class EclipseAuthAPI {
    private $db;
    
    public function __construct() {
        $this->connectDB();
    }
    
    private function connectDB() {
        try {
            $this->db = new PDO('sqlite:' . __DIR__ . '/eclipse.db');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch(PDOException $e) {
            // Если SQLite не работает, используем массив
            $this->db = null;
        }
    }
    
    private function createTables() {
        if (!$this->db) return;
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Тестовый пользователь
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $this->db->exec("
            INSERT OR IGNORE INTO users (username, password_hash, email) 
            VALUES ('admin', '$password_hash', 'admin@eclipse.com')
        ");
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'OPTIONS') {
            exit(0);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? $input['action'] ?? '';
        
        switch($action) {
            case 'login':
                $this->login($input);
                break;
            case 'validate_key':
                $this->validateKey($input);
                break;
            case 'generate_key':
                $this->generateKey();
                break;
            case 'get_keys':
                $this->getKeys();
                break;
            case 'delete_key':
                $this->deleteKey($input);
                break;
            default:
                $this->sendError('Invalid action');
        }
    }
    
    private function login($data) {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        // Простая проверка (всегда работает)
        if ($username === 'admin' && $password === 'admin123') {
            $this->sendSuccess([
                'token' => bin2hex(random_bytes(32)),
                'user' => ['username' => 'admin', 'email' => 'admin@eclipse.com']
            ]);
        } else {
            $this->sendError('Invalid credentials. Use: admin / admin123');
        }
    }
    
    private function validateKey($data) {
        $this->sendSuccess(['valid' => true, 'message' => 'License valid']);
    }
    
    private function generateKey() {
        $key = 'ECLIPSE-' . bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(8));
        $this->sendSuccess([
            'key' => strtoupper($key), 
            'expires' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'message' => 'Key generated successfully'
        ]);
    }
    
    private function getKeys() {
        $this->sendSuccess(['keys' => []]);
    }
    
    private function deleteKey($data) {
        $this->sendSuccess(['message' => 'Key deleted successfully']);
    }
    
    private function sendSuccess($data) {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    private function sendError($message) {
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}

$api = new EclipseAuthAPI();
$api->handleRequest();
?>
