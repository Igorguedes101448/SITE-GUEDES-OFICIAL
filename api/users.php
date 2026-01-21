<?php
// ============================================
// ChefGuedes - API de Utilizadores
// Gestão completa de utilizadores
// ============================================

require_once 'db.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// ============================================
// REGISTO DE UTILIZADOR
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'register') {
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validações
    if (strlen($username) < 3) {
        jsonError('O nome de utilizador deve ter pelo menos 3 caracteres.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Email inválido.');
    }
    
    if (strlen($password) < 6) {
        jsonError('A palavra-passe deve ter pelo menos 6 caracteres.');
    }
    
    try {
        $db = getDB();
        
        // Verificar se email já existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonError('Este email já está registado.');
        }
        
        // Verificar se username já existe
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            jsonError('Este nome de utilizador já está em uso.');
        }
        
        // Gerar user_code único
        $userCode = '';
        do {
            $userCode = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
            $stmt = $db->prepare("SELECT id FROM users WHERE user_code = ?");
            $stmt->execute([$userCode]);
        } while ($stmt->fetch());
        
        // Criar utilizador
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, user_code) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $hashedPassword, $userCode]);
        
        $userId = $db->lastInsertId();
        
        // Criar preferências padrão
        $stmt = $db->prepare("
            INSERT INTO user_preferences (user_id, newsletter, notifications) 
            VALUES (?, 0, 1)
        ");
        $stmt->execute([$userId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'register', ?)
        ");
        $stmt->execute([$userId, "Novo utilizador: $username"]);
        
        // Buscar dados do utilizador
        $stmt = $db->prepare("SELECT id, username, email, user_code, profile_picture, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        jsonSuccess('Conta criada com sucesso!', ['user' => $user]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao criar conta: ' . $e->getMessage(), 500);
    }
}

// ============================================
// LOGIN
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'login') {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $rememberMe = $input['rememberMe'] ?? false;
    
    if (empty($email) || empty($password)) {
        jsonError('Preencha todos os campos.');
    }
    
    try {
        $db = getDB();
        
        // Buscar utilizador
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            jsonError('Email ou palavra-passe incorretos.');
        }
        
        // Verificar se o utilizador está banido
        if (isset($user['banned']) && $user['banned'] == 1) {
            $banReason = $user['banned_reason'] ?? 'Sem motivo especificado';
            jsonError('A sua conta foi banida. Motivo: ' . $banReason, 403);
        }
        
        // Verificar se o utilizador está suspenso
        if (isset($user['suspended_until']) && $user['suspended_until'] && strtotime($user['suspended_until']) > time()) {
            $suspendedUntil = date('d/m/Y H:i', strtotime($user['suspended_until']));
            jsonError('A sua conta está suspensa até ' . $suspendedUntil, 403);
        }
        
        // Se estava suspenso mas o tempo expirou, limpar suspensão
        if (isset($user['suspended_until']) && $user['suspended_until'] && strtotime($user['suspended_until']) <= time()) {
            $stmt = $db->prepare("UPDATE users SET suspended_until = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
        
        // Criar token de sessão
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = $rememberMe ? date('Y-m-d H:i:s', strtotime('+30 days')) : date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Guardar sessão na base de dados
        $stmt = $db->prepare("
            INSERT INTO sessions (user_id, session_token, remember_me, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $sessionToken,
            $rememberMe ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expiresAt
        ]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'login', ?)
        ");
        $stmt->execute([$user['id'], "Login: {$user['username']}"]);
        
        // Buscar preferências
        $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $preferences = $stmt->fetch();
        
        // Preparar resposta
        unset($user['password']);
        $user['preferences'] = $preferences;
        
        jsonSuccess('Login efetuado com sucesso!', [
            'user' => $user,
            'sessionToken' => $sessionToken
        ]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao fazer login: ' . $e->getMessage(), 500);
    }
}

// ============================================
// LOGOUT
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'logout') {
    $sessionToken = $input['sessionToken'] ?? '';
    
    if (!empty($sessionToken)) {
        try {
            $db = getDB();
            
            // Buscar informações da sessão antes de apagar
            $stmt = $db->prepare("SELECT user_id FROM sessions WHERE session_token = ?");
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Registar atividade
                $stmt = $db->prepare("
                    SELECT username FROM users WHERE id = ?
                ");
                $stmt->execute([$session['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $stmt = $db->prepare("
                        INSERT INTO activities (user_id, type, description) 
                        VALUES (?, 'logout', ?)
                    ");
                    $stmt->execute([$session['user_id'], "Logout: {$user['username']}"]);
                }
                
                // Apagar sessão
                $stmt = $db->prepare("DELETE FROM sessions WHERE session_token = ?");
                $stmt->execute([$sessionToken]);
            }
            
            jsonSuccess('Logout efetuado com sucesso!');
            
        } catch (PDOException $e) {
            jsonError('Erro ao fazer logout: ' . $e->getMessage(), 500);
        }
    } else {
        jsonSuccess('Logout efetuado com sucesso!');
    }
}

// ============================================
// VERIFICAR SESSÃO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'verify_session') {
    $sessionToken = $input['sessionToken'] ?? '';
    
    if (empty($sessionToken)) {
        jsonError('Token de sessão não fornecido.', 401);
    }
    
    try {
        $db = getDB();
        
        // Buscar sessão
        $stmt = $db->prepare("
            SELECT s.*, u.id, u.username, u.email, u.user_code, u.profile_picture, u.phone, u.bio, u.location, u.created_at, u.is_admin
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.session_token = ? AND (s.expires_at IS NULL OR s.expires_at > NOW())
        ");
        $stmt->execute([$sessionToken]);
        $session = $stmt->fetch();
        
        if (!$session) {
            jsonError('Sessão inválida ou expirada.', 401);
        }
        
        // Buscar preferências
        $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$session['user_id']]);
        $preferences = $stmt->fetch();
        
        // Buscar favoritos
        $stmt = $db->prepare("SELECT recipe_id FROM favorites WHERE user_id = ?");
        $stmt->execute([$session['user_id']]);
        $favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $user = [
            'id' => $session['id'],
            'username' => $session['username'],
            'email' => $session['email'],
            'user_code' => $session['user_code'],
            'profile_picture' => $session['profile_picture'],
            'phone' => $session['phone'],
            'bio' => $session['bio'],
            'location' => $session['location'],
            'created_at' => $session['created_at'],
            'is_admin' => $session['is_admin'] ?? 0,
            'preferences' => $preferences,
            'favorites' => $favorites
        ];
        
        jsonSuccess('Sessão válida.', ['user' => $user]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao verificar sessão: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ATUALIZAR PERFIL
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'update_profile') {
    $sessionToken = $input['sessionToken'] ?? '';
    
    if (empty($sessionToken)) {
        jsonError('Token de sessão não fornecido.', 401);
    }
    
    try {
        $db = getDB();
        
        // Verificar sessão
        $stmt = $db->prepare("SELECT user_id FROM sessions WHERE session_token = ? AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->execute([$sessionToken]);
        $session = $stmt->fetch();
        
        if (!$session) {
            jsonError('Sessão inválida ou expirada.', 401);
        }
        
        $userId = $session['user_id'];
        
        // Atualizar dados do utilizador
        $updates = [];
        $params = [];
        
        if (isset($input['username'])) {
            $updates[] = "username = ?";
            $params[] = trim($input['username']);
        }
        if (isset($input['email'])) {
            $updates[] = "email = ?";
            $params[] = trim($input['email']);
        }
        if (isset($input['phone'])) {
            $updates[] = "phone = ?";
            $params[] = trim($input['phone']);
        }
        if (isset($input['bio'])) {
            $updates[] = "bio = ?";
            $params[] = trim($input['bio']);
        }
        if (isset($input['location'])) {
            $updates[] = "location = ?";
            $params[] = trim($input['location']);
        }
        if (isset($input['profilePicture'])) {
            $updates[] = "profile_picture = ?";
            $params[] = $input['profilePicture'];
        }
        
        if (!empty($updates)) {
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Atualizar preferências
        if (isset($input['preferences'])) {
            $prefs = $input['preferences'];
            
            $stmt = $db->prepare("
                INSERT INTO user_preferences (user_id, cuisines, restrictions, newsletter, notifications) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    cuisines = VALUES(cuisines),
                    restrictions = VALUES(restrictions),
                    newsletter = VALUES(newsletter),
                    notifications = VALUES(notifications)
            ");
            $stmt->execute([
                $userId,
                isset($prefs['cuisines']) ? implode(',', $prefs['cuisines']) : '',
                isset($prefs['restrictions']) ? implode(',', $prefs['restrictions']) : '',
                $prefs['newsletter'] ?? 0,
                $prefs['notifications'] ?? 1
            ]);
        }
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'profile_update', 'Perfil atualizado')
        ");
        $stmt->execute([$userId]);
        
        // Buscar dados atualizados
        $stmt = $db->prepare("SELECT id, username, email, user_code, profile_picture, phone, bio, location, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch();
        
        $user['preferences'] = $preferences;
        
        jsonSuccess('Perfil atualizado com sucesso!', ['user' => $user]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao atualizar perfil: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ALTERAR PALAVRA-PASSE
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'change_password') {
    $sessionToken = $input['sessionToken'] ?? '';
    $currentPassword = $input['currentPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';
    
    if (empty($sessionToken)) {
        jsonError('Token de sessão não fornecido.', 401);
    }
    
    if (strlen($newPassword) < 6) {
        jsonError('A nova palavra-passe deve ter pelo menos 6 caracteres.');
    }
    
    try {
        $db = getDB();
        
        // Verificar sessão e buscar utilizador
        $stmt = $db->prepare("
            SELECT u.* FROM users u
            JOIN sessions s ON u.id = s.user_id
            WHERE s.session_token = ? AND (s.expires_at IS NULL OR s.expires_at > NOW())
        ");
        $stmt->execute([$sessionToken]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonError('Sessão inválida ou expirada.', 401);
        }
        
        // Verificar palavra-passe atual
        if (!password_verify($currentPassword, $user['password'])) {
            jsonError('Palavra-passe atual incorreta.');
        }
        
        // Atualizar palavra-passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'password_change', ?)
        ");
        $stmt->execute([$user['id'], "Palavra-passe alterada: {$user['username']}"]);
        
        jsonSuccess('Palavra-passe alterada com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao alterar palavra-passe: ' . $e->getMessage(), 500);
    }
}

// Método não suportado
jsonError('Ação não reconhecida.', 400);
?>
