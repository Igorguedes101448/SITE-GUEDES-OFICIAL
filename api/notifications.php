<?php
// ============================================
// ChefGuedes - API de Notificações
// Gestão de notificações de utilizadores
// ============================================

require_once 'db.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Verificar sessão
function verifySession($sessionToken) {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id FROM sessions WHERE session_token = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$sessionToken]);
    $session = $stmt->fetch();
    
    if (!$session) {
        jsonError('Sessão inválida ou expirada.', 401);
    }
    
    return $session['user_id'];
}

// ============================================
// LISTAR NOTIFICAÇÕES
// ============================================
if ($method === 'GET') {
    $sessionToken = $_GET['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT n.*, u.username as sender_name, u.profile_picture as sender_picture
            FROM notifications n
            LEFT JOIN users u ON n.sender_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
        
        // Contar não lidas
        $stmt = $db->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unread = $stmt->fetch()['unread'];
        
        jsonSuccess('Notificações carregadas.', [
            'notifications' => $notifications,
            'unread' => $unread
        ]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar notificações: ' . $e->getMessage(), 500);
    }
}

// ============================================
// CRIAR NOTIFICAÇÃO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'create') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, sender_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['targetUserId'],
            $input['type'],
            $input['title'],
            $input['message'],
            $input['link'] ?? null,
            $userId
        ]);
        
        jsonSuccess('Notificação criada com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao criar notificação: ' . $e->getMessage(), 500);
    }
}

// ============================================
// MARCAR COMO LIDA
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'mark_read') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    try {
        $db = getDB();
        
        $notificationId = $input['notificationId'] ?? null;
        
        if ($notificationId) {
            // Marcar uma notificação específica
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
        } else {
            // Marcar todas como lidas
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        
        jsonSuccess('Notificação(ões) marcada(s) como lida(s).');
        
    } catch (PDOException $e) {
        jsonError('Erro ao marcar notificação: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ELIMINAR NOTIFICAÇÃO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'delete') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['notificationId'], $userId]);
        
        jsonSuccess('Notificação eliminada.');
        
    } catch (PDOException $e) {
        jsonError('Erro ao eliminar notificação: ' . $e->getMessage(), 500);
    }
}
