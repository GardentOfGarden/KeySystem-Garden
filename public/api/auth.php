<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Подключаем базу данных
require_once 'database.php';

class EclipseAuthAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Для OPTIONS запроса (CORS)
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
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $this->sendSuccess([
                'token' => bin2hex(random_bytes(32)),
                'user' => ['username' => $user['username'], 'email' => $user['email']]
            ]);
        } else {
            $this->sendError('Invalid credentials');
        }
    }
    
    private function validateKey($data) {
        $key = $data['key'] ?? '';
        $hwid = $data['hwid'] ?? '';
        
        $stmt = $this->db->prepare("SELECT * FROM license_keys WHERE license_key = ? AND status = 'active'");
        $stmt->execute([$key]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($license) {
            // Проверяем срок действия
            if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
                $this->sendError('License expired');
                return;
            }
            
            if (empty($license['hwid'])) {
                // Первое использование - привязываем HWID
                $stmt = $this->db->prepare("UPDATE license_keys SET hwid = ?, used = 1 WHERE license_key = ?");
                $stmt->execute([$hwid, $key]);
                $this->sendSuccess(['valid' => true, 'message' => 'License activated']);
            } else if ($license['hwid'] === $hwid) {
                $this->sendSuccess(['valid' => true, 'message' => 'License valid']);
            } else {
                $this->sendError('HWID mismatch - this license is already used on another device');
            }
        } else {
            $this->sendError('Invalid license key');
        }
    }
    
    private function generateKey() {
        $key = 'ECLIPSE-' . bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(8));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $this->db->prepare("INSERT INTO license_keys (license_key, status, expires_at) VALUES (?, 'active', ?)");
        $stmt->execute([strtoupper($key), $expires]);
        
        $this->sendSuccess([
            'key' => strtoupper($key), 
            'expires' => $expires,
            'message' => 'Key generated successfully'
        ]);
    }
    
    private function getKeys() {
        $stmt = $this->db->query("SELECT * FROM license_keys ORDER BY created_at DESC");
        $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendSuccess(['keys' => $keys]);
    }
    
    private function deleteKey($data) {
        $keyId = $data['key_id'] ?? '';
        
        $stmt = $this->db->prepare("DELETE FROM license_keys WHERE id = ?");
        $stmt->execute([$keyId]);
        
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

// Обработка запроса
$api = new EclipseAuthAPI();
$api->handleRequest();
?>
