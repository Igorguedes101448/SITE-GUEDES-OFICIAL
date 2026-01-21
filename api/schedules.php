<?php
// ============================================
// ChefGuedes - API de Agendamento
// Gestão de agendamentos de receitas
// ============================================

require_once 'db.php';

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
// LISTAR AGENDAMENTOS
// ============================================
if ($method === 'GET') {
    $sessionToken = $_GET['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $startDate = $_GET['start_date'] ?? date('Y-m-d');
    $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $groupId = $_GET['group_id'] ?? null;
    
    try {
        $db = getDB();
        
        // Se group_id for fornecido, buscar agendamentos do grupo
        if ($groupId) {
            // Verificar se o utilizador é membro do grupo
            $stmt = $db->prepare("SELECT id FROM `group_members` WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            if (!$stmt->fetch()) {
                jsonError('Não tem permissão para ver agendamentos deste grupo.', 403);
            }
            
            $sql = "
                SELECT s.*, r.title as recipe_title, r.image as recipe_image,
                       u.username as created_by_name
                FROM schedules s
                LEFT JOIN recipes r ON s.recipe_id = r.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.group_id = ? AND s.scheduled_date BETWEEN ? AND ?
                ORDER BY s.scheduled_date ASC, s.scheduled_time ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([$groupId, $startDate, $endDate]);
        } else {
            // Buscar agendamentos pessoais do utilizador
            $sql = "
                SELECT s.*, r.title as recipe_title, r.image as recipe_image
                FROM schedules s
                LEFT JOIN recipes r ON s.recipe_id = r.id
                WHERE s.user_id = ? AND s.group_id IS NULL AND s.scheduled_date BETWEEN ? AND ?
                ORDER BY s.scheduled_date ASC, s.scheduled_time ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $startDate, $endDate]);
        }
        
        $schedules = $stmt->fetchAll();
        
        jsonSuccess('Agendamentos carregados.', ['schedules' => $schedules]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar agendamentos: ' . $e->getMessage(), 500);
    }
}

// ============================================
// CRIAR AGENDAMENTO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'create') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $groupId = $input['groupId'] ?? null;
    $recipeId = $input['recipeId'] ?? null;
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $mealType = $input['mealType'] ?? 'Almoço';
    $scheduledDate = $input['scheduledDate'] ?? '';
    $scheduledTime = $input['scheduledTime'] ?? null;
    
    if (empty($title) || empty($scheduledDate)) {
        jsonError('Título e data são obrigatórios.', 400);
    }
    
    try {
        $db = getDB();
        
        // Se for para um grupo, verificar se é membro
        if ($groupId) {
            $stmt = $db->prepare("SELECT id FROM `group_members` WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            if (!$stmt->fetch()) {
                jsonError('Não tem permissão para criar agendamentos neste grupo.', 403);
            }
        }
        
        // Criar agendamento
        $stmt = $db->prepare("
            INSERT INTO schedules (user_id, group_id, recipe_id, title, description, meal_type, scheduled_date, scheduled_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $groupId,
            $recipeId,
            $title,
            $description,
            $mealType,
            $scheduledDate,
            $scheduledTime
        ]);
        
        $scheduleId = $db->lastInsertId();
        
        // Se for agendamento de grupo, notificar os membros
        if ($groupId) {
            $stmt = $db->prepare("SELECT name FROM `groups` WHERE id = ?");
            $stmt->execute([$groupId]);
            $group = $stmt->fetch();
            
            $stmt = $db->prepare("SELECT user_id FROM `group_members` WHERE group_id = ? AND user_id != ?");
            $stmt->execute([$groupId, $userId]);
            $members = $stmt->fetchAll();
            
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, sender_id, related_id) 
                VALUES (?, 'schedule_reminder', ?, ?, ?, ?, ?)
            ");
            
            foreach ($members as $member) {
                $notifStmt->execute([
                    $member['user_id'],
                    'Nova Refeição Agendada',
                    "Refeição '$title' foi agendada no grupo '{$group['name']}' para " . date('d/m/Y', strtotime($scheduledDate)),
                    'grupos.html?group=' . $groupId,
                    $userId,
                    $scheduleId
                ]);
            }
        }
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'schedule_create', ?)
        ");
        $activityDesc = $groupId ? "Agendou refeição: $title (Grupo)" : "Agendou refeição: $title";
        $stmt->execute([$userId, $activityDesc]);
        
        jsonSuccess('Agendamento criado com sucesso!', ['scheduleId' => $scheduleId]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao criar agendamento: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ATUALIZAR AGENDAMENTO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'update') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $scheduleId = $input['scheduleId'] ?? 0;
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $mealType = $input['mealType'] ?? 'Almoço';
    $scheduledDate = $input['scheduledDate'] ?? '';
    $scheduledTime = $input['scheduledTime'] ?? null;
    $recipeId = $input['recipeId'] ?? null;
    
    try {
        $db = getDB();
        
        // Verificar se o agendamento pertence ao utilizador
        $stmt = $db->prepare("SELECT user_id, group_id FROM schedules WHERE id = ?");
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch();
        
        if (!$schedule) {
            jsonError('Agendamento não encontrado.', 404);
        }
        
        if ($schedule['user_id'] != $userId) {
            jsonError('Não tem permissão para editar este agendamento.', 403);
        }
        
        // Atualizar agendamento
        $stmt = $db->prepare("
            UPDATE schedules 
            SET title = ?, description = ?, meal_type = ?, scheduled_date = ?, scheduled_time = ?, recipe_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $mealType, $scheduledDate, $scheduledTime, $recipeId, $scheduleId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'schedule_update', ?)
        ");
        $stmt->execute([$userId, "Atualizou agendamento: $title"]);
        
        jsonSuccess('Agendamento atualizado com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao atualizar agendamento: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ELIMINAR AGENDAMENTO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'delete') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $scheduleId = $input['scheduleId'] ?? 0;
    
    try {
        $db = getDB();
        
        // Verificar permissões
        $stmt = $db->prepare("SELECT user_id FROM schedules WHERE id = ?");
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch();
        
        if (!$schedule) {
            jsonError('Agendamento não encontrado.', 404);
        }
        
        if ($schedule['user_id'] != $userId) {
            jsonError('Não tem permissão para eliminar este agendamento.', 403);
        }
        
        // Eliminar
        $stmt = $db->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->execute([$scheduleId]);
        
        jsonSuccess('Agendamento eliminado com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao eliminar agendamento: ' . $e->getMessage(), 500);
    }
}

// Método não suportado
jsonError('Ação não reconhecida.', 400);
?>
