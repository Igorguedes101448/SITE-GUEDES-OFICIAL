<?php
// ============================================
// ChefGuedes - API de Grupos
// Gestão completa de grupos
// ============================================

require_once 'db.php';
require_once 'profanity-filter.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// ============================================
// LISTAR TODOS OS GRUPOS
// ============================================
if ($method === 'GET' && !isset($_GET['user_groups'])) {
    try {
        $db = getDB();
        
        $sql = "
            SELECT g.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM `group_members` WHERE group_id = g.id) as member_count
            FROM `groups` g
            LEFT JOIN users u ON g.created_by = u.id
            ORDER BY g.created_at DESC
        ";
        
        $stmt = $db->query($sql);
        $groups = $stmt->fetchAll();
        
        jsonSuccess('Grupos carregados.', ['groups' => $groups]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar grupos: ' . $e->getMessage(), 500);
    }
}

// ============================================
// LISTAR GRUPOS DO UTILIZADOR (onde é membro)
// ============================================
if ($method === 'GET' && isset($_GET['user_groups'])) {
    $sessionToken = $_GET['sessionToken'] ?? '';
    
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
        
        // Buscar todos os grupos onde o utilizador é membro
        $sql = "
            SELECT DISTINCT g.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM `group_members` WHERE group_id = g.id) as member_count,
                   gm.role as user_role
            FROM `groups` g
            LEFT JOIN users u ON g.created_by = u.id
            INNER JOIN `group_members` gm ON g.id = gm.group_id
            WHERE gm.user_id = ?
            ORDER BY g.created_at DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $groups = $stmt->fetchAll();
        
        jsonSuccess('Grupos do utilizador carregados.', ['groups' => $groups]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar grupos do utilizador: ' . $e->getMessage(), 500);
    }
}

