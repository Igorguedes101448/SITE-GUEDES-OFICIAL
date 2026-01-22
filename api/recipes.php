<?php
// ============================================
// ChefGuedes - API de Receitas
// Gestão completa de receitas
// ============================================

require_once 'db.php';
require_once 'profanity-filter.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// ============================================
// LISTAR TODAS AS RECEITAS
// ============================================
if ($method === 'GET') {
    try {
        $db = getDB();
        
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';
        $drafts = isset($_GET['drafts']) ? $_GET['drafts'] === 'true' : false;
        $private = isset($_GET['private']) ? $_GET['private'] === 'true' : false;
        
        // Se for pedido de rascunhos ou receitas privadas, requer autenticação
        if ($drafts || $private) {
            // Tentar obter o token de autorização de várias formas (compatibilidade com diferentes servidores)
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
            
            // Verificar sessão
            $stmt = $db->prepare("SELECT user_id FROM sessions WHERE session_token = ? AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch();
            
            if (!$session) {
                jsonError('Sessão inválida ou expirada.', 401);
            }
            
            $userId = $session['user_id'];
            
            if ($drafts) {
                // Buscar apenas rascunhos do utilizador
                $sql = "
                    SELECT r.*, u.username as author_name
                    FROM recipes r
                    LEFT JOIN users u ON r.author_id = u.id
                    WHERE r.is_draft = 1 AND r.author_id = ?
                    ORDER BY r.updated_at DESC
                ";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
                $recipes = $stmt->fetchAll();
                
                jsonSuccess('Rascunhos carregados.', ['recipes' => $recipes]);
            } else {
                // Buscar receitas privadas do utilizador (incluindo rascunhos e receitas com visibilidade privada)
                $sql = "
                    SELECT r.*, u.username as author_name
                    FROM recipes r
                    LEFT JOIN users u ON r.author_id = u.id
                    WHERE (r.is_draft = 1 OR r.visibility = 'private') AND r.author_id = ?
                    ORDER BY r.updated_at DESC
                ";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
                $recipes = $stmt->fetchAll();
                
                jsonSuccess('Receitas privadas carregadas.', ['recipes' => $recipes]);
            }
        } else {
            // Listar receitas públicas (não rascunhos e com visibilidade pública)
            $sql = "
                SELECT r.*, u.username as author_name
                FROM recipes r
                LEFT JOIN users u ON r.author_id = u.id
                WHERE r.is_draft = 0 AND r.visibility = 'public'
            ";
            $params = [];
            
            if (!empty($category)) {
                $sql .= " AND r.category = ?";
                $params[] = $category;
            }
            
            // Adicionar filtro de subcategoria
            $subcategory = $_GET['subcategory'] ?? '';
            if (!empty($subcategory)) {
                $sql .= " AND r.subcategory = ?";
                $params[] = $subcategory;
            }
            
            if (!empty($search)) {
                $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.ingredients LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY r.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $recipes = $stmt->fetchAll();
            
            jsonSuccess('Receitas carregadas.', ['recipes' => $recipes]);
        }
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar receitas: ' . $e->getMessage(), 500);
    }
}

// ============================================
// CRIAR RECEITA
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
        
        // Validar conteúdo (filtro de palavras inadequadas)
        $validation = validateRecipeContent([
            'title' => $input['title'] ?? '',
            'description' => $input['description'] ?? '',
            'ingredients' => $input['ingredients'] ?? '',
            'instructions' => $input['instructions'] ?? ''
        ]);
        
        if (!$validation['isValid']) {
            $errorMessages = array_map(function($error) {
                return $error['message'];
            }, $validation['errors']);
            jsonError('Conteúdo inadequado detectado: ' . implode(' ', $errorMessages), 400);
        }
        
        // Criar receita
        $stmt = $db->prepare("
            INSERT INTO recipes (title, category, subcategory, description, ingredients, quantities, instructions, image, prep_time, cook_time, servings, difficulty, author_id, is_draft, visibility) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['title'] ?? '',
            $input['category'] ?? '',
            $input['subcategory'] ?? null,
            $input['description'] ?? '',
            $input['ingredients'] ?? '',
            $input['quantities'] ?? null,
            $input['instructions'] ?? '',
            $input['image'] ?? null,
            $input['prepTime'] ?? null,
            $input['cookTime'] ?? null,
            $input['servings'] ?? null,
            $input['difficulty'] ?? 'Média',
            $userId,
            $input['isDraft'] ?? false,  // Padrão: criar como receita completa
            $input['visibility'] ?? 'public'  // Padrão: visibilidade pública
        ]);
        
        $recipeId = $db->lastInsertId();
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'recipe_create', ?)
        ");
        $stmt->execute([$userId, "Criou receita: {$input['title']}"]);
        
        // Buscar receita criada
        $stmt = $db->prepare("
            SELECT r.*, u.username as author_name
            FROM recipes r
            LEFT JOIN users u ON r.author_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        jsonSuccess('Receita criada com sucesso!', ['recipe' => $recipe]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao criar receita: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ATUALIZAR RECEITA
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'update') {
    $sessionToken = $input['sessionToken'] ?? '';
    $recipeId = $input['recipeId'] ?? 0;
    
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
        
        // Verificar se a receita pertence ao utilizador
        $stmt = $db->prepare("SELECT author_id FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        if (!$recipe || $recipe['author_id'] != $userId) {
            jsonError('Não tem permissão para editar esta receita.', 403);
        }
        
        // Validar conteúdo editado (filtro de palavras inadequadas)
        $contentToValidate = [];
        if (isset($input['title'])) $contentToValidate['title'] = $input['title'];
        if (isset($input['description'])) $contentToValidate['description'] = $input['description'];
        if (isset($input['ingredients'])) $contentToValidate['ingredients'] = $input['ingredients'];
        if (isset($input['instructions'])) $contentToValidate['instructions'] = $input['instructions'];
        
        if (!empty($contentToValidate)) {
            $validation = validateRecipeContent($contentToValidate);
            if (!$validation['isValid']) {
                $errorMessages = array_map(function($error) {
                    return $error['message'];
                }, $validation['errors']);
                jsonError('Conteúdo inadequado detectado: ' . implode(' ', $errorMessages), 400);
            }
        }
        
        // Atualizar receita
        $updates = [];
        $params = [];
        
        if (isset($input['title'])) {
            $updates[] = "title = ?";
            $params[] = $input['title'];
        }
        if (isset($input['category'])) {
            $updates[] = "category = ?";
            $params[] = $input['category'];
        }
        if (isset($input['description'])) {
            $updates[] = "description = ?";
            $params[] = $input['description'];
        }
        if (isset($input['ingredients'])) {
            $updates[] = "ingredients = ?";
            $params[] = $input['ingredients'];
        }
        if (isset($input['instructions'])) {
            $updates[] = "instructions = ?";
            $params[] = $input['instructions'];
        }
        if (isset($input['image'])) {
            $updates[] = "image = ?";
            $params[] = $input['image'];
        }
        if (isset($input['prep_time'])) {
            $updates[] = "prep_time = ?";
            $params[] = $input['prep_time'];
        }
        if (isset($input['cook_time'])) {
            $updates[] = "cook_time = ?";
            $params[] = $input['cook_time'];
        }
        if (isset($input['servings'])) {
            $updates[] = "servings = ?";
            $params[] = $input['servings'];
        }
        if (isset($input['difficulty'])) {
            $updates[] = "difficulty = ?";
            $params[] = $input['difficulty'];
        }
        if (isset($input['subcategory'])) {
            $updates[] = "subcategory = ?";
            $params[] = $input['subcategory'];
        }
        if (isset($input['quantities'])) {
            $updates[] = "quantities = ?";
            $params[] = $input['quantities'];
        }
        if (isset($input['visibility'])) {
            $updates[] = "visibility = ?";
            $params[] = $input['visibility'];
        }
        if (isset($input['isDraft'])) {
            $updates[] = "is_draft = ?";
            $params[] = $input['isDraft'] ? 1 : 0;
        }
        
        if (!empty($updates)) {
            $params[] = $recipeId;
            $sql = "UPDATE recipes SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'recipe_update', ?)
        ");
        $stmt->execute([$userId, "Atualizou receita ID: $recipeId"]);
        
        jsonSuccess('Receita atualizada com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao atualizar receita: ' . $e->getMessage(), 500);
    }
}

// ============================================
// APAGAR RECEITA
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'delete') {
    $sessionToken = $input['sessionToken'] ?? '';
    $recipeId = $input['recipeId'] ?? 0;
    
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
        
        // Verificar se a receita pertence ao utilizador
        $stmt = $db->prepare("SELECT author_id FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        if (!$recipe || $recipe['author_id'] != $userId) {
            jsonError('Não tem permissão para apagar esta receita.', 403);
        }
        
        // Apagar receita
        $stmt = $db->prepare("DELETE FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'recipe_delete', ?)
        ");
        $stmt->execute([$userId, "Apagou receita ID: $recipeId"]);
        
        jsonSuccess('Receita apagada com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao apagar receita: ' . $e->getMessage(), 500);
    }
}

