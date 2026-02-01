<?php
// ============================================
// ChefGuedes - API de Avaliações e Comentários
// Sistema de avaliação por estrelas e comentários nas receitas
// ============================================

require_once 'db.php';
require_once 'profanity-filter.php';

// Headers já definidos em db.php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Função para verificar sessão
function verifySession() {
    $authHeader = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        }
    }
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        jsonError('Token de autorização não fornecido.', 401);
    }
    
    $sessionToken = $matches[1];
    
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id FROM sessions WHERE session_token = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$sessionToken]);
    $session = $stmt->fetch();
    
    if (!$session) {
        jsonError('Sessão inválida ou expirada.', 401);
    }
    
    return $session['user_id'];
}

// Função para criar notificação de infração
function createInfractionNotification($userId, $infractionDetails) {
    $db = getDB();
    
    // Registrar infração
    $stmt = $db->prepare("INSERT INTO user_infractions (user_id, infraction_type, infraction_details) VALUES (?, 'profanity_comment', ?)");
    $stmt->execute([$userId, $infractionDetails]);
    
    // Contar infrações do utilizador
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM user_infractions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $infractions = $stmt->fetch()['total'];
    
    // Criar notificação de aviso
    $message = "O seu comentário foi rejeitado por conter linguagem inadequada: \"" . $infractionDetails . "\". ";
    if ($infractions >= 2) {
        $message .= "ATENÇÃO: Esta é a sua " . $infractions . "ª infração. Mais violações resultarão no banimento permanente da sua conta!";
    } else {
        $message .= "Por favor, mantenha o respeito. Futuras violações resultarão no banimento da sua conta.";
    }
    
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, type, title, message, link) 
        VALUES (?, 'warning', 'Aviso de Infração', ?, NULL)
    ");
    $stmt->execute([$userId, $message]);
}