// ============================================
// CRIAR GRUPO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'create') {
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
        
        // Validar nome do grupo (filtro de palavras inadequadas)
        $validation = validateGroupName($input['name'] ?? '');
        if (!$validation['isValid']) {
            jsonError('Nome do grupo contém palavras inadequadas.', 400);
        }
        
        // Validar descrição do grupo
        if (!empty($input['description'])) {
            $descCheck = checkProfanity($input['description']);
            if (!$descCheck['isClean']) {
                jsonError('Descrição do grupo contém palavras inadequadas.', 400);
            }
        }
        
        // Criar grupo
        $stmt = $db->prepare("
            INSERT INTO `groups` (name, description, image, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['name'] ?? '',
            $input['description'] ?? '',
            $input['image'] ?? '',
            $userId
        ]);
        
        $groupId = $db->lastInsertId();
        
        // Adicionar criador como membro admin
        $stmt = $db->prepare("
            INSERT INTO `group_members` (group_id, user_id, role) 
            VALUES (?, ?, 'admin')
        ");
        $stmt->execute([$groupId, $userId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'group_create', ?)
        ");
        $stmt->execute([$userId, "Criou grupo: {$input['name']}"]);
        
        // Buscar grupo criado
        $stmt = $db->prepare("
            SELECT g.*, u.username as created_by_name
            FROM `groups` g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.id = ?
        ");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        
        jsonSuccess('Grupo criado com sucesso!', ['group' => $group]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao criar grupo: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ENVIAR CONVITE PARA GRUPO (POR USER_CODE)
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'send_invite') {
    $sessionToken = $input['sessionToken'] ?? '';
    $groupId = $input['groupId'] ?? 0;
    $userCode = strtoupper(trim($input['userCode'] ?? ''));
    
    if (empty($sessionToken)) {
        jsonError('Token de sessão não fornecido.', 401);
    }
    
    if (empty($userCode) || strlen($userCode) !== 6) {
        jsonError('Código de utilizador inválido.', 400);
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
        
        $inviterId = $session['user_id'];
        
        // Verificar se o utilizador é admin do grupo
        $stmt = $db->prepare("SELECT role FROM `group_members` WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $inviterId]);
        $member = $stmt->fetch();
        
        if (!$member || $member['role'] !== 'admin') {
            jsonError('Apenas administradores podem enviar convites.', 403);
        }
        
        // Buscar utilizador pelo código
        $stmt = $db->prepare("SELECT id, username FROM users WHERE user_code = ?");
        $stmt->execute([$userCode]);
        $targetUser = $stmt->fetch();
        
        if (!$targetUser) {
            jsonError('Utilizador não encontrado com este código.', 404);
        }
        
        // Verificar se já é membro
        $stmt = $db->prepare("SELECT id FROM `group_members` WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $targetUser['id']]);
        if ($stmt->fetch()) {
            jsonError('Este utilizador já é membro do grupo.', 400);
        }
        
        // Verificar se já existe convite pendente
        $stmt = $db->prepare("SELECT id, status FROM group_invites WHERE group_id = ? AND invitee_id = ?");
        $stmt->execute([$groupId, $targetUser['id']]);
        $existingInvite = $stmt->fetch();
        
        if ($existingInvite) {
            if ($existingInvite['status'] === 'pending') {
                jsonError('Já existe um convite pendente para este utilizador.', 400);
            } else {
                // Reenviar convite (atualizar status para pending)
                $stmt = $db->prepare("UPDATE group_invites SET status = 'pending', inviter_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$inviterId, $existingInvite['id']]);
            }
        } else {
            // Criar novo convite
            $stmt = $db->prepare("
                INSERT INTO group_invites (group_id, inviter_id, invitee_id, invitee_user_code, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$groupId, $inviterId, $targetUser['id'], $userCode]);
        }
        
        // Buscar informações do grupo
        $stmt = $db->prepare("SELECT name, description FROM `groups` WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        
        // Buscar nome do convidador
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$inviterId]);
        $inviter = $stmt->fetch();
        
        // Criar notificação para o utilizador convidado
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, sender_id, related_id) 
            VALUES (?, 'group_invite', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $targetUser['id'],
            'Convite para Grupo',
            "{$inviter['username']} convidou você para o grupo '{$group['name']}'",
            'grupos.html?tab=invites',
            $inviterId,
            $groupId
        ]);
        
        jsonSuccess('Convite enviado com sucesso!', ['user' => $targetUser]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao enviar convite: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ACEITAR CONVITE DE GRUPO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'accept_invite') {
    $sessionToken = $input['sessionToken'] ?? '';
    $inviteId = $input['inviteId'] ?? 0;
    
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
        
        // Buscar convite
        $stmt = $db->prepare("SELECT * FROM group_invites WHERE id = ? AND invitee_id = ? AND status = 'pending'");
        $stmt->execute([$inviteId, $userId]);
        $invite = $stmt->fetch();
        
        if (!$invite) {
            jsonError('Convite não encontrado ou já respondido.', 404);
        }
        
        // Adicionar como membro do grupo
        $stmt = $db->prepare("
            INSERT INTO `group_members` (group_id, user_id, role) 
            VALUES (?, ?, 'member')
        ");
        $stmt->execute([$invite['group_id'], $userId]);
        
        // Atualizar status do convite
        $stmt = $db->prepare("UPDATE group_invites SET status = 'accepted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$inviteId]);
        
        // Notificar o convidador
        $stmt = $db->prepare("SELECT name FROM `groups` WHERE id = ?");
        $stmt->execute([$invite['group_id']]);
        $group = $stmt->fetch();
        
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, sender_id, related_id) 
            VALUES (?, 'group_accept', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invite['inviter_id'],
            'Convite Aceite',
            "O seu convite para o grupo '{$group['name']}' foi aceite",
            'grupos.html?group=' . $invite['group_id'],
            $userId,
            $invite['group_id']
        ]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'group_join', ?)
        ");
        $stmt->execute([$userId, "Entrou no grupo: {$group['name']}"]);
        
        jsonSuccess('Convite aceite! Bem-vindo ao grupo.', ['groupId' => $invite['group_id']]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao aceitar convite: ' . $e->getMessage(), 500);
    }
}

// ============================================
// RECUSAR CONVITE DE GRUPO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'reject_invite') {
    $sessionToken = $input['sessionToken'] ?? '';
    $inviteId = $input['inviteId'] ?? 0;
    
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
        
        // Buscar convite
        $stmt = $db->prepare("SELECT * FROM group_invites WHERE id = ? AND invitee_id = ? AND status = 'pending'");
        $stmt->execute([$inviteId, $userId]);
        $invite = $stmt->fetch();
        
        if (!$invite) {
            jsonError('Convite não encontrado ou já respondido.', 404);
        }
        
        // Atualizar status do convite
        $stmt = $db->prepare("UPDATE group_invites SET status = 'rejected', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$inviteId]);
        
        // Notificar o convidador
        $stmt = $db->prepare("SELECT name FROM `groups` WHERE id = ?");
        $stmt->execute([$invite['group_id']]);
        $group = $stmt->fetch();
        
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, sender_id, related_id) 
            VALUES (?, 'group_reject', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invite['inviter_id'],
            'Convite Recusado',
            "O convite para o grupo '{$group['name']}' foi recusado",
            'grupos.html',
            $userId,
            $invite['group_id']
        ]);
        
        jsonSuccess('Convite recusado.');
        
    } catch (PDOException $e) {
        jsonError('Erro ao recusar convite: ' . $e->getMessage(), 500);
    }
}

// ============================================
// LISTAR CONVITES PENDENTES
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'get_invites') {
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
        
        // Buscar convites pendentes
        $stmt = $db->prepare("
            SELECT gi.*, g.name as group_name, g.description as group_description,
                   u.username as inviter_name, u.profile_picture as inviter_picture
            FROM group_invites gi
            JOIN `groups` g ON gi.group_id = g.id
            JOIN users u ON gi.inviter_id = u.id
            WHERE gi.invitee_id = ? AND gi.status = 'pending'
            ORDER BY gi.created_at DESC
        ");
        $stmt->execute([$userId]);
        $invites = $stmt->fetchAll();
        
        jsonSuccess('Convites carregados.', ['invites' => $invites]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar convites: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ADICIONAR MEMBRO AO GRUPO (LEGACY - Agora usa convites)
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'add_member') {
    // A ação 'add_member' agora envia um convite em vez de adicionar diretamente
    // Isso garante que o utilizador tenha a opção de aceitar ou recusar
    
    $sessionToken = $input['sessionToken'] ?? '';
    $groupId = $input['groupId'] ?? 0;
    $userCode = strtoupper(trim($input['userCode'] ?? ''));
    
    if (empty($sessionToken)) {
        jsonError('Token de sessão não fornecido.', 401);
    }
    
    if (empty($userCode) || strlen($userCode) !== 6) {
        jsonError('Código de utilizador inválido.', 400);
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
        
        $inviterId = $session['user_id'];
        
        // Verificar se o utilizador é admin do grupo
        $stmt = $db->prepare("SELECT role FROM `group_members` WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $inviterId]);
        $member = $stmt->fetch();
        
        if (!$member || $member['role'] !== 'admin') {
            jsonError('Apenas administradores podem enviar convites.', 403);
        }
        
        // Buscar utilizador pelo código
        $stmt = $db->prepare("SELECT id, username FROM users WHERE user_code = ?");
        $stmt->execute([$userCode]);
        $targetUser = $stmt->fetch();
        
        if (!$targetUser) {
            jsonError('Utilizador não encontrado com este código.', 404);
        }
        
        // Verificar se já é membro
        $stmt = $db->prepare("SELECT id FROM `group_members` WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $targetUser['id']]);
        if ($stmt->fetch()) {
            jsonError('Este utilizador já é membro do grupo.', 400);
        }
        
        // Verificar se já existe convite pendente
        $stmt = $db->prepare("SELECT id, status FROM group_invites WHERE group_id = ? AND invitee_id = ?");
        $stmt->execute([$groupId, $targetUser['id']]);
        $existingInvite = $stmt->fetch();
        
        if ($existingInvite && $existingInvite['status'] === 'pending') {
            jsonError('Já existe um convite pendente para este utilizador.', 400);
        }
        
        // Criar ou atualizar convite
        if ($existingInvite) {
            $stmt = $db->prepare("UPDATE group_invites SET status = 'pending', inviter_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$inviterId, $existingInvite['id']]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO group_invites (group_id, inviter_id, invitee_id, invitee_user_code, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$groupId, $inviterId, $targetUser['id'], $userCode]);
        }
        
        // Buscar informações do grupo e convidador
        $stmt = $db->prepare("SELECT name FROM `groups` WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$inviterId]);
        $inviter = $stmt->fetch();
        
        // Criar notificação
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, sender_id, related_id) 
            VALUES (?, 'group_invite', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $targetUser['id'],
            'Convite para Grupo',
            "{$inviter['username']} convidou você para o grupo '{$group['name']}'",
            'grupos.html?tab=invites',
            $inviterId,
            $groupId
        ]);
        
        jsonSuccess('Convite enviado com sucesso! O utilizador poderá aceitar ou recusar.', ['user' => $targetUser]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao enviar convite: ' . $e->getMessage(), 500);
    }
}

// ============================================
// REMOVER MEMBRO DO GRUPO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'remove_member') {
    $sessionToken = $input['sessionToken'] ?? '';
    $groupId = $input['groupId'] ?? 0;
    $userId = $input['userId'] ?? 0;
    
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
        
        $adminUserId = $session['user_id'];
        
        // Verificar se é admin
        $stmt = $db->prepare("SELECT role FROM `group_members` WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $adminUserId]);
        $member = $stmt->fetch();
        
        if (!$member || $member['role'] !== 'admin') {
            jsonError('Apenas administradores podem remover membros.', 403);
        }
        
        // Não permitir remover admins
        $stmt = $db->prepare("SELECT role FROM `group_members` WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);
        $targetMember = $stmt->fetch();
        
        if ($targetMember && $targetMember['role'] === 'admin') {
            jsonError('Não é possível remover um administrador.', 403);
        }
        
        // Remover membro
        $stmt = $db->prepare("DELETE FROM `group_members` WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);
        
        jsonSuccess('Membro removido com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao remover membro: ' . $e->getMessage(), 500);
    }
}

// ============================================
// LISTAR MEMBROS DO GRUPO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'get_members') {
    $groupId = $input['groupId'] ?? 0;
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT gm.user_id, gm.role, u.username, u.email, u.profile_picture
            FROM `group_members` gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY gm.role DESC, u.username ASC
        ");
        $stmt->execute([$groupId]);
        $members = $stmt->fetchAll();
        
        jsonSuccess('Membros carregados.', ['members' => $members]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar membros: ' . $e->getMessage(), 500);
    }
}

// ============================================
// APAGAR GRUPO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'delete') {
    $sessionToken = $input['sessionToken'] ?? '';
    $groupId = $input['groupId'] ?? 0;
    
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
        
        // Verificar se o utilizador é o criador
        $stmt = $db->prepare("SELECT created_by FROM `groups` WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        
        if (!$group || $group['created_by'] != $userId) {
            jsonError('Não tem permissão para apagar este grupo.', 403);
        }
        
        // Apagar grupo (membros serão apagados automaticamente por CASCADE)
        $stmt = $db->prepare("DELETE FROM `groups` WHERE id = ?");
        $stmt->execute([$groupId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'group_delete', ?)
        ");
        $stmt->execute([$userId, "Apagou grupo ID: $groupId"]);
        
        jsonSuccess('Grupo apagado com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao apagar grupo: ' . $e->getMessage(), 500);
    }
}

// Método não suportado
jsonError('Ação não reconhecida.', 400);
?>
