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
        // Для Render.com - используй переменные окружения
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'eclipse_auth';
        $username = getenv('DB_USER') ?: 'username';
        $password = getenv('DB_PASS') ?: 'password';
        
        try {
            $this->db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            $this->sendError('Database connection failed');
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch($action) {
            case 'login':
                $this->login();
                break;
            case 'validate_key':
                $this->validateKey();
                break;
            case 'generate_key':
                $this->generateKey();
                break;
            default:
                $this->sendError('Invalid action');
        }
    }
    
    private function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        // Простая проверка (замени на реальную логику)
        if ($username === 'admin' && $password === 'admin') {
            $this->sendSuccess(['token' => bin2hex(random_bytes(32))]);
        } else {
            $this->sendError('Invalid credentials');
        }
    }
    
    private function validateKey() {
        $data = json_decode(file_get_contents('php://input'), true);
        $key = $data['key'] ?? '';
        $hwid = $data['hwid'] ?? '';
        
        $stmt = $this->db->prepare("SELECT * FROM license_keys WHERE license_key = ? AND status = 'active'");
        $stmt->execute([$key]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($license) {
            if (empty($license['hwid'])) {
                // Первое использование - привязываем HWID
                $stmt = $this->db->prepare("UPDATE license_keys SET hwid = ?, used = 1 WHERE license_key = ?");
                $stmt->execute([$hwid, $key]);
                $this->sendSuccess(['valid' => true, 'message' => 'License activated']);
            } else if ($license['hwid'] === $hwid) {
                $this->sendSuccess(['valid' => true, 'message' => 'License valid']);
            } else {
                $this->sendError('HWID mismatch');
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
        
        $this->sendSuccess(['key' => strtoupper($key), 'expires' => $expires]);
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
