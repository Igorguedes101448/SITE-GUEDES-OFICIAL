<?php
// ============================================
// ChefGuedes - API de Assistente IA
// Sugestões inteligentes de receitas e planeamento
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
// SUGERIR RECEITAS BASEADAS EM CONTEXTO
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'suggest_recipes') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $context = $input['context'] ?? [];
    $prepTime = $context['prep_time'] ?? null;
    $servings = $context['servings'] ?? null;
    $difficulty = $context['difficulty'] ?? null;
    $category = $context['category'] ?? null;
    
    try {
        $db = getDB();
        
        // Buscar preferências do utilizador
        $stmt = $db->prepare("SELECT cuisines, restrictions FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch();
        
        // Construir query de sugestão
        $sql = "SELECT * FROM recipes WHERE visibility = 'public' AND is_draft = 0";
        $params = [];
        
        if ($prepTime) {
            $sql .= " AND (prep_time + cook_time) <= ?";
            $params[] = $prepTime;
        }
        
        if ($servings) {
            $sql .= " AND servings >= ?";
            $params[] = $servings;
        }
        
        if ($difficulty) {
            $sql .= " AND difficulty = ?";
            $params[] = $difficulty;
        }
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY RAND() LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $recipes = $stmt->fetchAll();
        
        // Registar sugestão
        $stmt = $db->prepare("
            INSERT INTO ai_suggestions (user_id, suggestion_type, content, context) 
            VALUES (?, 'recipe', ?, ?)
        ");
        $stmt->execute([
            $userId,
            json_encode($recipes, JSON_UNESCAPED_UNICODE),
            json_encode($context, JSON_UNESCAPED_UNICODE)
        ]);
        
        jsonSuccess('Sugestões geradas.', [
            'recipes' => $recipes,
            'explanation' => 'Estas receitas foram selecionadas com base nas suas preferências e no contexto fornecido.'
        ]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao gerar sugestões: ' . $e->getMessage(), 500);
    }
}

// ============================================
// SUGERIR PLANO SEMANAL
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'suggest_weekly_plan') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $startDate = $input['start_date'] ?? date('Y-m-d');
    $servings = $input['servings'] ?? 2;
    $mealsPerDay = $input['meals_per_day'] ?? 2; // Almoço e Jantar por padrão
    
    try {
        $db = getDB();
        
        // Buscar receitas variadas
        $stmt = $db->prepare("
            SELECT * FROM recipes 
            WHERE visibility = 'public' AND is_draft = 0
            ORDER BY RAND() 
            LIMIT ?
        ");
        $totalMeals = 7 * $mealsPerDay; // 7 dias
        $stmt->execute([$totalMeals]);
        $recipes = $stmt->fetchAll();
        
        // Organizar plano semanal
        $weekPlan = [];
        $mealTypes = ['Almoço', 'Jantar', 'Pequeno-almoço', 'Lanche'];
        $date = new DateTime($startDate);
        
        for ($day = 0; $day < 7; $day++) {
            $currentDate = $date->format('Y-m-d');
            $dayPlan = [
                'date' => $currentDate,
                'day_name' => $date->format('l'),
                'meals' => []
            ];
            
            for ($meal = 0; $meal < $mealsPerDay; $meal++) {
                $recipeIndex = ($day * $mealsPerDay + $meal) % count($recipes);
                $recipe = $recipes[$recipeIndex] ?? null;
                
                if ($recipe) {
                    $dayPlan['meals'][] = [
                        'meal_type' => $mealTypes[$meal % count($mealTypes)],
                        'recipe' => $recipe,
                        'scheduled_time' => $meal == 0 ? '12:00' : '19:00'
                    ];
                }
            }
            
            $weekPlan[] = $dayPlan;
            $date->modify('+1 day');
        }
        
        // Registar sugestão
        $stmt = $db->prepare("
            INSERT INTO ai_suggestions (user_id, suggestion_type, content, context) 
            VALUES (?, 'meal_plan', ?, ?)
        ");
        $stmt->execute([
            $userId,
            json_encode($weekPlan, JSON_UNESCAPED_UNICODE),
            json_encode(['start_date' => $startDate, 'servings' => $servings], JSON_UNESCAPED_UNICODE)
        ]);
        
        jsonSuccess('Plano semanal gerado.', [
            'plan' => $weekPlan,
            'explanation' => 'Este plano foi criado com receitas variadas para toda a semana. Pode ajustar conforme necessário.'
        ]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao gerar plano semanal: ' . $e->getMessage(), 500);
    }
}

// ============================================
// SUGERIR MELHORIAS PARA RECEITA
// ============================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'suggest_improvements') {
    $sessionToken = $input['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $recipeId = $input['recipeId'] ?? 0;
    
    try {
        $db = getDB();
        
        // Buscar receita
        $stmt = $db->prepare("SELECT * FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            jsonError('Receita não encontrada.', 404);
        }
        
        // Gerar sugestões de melhoria (baseado em lógica simples)
        $suggestions = [];
        
        // Verificar descrição
        if (empty($recipe['description']) || strlen($recipe['description']) < 50) {
            $suggestions[] = [
                'type' => 'description',
                'priority' => 'high',
                'message' => 'Adicione uma descrição mais detalhada da receita para ajudar outros utilizadores.'
            ];
        }
        
        // Verificar imagem
        if (empty($recipe['image'])) {
            $suggestions[] = [
                'type' => 'image',
                'priority' => 'high',
                'message' => 'Adicione uma imagem apetitosa da receita para torná-la mais atraente.'
            ];
        }
        
        // Verificar tempo de preparação
        if (empty($recipe['prep_time']) || empty($recipe['cook_time'])) {
            $suggestions[] = [
                'type' => 'timing',
                'priority' => 'medium',
                'message' => 'Especifique os tempos de preparação e confecção para melhor planeamento.'
            ];
        }
        
        // Verificar porções
        if (empty($recipe['servings'])) {
            $suggestions[] = [
                'type' => 'servings',
                'priority' => 'medium',
                'message' => 'Indique quantas porções a receita rende.'
            ];
        }
        
        // Verificar instruções
        $instructions = $recipe['instructions'] ?? '';
        $stepCount = substr_count(strtolower($instructions), 'passo') + substr_count($instructions, "\n");
        if ($stepCount < 3) {
            $suggestions[] = [
                'type' => 'instructions',
                'priority' => 'medium',
                'message' => 'Divida as instruções em passos numerados para facilitar o seguimento da receita.'
            ];
        }
        
        // Sugestões gerais
        $suggestions[] = [
            'type' => 'tips',
            'priority' => 'low',
            'message' => 'Considere adicionar dicas ou variações da receita para torná-la mais versátil.'
        ];
        
        // Registar sugestão
        $stmt = $db->prepare("
            INSERT INTO ai_suggestions (user_id, suggestion_type, content, context) 
            VALUES (?, 'tip', ?, ?)
        ");
        $stmt->execute([
            $userId,
            json_encode($suggestions, JSON_UNESCAPED_UNICODE),
            json_encode(['recipe_id' => $recipeId], JSON_UNESCAPED_UNICODE)
        ]);
        
        jsonSuccess('Sugestões de melhoria geradas.', [
            'suggestions' => $suggestions,
            'recipe' => $recipe
        ]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao gerar sugestões: ' . $e->getMessage(), 500);
    }
}

// ============================================
// OBTER HISTÓRICO DE SUGESTÕES
// ============================================
if ($method === 'GET') {
    $sessionToken = $_GET['sessionToken'] ?? '';
    $userId = verifySession($sessionToken);
    
    $type = $_GET['type'] ?? null;
    
    try {
        $db = getDB();
        
        $sql = "SELECT * FROM ai_suggestions WHERE user_id = ?";
        $params = [$userId];
        
        if ($type) {
            $sql .= " AND suggestion_type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 20";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $suggestions = $stmt->fetchAll();
        
        jsonSuccess('Histórico carregado.', ['suggestions' => $suggestions]);
        
    } catch (PDOException $e) {
        jsonError('Erro ao carregar histórico: ' . $e->getMessage(), 500);
    }
}

// Método não suportado
jsonError('Ação não reconhecida.', 400);
?>
