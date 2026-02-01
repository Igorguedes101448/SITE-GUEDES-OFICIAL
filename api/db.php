<?php
// ============================================
// ChefGuedes - Configuração da Base de Dados
// Conexão MySQL com PDO
// ============================================

// Configurações da base de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'siteguedes');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Classe de conexão à base de dados
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode([
                'success' => false,
                'message' => 'Erro de conexão com a base de dados: ' . $e->getMessage()
            ]));
        }
    }
    
    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // Prevenir clonagem
    private function __clone() {}
    
    // Prevenir deserialização
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Função auxiliar para obter conexão
function getDB() {
    return Database::getInstance()->getConnection();
}

// Headers para API (apenas se não estiver em CLI)
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Tratar OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Função para resposta JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Função para erro JSON
function jsonError($message, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

// Função para sucesso JSON
function jsonSuccess($message, $data = null) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    jsonResponse($response);
}
?>