// ============================================
// TOGGLE FAVORITO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'toggle_favorite') {
    $sessionToken = $input['sessionToken'] ?? '';
    $recipeId = $input['recipeId'] ?? 0;
    
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
        
        // Verificar se já é favorito
        $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?");
        $stmt->execute([$userId, $recipeId]);
        $favorite = $stmt->fetch();
        
        if ($favorite) {
            // Remover dos favoritos
            $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?");
            $stmt->execute([$userId, $recipeId]);
            
            jsonSuccess('Removido dos favoritos.', ['isFavorite' => false]);
        } else {
            // Adicionar aos favoritos
            $stmt = $db->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)");
            $stmt->execute([$userId, $recipeId]);
            
            // Registar atividade
            $stmt = $db->prepare("
                INSERT INTO activities (user_id, type, description) 
                VALUES (?, 'favorite', 'Adicionou receita aos favoritos')
            ");
            $stmt->execute([$userId]);
            
            jsonSuccess('Adicionado aos favoritos!', ['isFavorite' => true]);
        }
        
    } catch (PDOException $e) {
        jsonError('Erro ao atualizar favoritos: ' . $e->getMessage(), 500);
    }
}

// ============================================
// PUBLICAR RASCUNHO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'publish_draft') {
    $sessionToken = $input['sessionToken'] ?? '';
    $recipeId = $input['recipeId'] ?? 0;
    
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
        
        // Verificar se a receita é um rascunho e pertence ao utilizador
        $stmt = $db->prepare("SELECT author_id, is_draft FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            jsonError('Receita não encontrada.', 404);
        }
        
        if ($recipe['author_id'] != $userId) {
            jsonError('Não tem permissão para publicar esta receita.', 403);
        }
        
        if (!$recipe['is_draft']) {
            jsonError('Esta receita já está publicada.', 400);
        }
        
        // Publicar rascunho (definir is_draft como false)
        $stmt = $db->prepare("UPDATE recipes SET is_draft = 0 WHERE id = ?");
        $stmt->execute([$recipeId]);
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'recipe_publish', ?)
        ");
        $stmt->execute([$userId, "Publicou receita ID: $recipeId"]);
        
        jsonSuccess('Rascunho publicado com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao publicar rascunho: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ALTERAR VISIBILIDADE DA RECEITA
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'change_visibility') {
    $sessionToken = $input['sessionToken'] ?? '';
    $recipeId = $input['recipeId'] ?? 0;
    $visibility = $input['visibility'] ?? '';
    
    if (empty($sessionToken)) {
        jsonError('Token de sessão não fornecido.', 401);
    }
    
    if (!in_array($visibility, ['public', 'private', 'friends'])) {
        jsonError('Visibilidade inválida. Use: public, private ou friends.', 400);
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
        
        // Verificar se a receita pertence ao utilizador
        $stmt = $db->prepare("SELECT author_id, is_draft FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            jsonError('Receita não encontrada.', 404);
        }
        
        if ($recipe['author_id'] != $userId) {
            jsonError('Não tem permissão para alterar a visibilidade desta receita.', 403);
        }
        
        // Alterar visibilidade (se tornar pública, também marca como não-rascunho)
        if ($visibility === 'public') {
            $stmt = $db->prepare("UPDATE recipes SET visibility = ?, is_draft = 0 WHERE id = ?");
            $stmt->execute([$visibility, $recipeId]);
        } else {
            $stmt = $db->prepare("UPDATE recipes SET visibility = ? WHERE id = ?");
            $stmt->execute([$visibility, $recipeId]);
        }
        
        // Registar atividade
        $stmt = $db->prepare("
            INSERT INTO activities (user_id, type, description) 
            VALUES (?, 'recipe_visibility_change', ?)
        ");
        $visibilityText = $visibility === 'public' ? 'pública' : ($visibility === 'private' ? 'privada' : 'amigos');
        $stmt->execute([$userId, "Alterou visibilidade da receita ID: $recipeId para $visibilityText"]);
        
        jsonSuccess('Visibilidade da receita alterada com sucesso!');
        
    } catch (PDOException $e) {
        jsonError('Erro ao alterar visibilidade: ' . $e->getMessage(), 500);
    }
}

// Método não suportado
jsonError('Ação não reconhecida.', 400);
?>