// ============================================
// OBTER AVALIAÇÕES E COMENTÁRIOS DE UMA RECEITA
// ============================================
if ($method === 'GET' && isset($_GET['recipe_id'])) {
    $recipeId = $_GET['recipe_id'];
    
    try {
        $db = getDB();
        
        // Verificar se a receita existe e obter o autor
        $stmt = $db->prepare("SELECT id, author_id FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        if (!$recipe) {
            jsonError('Receita não encontrada.', 404);
        }
        
        // Obter estatísticas de avaliações
        $stmt = $db->prepare("
            SELECT 
                AVG(rating) as average_rating,
                COUNT(*) as total_ratings,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM recipe_ratings 
            WHERE recipe_id = ?
        ");
        $stmt->execute([$recipeId]);
        $stats = $stmt->fetch();
        
        // Obter comentários com informações do utilizador
        $stmt = $db->prepare("
            SELECT 
                c.id,
                c.comment,
                c.created_at,
                c.updated_at,
                u.id as user_id,
                u.username,
                u.profile_picture,
                r.rating
            FROM recipe_comments c
            INNER JOIN users u ON c.user_id = u.id
            LEFT JOIN recipe_ratings r ON r.recipe_id = c.recipe_id AND r.user_id = c.user_id
            WHERE c.recipe_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$recipeId]);
        $comments = $stmt->fetchAll();
        
        // Se houver utilizador logado, obter a sua avaliação
        $userRating = null;
        $userCommentCount = 0;
        $userId = null;
        
        // Tentar obter token (opcional, não obrigatório para visualização)
        $authHeader = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
            }
        }
        
        // Se tiver token, verificar sessão
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            try {
                $sessionToken = $matches[1];
                $stmt = $db->prepare("SELECT user_id FROM sessions WHERE session_token = ? AND (expires_at IS NULL OR expires_at > NOW())");
                $stmt->execute([$sessionToken]);
                $session = $stmt->fetch();
                
                if ($session) {
                    $userId = $session['user_id'];
                    
                    $stmt = $db->prepare("SELECT rating FROM recipe_ratings WHERE recipe_id = ? AND user_id = ?");
                    $stmt->execute([$recipeId, $userId]);
                    $userRatingData = $stmt->fetch();
                    $userRating = $userRatingData ? (int)$userRatingData['rating'] : null;
                    
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM recipe_comments WHERE recipe_id = ? AND user_id = ?");
                    $stmt->execute([$recipeId, $userId]);
                    $userCommentCount = (int)$stmt->fetch()['count'];
                }
            } catch (Exception $e) {
                // Ignorar erros de autenticação, permitir visualização sem login
            }
        }
        
        jsonSuccess('Avaliações e comentários carregados.', [
            'stats' => [
                'average_rating' => round((float)$stats['average_rating'], 2),
                'total_ratings' => (int)$stats['total_ratings'],
                'five_stars' => (int)$stats['five_stars'],
                'four_stars' => (int)$stats['four_stars'],
                'three_stars' => (int)$stats['three_stars'],
                'two_stars' => (int)$stats['two_stars'],
                'one_star' => (int)$stats['one_star']
            ],
            'comments' => $comments,
            'user_rating' => $userRating,
            'user_comment_count' => $userCommentCount,
            'recipe_author_id' => (int)$recipe['author_id'],
            'current_user_id' => $userId
        ]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar avaliações: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ADICIONAR OU ATUALIZAR AVALIAÇÃO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'rate') {
    $userId = verifySession();
    
    if (!isset($input['recipe_id']) || !isset($input['rating'])) {
        jsonError('Dados incompletos.');
    }
    
    $recipeId = (int)$input['recipe_id'];
    $rating = (int)$input['rating'];
    
    if ($rating < 1 || $rating > 5) {
        jsonError('A avaliação deve ser entre 1 e 5 estrelas.');
    }
    
    try {
        $db = getDB();
        
        // Verificar se a receita existe
        $stmt = $db->prepare("SELECT id FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        if (!$stmt->fetch()) {
            jsonError('Receita não encontrada.', 404);
        }
        
        // Verificar se já avaliou (atualizar) ou inserir nova avaliação
        $stmt = $db->prepare("SELECT id FROM recipe_ratings WHERE recipe_id = ? AND user_id = ?");
        $stmt->execute([$recipeId, $userId]);
        $existingRating = $stmt->fetch();
        
        if ($existingRating) {
            // Atualizar avaliação existente
            $stmt = $db->prepare("UPDATE recipe_ratings SET rating = ?, updated_at = NOW() WHERE recipe_id = ? AND user_id = ?");
            $stmt->execute([$rating, $recipeId, $userId]);
            $message = 'Avaliação atualizada com sucesso!';
        } else {
            // Inserir nova avaliação
            $stmt = $db->prepare("INSERT INTO recipe_ratings (recipe_id, user_id, rating) VALUES (?, ?, ?)");
            $stmt->execute([$recipeId, $userId, $rating]);
            $message = 'Avaliação registada com sucesso!';
            
            // Criar notificação para o autor da receita
            $stmt = $db->prepare("SELECT author_id, title FROM recipes WHERE id = ?");
            $stmt->execute([$recipeId]);
            $recipe = $stmt->fetch();
            
            if ($recipe && $recipe['author_id'] != $userId) {
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link, sender_id) 
                    VALUES (?, 'rating', 'Nova Avaliação', ?, ?, ?)
                ");
                $stmt->execute([
                    $recipe['author_id'],
                    'A sua receita "' . $recipe['title'] . '" recebeu uma avaliação de ' . $rating . ' estrelas!',
                    '/pages/explorar-receitas.html?recipe=' . $recipeId,
                    $userId
                ]);
            }
        }
        
        jsonSuccess($message);
        
    } catch (PDOException $e) {
        jsonError('Erro ao registar avaliação: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ADICIONAR COMENTÁRIO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'comment') {
    $userId = verifySession();
    
    if (!isset($input['recipe_id']) || !isset($input['comment'])) {
        jsonError('Dados incompletos.');
    }
    
    $recipeId = (int)$input['recipe_id'];
    $comment = trim($input['comment']);
    
    if (empty($comment)) {
        jsonError('O comentário não pode estar vazio.');
    }
    
    if (strlen($comment) < 3) {
        jsonError('O comentário deve ter pelo menos 3 caracteres.');
    }
    
    if (strlen($comment) > 1000) {
        jsonError('O comentário não pode ter mais de 1000 caracteres.');
    }
    
    try {
        $db = getDB();
        
        // Verificar se a receita existe
        $stmt = $db->prepare("SELECT id, author_id, title FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            jsonError('Receita não encontrada.', 404);
        }
                // Impedir que o autor comente a própria receita
        if ($recipe['author_id'] == $userId) {
            jsonError('Não pode comentar a sua própria receita.', 403);
        }
                // Impedir que o autor comente a própria receita
        if ($recipe['author_id'] == $userId) {
            jsonError('Não pode comentar a sua própria receita.', 403);
        }
        
        // Verificar quantos comentários o utilizador já fez nesta receita
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM recipe_comments WHERE recipe_id = ? AND user_id = ?");
        $stmt->execute([$recipeId, $userId]);
        $commentCount = (int)$stmt->fetch()['count'];
        
        if ($commentCount >= 2) {
            jsonError('Já atingiu o limite de 2 comentários nesta receita.');
        }
        
        // Verificar profanidade
        $profanityCheck = checkProfanity($comment);
        
        if (!$profanityCheck['isClean']) {
            // Registrar infração e criar notificação
            $foundWords = implode(', ', $profanityCheck['foundWords']);
            createInfractionNotification($userId, $foundWords);
            
            jsonError('O seu comentário contém linguagem inadequada e foi rejeitado. Uma notificação de aviso foi enviada. Futuras violações resultarão no banimento da sua conta.', 403);
        }
        
        // Inserir comentário
        $stmt = $db->prepare("INSERT INTO recipe_comments (recipe_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$recipeId, $userId, $comment]);
        
        // Criar notificação para o autor da receita (se não for o próprio)
        if ($recipe['author_id'] != $userId) {
            $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $commenter = $stmt->fetch();
            
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, sender_id) 
                VALUES (?, 'comment', 'Novo Comentário', ?, ?, ?)
            ");
            $stmt->execute([
                $recipe['author_id'],
                $commenter['username'] . ' comentou na sua receita "' . $recipe['title'] . '"',
                '/pages/explorar-receitas.html?recipe=' . $recipeId,
                $userId
            ]);
        }
        
        jsonSuccess('Comentário adicionado com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao adicionar comentário: ' . $e->getMessage(), 500);
    }
}

// ============================================
// DELETAR COMENTÁRIO (apenas o próprio utilizador ou admin)
// ============================================
if ($method === 'DELETE' || ($method === 'POST' && isset($input['action']) && $input['action'] === 'delete_comment')) {
    $userId = verifySession();
    
    $commentId = $method === 'DELETE' ? $_GET['comment_id'] : $input['comment_id'];
    
    if (!$commentId) {
        jsonError('ID do comentário não fornecido.');
    }
    
    try {
        $db = getDB();
        
        // Verificar se o comentário pertence ao utilizador ou se é admin
        $stmt = $db->prepare("SELECT user_id FROM recipe_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            jsonError('Comentário não encontrado.', 404);
        }
        
        // Verificar se é admin
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $isAdmin = ($user && $user['role'] === 'admin');
        
        if ($comment['user_id'] != $userId && !$isAdmin) {
            jsonError('Não tem permissão para deletar este comentário.', 403);
        }
        
        // Deletar comentário
        $stmt = $db->prepare("DELETE FROM recipe_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        
        jsonSuccess('Comentário removido com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao remover comentário: ' . $e->getMessage(), 500);
    }
}

// ============================================
// OBTER INFRAÇÕES DO UTILIZADOR
// ============================================
if ($method === 'GET' && isset($_GET['user_infractions'])) {
    $userId = verifySession();
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM user_infractions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $infractions = $stmt->fetchAll();
        
        jsonSuccess('Infrações carregadas.', ['infractions' => $infractions]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar infrações: ' . $e->getMessage(), 500);
    }
}

// Se chegou aqui, método não reconhecido
jsonError('Método ou ação não reconhecida.', 400);
