<?php
// ============================================
// ChefGuedes - API de Administração
// Gestão de utilizadores e receitas (apenas admins)
// ============================================

require_once 'db.php';
session_start();

// Verificar se é admin
function isAdmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        return $user && $user['is_admin'] == 1;
    } catch (PDOException $e) {
        return false;
    }
}

// Função para retornar erro JSON
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Função para retornar sucesso JSON
function jsonSuccess($data = [], $message = '') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

// Verificar autenticação e permissão de admin
if (!isAdmin()) {
    jsonError('Acesso negado. Apenas administradores podem aceder a esta API.', 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = getDB();

    // ============================================
    // OBTER ESTATÍSTICAS
    // ============================================
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stats') {
        // Total de utilizadores
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
        $totalUsers = $stmt->fetch()['total'];
        
        // Utilizadores banidos
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE banned = 1");
        $bannedUsers = $stmt->fetch()['total'];
        
        // Utilizadores suspensos
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE suspended_until IS NOT NULL AND suspended_until > NOW()");
        $suspendedUsers = $stmt->fetch()['total'];
        
        // Total de receitas
        $stmt = $db->query("SELECT COUNT(*) as total FROM recipes");
        $totalRecipes = $stmt->fetch()['total'];
        
        // Receitas públicas
        $stmt = $db->query("SELECT COUNT(*) as total FROM recipes WHERE visibility = 'public'");
        $publicRecipes = $stmt->fetch()['total'];
        
        // Ações recentes
        $stmt = $db->query("SELECT COUNT(*) as total FROM admin_actions WHERE DATE(created_at) = CURDATE()");
        $todayActions = $stmt->fetch()['total'];
        
        jsonSuccess([
            'totalUsers' => $totalUsers,
            'bannedUsers' => $bannedUsers,
            'suspendedUsers' => $suspendedUsers,
            'totalRecipes' => $totalRecipes,
            'publicRecipes' => $publicRecipes,
            'todayActions' => $todayActions
        ]);
    }

    // ============================================
    // LISTAR TODOS OS UTILIZADORES
    // ============================================
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'users') {
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT id, username, email, created_at, banned, banned_reason, 
                       suspended_until, warning_count, is_admin 
                FROM users 
                WHERE is_admin = 0";
        
        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $stmt = $db->prepare($sql . " ORDER BY created_at DESC");
            $searchParam = '%' . $search . '%';
            $stmt->execute([$searchParam, $searchParam]);
        } else {
            $stmt = $db->prepare($sql . " ORDER BY created_at DESC");
            $stmt->execute();
        }
        
        $users = $stmt->fetchAll();
        
        // Adicionar status a cada utilizador
        foreach ($users as &$user) {
            if ($user['banned']) {
                $user['status'] = 'banned';
            } elseif ($user['suspended_until'] && strtotime($user['suspended_until']) > time()) {
                $user['status'] = 'suspended';
            } else {
                $user['status'] = 'active';
            }
        }
        
        jsonSuccess($users);
    }

    // ============================================
    // LISTAR TODAS AS RECEITAS
    // ============================================
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'recipes') {
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT r.id, r.title, r.category, r.visibility, r.created_at, r.image,
                       u.username as author_username, u.id as author_id
                FROM recipes r
                LEFT JOIN users u ON r.author_id = u.id";
        
        if ($search) {
            $sql .= " WHERE r.title LIKE ? OR r.category LIKE ?";
            $stmt = $db->prepare($sql . " ORDER BY r.created_at DESC");
            $searchParam = '%' . $search . '%';
            $stmt->execute([$searchParam, $searchParam]);
        } else {
            $stmt = $db->prepare($sql . " ORDER BY r.created_at DESC");
            $stmt->execute();
        }
        
        $recipes = $stmt->fetchAll();
        jsonSuccess($recipes);
    }

    // ============================================
    // BANIR UTILIZADOR
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'ban_user') {
        $userId = $input['user_id'] ?? null;
        $reason = $input['reason'] ?? 'Sem motivo especificado';
        
        if (!$userId) {
            jsonError('ID do utilizador não fornecido.');
        }
        
        // Verificar se não é admin
        $stmt = $db->prepare("SELECT username, is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonError('Utilizador não encontrado.');
        }
        
        if ($user['is_admin']) {
            jsonError('Não é possível banir um administrador.');
        }
        
        // Banir utilizador
        $stmt = $db->prepare("UPDATE users SET banned = 1, banned_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $userId]);
        
        // Registar ação
        $stmt = $db->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_user_id, reason)
            VALUES (?, 'ban', ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $userId, $reason]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description)
            VALUES (?, 'user_banned', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], "Utilizador {$user['username']} foi banido. Motivo: $reason"]);
        
        jsonSuccess([], 'Utilizador banido com sucesso.');
    }

    // ============================================
    // DESBANIR UTILIZADOR
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'unban_user') {
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            jsonError('ID do utilizador não fornecido.');
        }
        
        // Obter nome do utilizador
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonError('Utilizador não encontrado.');
        }
        
        // Desbanir utilizador
        $stmt = $db->prepare("UPDATE users SET banned = 0, banned_reason = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Registar ação
        $stmt = $db->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_user_id, reason)
            VALUES (?, 'unban', ?, 'Banimento removido')
        ");
        $stmt->execute([$_SESSION['user_id'], $userId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description)
            VALUES (?, 'user_unbanned', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], "Utilizador {$user['username']} foi desbanido"]);
        
        jsonSuccess([], 'Utilizador desbanido com sucesso.');
    }

    // ============================================
    // SUSPENDER UTILIZADOR (temporário)
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'suspend_user') {
        $userId = $input['user_id'] ?? null;
        $days = $input['days'] ?? 7;
        $reason = $input['reason'] ?? 'Sem motivo especificado';
        
        if (!$userId) {
            jsonError('ID do utilizador não fornecido.');
        }
        
        // Verificar se não é admin
        $stmt = $db->prepare("SELECT username, is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonError('Utilizador não encontrado.');
        }
        
        if ($user['is_admin']) {
            jsonError('Não é possível suspender um administrador.');
        }
        
        // Calcular data de suspensão
        $suspendUntil = date('Y-m-d H:i:s', strtotime("+$days days"));
        
        // Suspender utilizador
        $stmt = $db->prepare("UPDATE users SET suspended_until = ? WHERE id = ?");
        $stmt->execute([$suspendUntil, $userId]);
        
        // Registar ação
        $stmt = $db->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_user_id, reason, details)
            VALUES (?, 'suspend', ?, ?, ?)
        ");
        $details = json_encode(['days' => $days, 'suspended_until' => $suspendUntil]);
        $stmt->execute([$_SESSION['user_id'], $userId, $reason, $details]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description)
            VALUES (?, 'user_suspended', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], "Utilizador {$user['username']} suspenso por $days dias. Motivo: $reason"]);
        
        jsonSuccess(['suspended_until' => $suspendUntil], "Utilizador suspenso por $days dias.");
    }

    // ============================================
    // REMOVER SUSPENSÃO
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'unsuspend_user') {
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            jsonError('ID do utilizador não fornecido.');
        }
        
        // Obter nome do utilizador
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonError('Utilizador não encontrado.');
        }
        
        // Remover suspensão
        $stmt = $db->prepare("UPDATE users SET suspended_until = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Registar ação
        $stmt = $db->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_user_id, reason)
            VALUES (?, 'unsuspend', ?, 'Suspensão removida')
        ");
        $stmt->execute([$_SESSION['user_id'], $userId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description)
            VALUES (?, 'user_unsuspended', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], "Suspensão de {$user['username']} removida"]);
        
        jsonSuccess([], 'Suspensão removida com sucesso.');
    }

    // ============================================
    // DAR AVISO AO UTILIZADOR
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'warn_user') {
        $userId = $input['user_id'] ?? null;
        $reason = $input['reason'] ?? 'Sem motivo especificado';
        
        if (!$userId) {
            jsonError('ID do utilizador não fornecido.');
        }
        
        // Verificar se não é admin
        $stmt = $db->prepare("SELECT username, is_admin, warning_count FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonError('Utilizador não encontrado.');
        }
        
        if ($user['is_admin']) {
            jsonError('Não é possível dar aviso a um administrador.');
        }
        
        // Incrementar contador de avisos
        $newWarningCount = ($user['warning_count'] ?? 0) + 1;
        $stmt = $db->prepare("UPDATE users SET warning_count = ? WHERE id = ?");
        $stmt->execute([$newWarningCount, $userId]);
        
        // Registar ação
        $stmt = $db->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_user_id, reason, details)
            VALUES (?, 'warn', ?, ?, ?)
        ");
        $details = json_encode(['warning_count' => $newWarningCount]);
        $stmt->execute([$_SESSION['user_id'], $userId, $reason, $details]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description)
            VALUES (?, 'user_warned', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], "Aviso dado a {$user['username']} ($newWarningCount avisos). Motivo: $reason"]);
        
        jsonSuccess(['warning_count' => $newWarningCount], "Aviso registado. Total de avisos: $newWarningCount");
    }

    // ============================================
    // APAGAR UTILIZADOR
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'delete_user') {
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            jsonError('ID do utilizador não fornecido.');
        }
        
        // Verificar se não é admin
        $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonError('Utilizador não encontrado.');
        }
        
        if ($user['is_admin']) {
            jsonError('Não é possível apagar um administrador.');
        }
        
        // Apagar utilizador (CASCADE irá apagar suas receitas, favoritos, etc)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        jsonSuccess([], 'Utilizador apagado com sucesso.');
    }

    // ============================================
    // APAGAR RECEITA
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'delete_recipe') {
        $recipeId = $input['recipe_id'] ?? null;
        $reason = $input['reason'] ?? 'Sem motivo especificado';
        
        if (!$recipeId) {
            jsonError('ID da receita não fornecido.');
        }
        
        // Verificar se receita existe e obter detalhes
        $stmt = $db->prepare("SELECT r.id, r.title, u.username as author FROM recipes r LEFT JOIN users u ON r.author_id = u.id WHERE r.id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            jsonError('Receita não encontrada.');
        }
        
        // Apagar receita
        $stmt = $db->prepare("DELETE FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        
        // Registar ação
        $stmt = $db->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_recipe_id, reason, details)
            VALUES (?, 'delete_recipe', ?, ?, ?)
        ");
        $details = json_encode(['recipe_title' => $recipe['title'], 'recipe_author' => $recipe['author']]);
        $stmt->execute([$_SESSION['user_id'], $recipeId, $reason, $details]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description)
            VALUES (?, 'recipe_deleted_admin', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], "Receita '{$recipe['title']}' de {$recipe['author']} foi apagada. Motivo: $reason"]);
        
        jsonSuccess([], 'Receita apagada com sucesso.');
    }

    // ============================================
    // OBTER LOGS DE AÇÕES DO ADMIN
    // ============================================
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logs') {
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        
        $stmt = $db->prepare("
            SELECT 
                aa.*,
                u1.username as admin_username,
                u2.username as target_username
            FROM admin_actions aa
            LEFT JOIN users u1 ON aa.admin_id = u1.id
            LEFT JOIN users u2 ON aa.target_user_id = u2.id
            ORDER BY aa.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $logs = $stmt->fetchAll();
        
        jsonSuccess($logs);
    }

    // ============================================
    // ALTERAR VISIBILIDADE DA RECEITA
    // ============================================
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'change_recipe_visibility') {
        $recipeId = $input['recipe_id'] ?? null;
        $visibility = $input['visibility'] ?? 'private';
        
        if (!$recipeId) {
            jsonError('ID da receita não fornecido.');
        }
        
        if (!in_array($visibility, ['public', 'private', 'friends'])) {
            jsonError('Visibilidade inválida.');
        }
        
        // Alterar visibilidade
        $stmt = $db->prepare("UPDATE recipes SET visibility = ? WHERE id = ?");
        $stmt->execute([$visibility, $recipeId]);
        
        jsonSuccess([], 'Visibilidade alterada com sucesso.');
    }

    // ============================================
    // VERIFICAR SE É ADMIN (para validação)
    // ============================================
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check_admin') {
        jsonSuccess(['is_admin' => true]);
    }

    // Ação não encontrada
    jsonError('Ação não reconhecida.', 404);

} catch (PDOException $e) {
    jsonError('Erro no servidor: ' . $e->getMessage(), 500);
}
?>
