<?php
// ============================================
// ChefGuedes - Script de Instalação Admin
// Cria a primeira conta de administrador
// ============================================

header('Content-Type: application/json; charset=utf-8');
require_once 'api/db.php';

// Apenas POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]));
}

// Receber dados
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Validações
if (empty($username) || empty($email) || empty($password)) {
    die(json_encode([
        'success' => false,
        'message' => 'Todos os campos são obrigatórios!'
    ]));
}

if (strlen($username) < 3) {
    die(json_encode([
        'success' => false,
        'message' => 'O nome de utilizador deve ter pelo menos 3 caracteres.'
    ]));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode([
        'success' => false,
        'message' => 'Email inválido.'
    ]));
}

if (strlen($password) < 6) {
    die(json_encode([
        'success' => false,
        'message' => 'A palavra-passe deve ter pelo menos 6 caracteres.'
    ]));
}

try {
    $db = getDB();
    
    // Verificar se já existe algum admin
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        die(json_encode([
            'success' => false,
            'message' => 'Já existe uma conta de administrador! Se perdeu o acesso, contacte o suporte técnico.'
        ]));
    }
    
    // Verificar se email já existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die(json_encode([
            'success' => false,
            'message' => 'Este email já está registado.'
        ]));
    }
    
    // Verificar se username já existe
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        die(json_encode([
            'success' => false,
            'message' => 'Este nome de utilizador já está em uso.'
        ]));
    }
    
    // Gerar user_code único para admin
    $userCode = 'ADMIN1';
    $stmt = $db->prepare("SELECT id FROM users WHERE user_code = ?");
    $stmt->execute([$userCode]);
    if ($stmt->fetch()) {
        // Se ADMIN1 já existe, gerar código aleatório
        do {
            $userCode = 'ADM' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 3));
            $stmt = $db->prepare("SELECT id FROM users WHERE user_code = ?");
            $stmt->execute([$userCode]);
        } while ($stmt->fetch());
    }
    
    // Criar conta admin
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, user_code, is_admin) 
        VALUES (?, ?, ?, ?, 1)
    ");
    $stmt->execute([$username, $email, $hashedPassword, $userCode]);
    
    $adminId = $db->lastInsertId();
    
    // Criar preferências padrão
    $stmt = $db->prepare("
        INSERT INTO user_preferences (user_id, newsletter, notifications) 
        VALUES (?, 1, 1)
    ");
    $stmt->execute([$adminId]);
    
    // Registar atividade
    $stmt = $db->prepare("
        INSERT INTO activities (user_id, type, description) 
        VALUES (?, 'admin_created', ?)
    ");
    $stmt->execute([$adminId, "Conta de administrador criada: $username"]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conta de administrador criada com sucesso! Redirecionando para login...',
        'admin' => [
            'id' => $adminId,
            'username' => $username,
            'email' => $email,
            'user_code' => $userCode
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao criar admin: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar conta de administrador. Verifique se executou o script SQL de atualização da base de dados.',
        'error' => $e->getMessage()
    ]);
}
?>
