<?php
// Script para testar visualização de receitas privadas
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "=== Testando Receitas Privadas do Utilizador 'teste' ===\n\n";
    
    // Encontrar utilizador teste
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username = 'teste'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Utilizador 'teste' não encontrado!\n";
        exit;
    }
    
    echo "✓ Utilizador encontrado: ID {$user['id']} - {$user['username']}\n\n";
    
    // Buscar todas as receitas do utilizador
    echo "--- RECEITAS PÚBLICAS (is_draft = 0) ---\n";
    $stmt = $db->prepare("
        SELECT id, title, category, is_draft, visibility, created_at 
        FROM recipes 
        WHERE author_id = ? AND is_draft = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $publicRecipes = $stmt->fetchAll();
    
    if (empty($publicRecipes)) {
        echo "Nenhuma receita pública encontrada.\n";
    } else {
        foreach ($publicRecipes as $recipe) {
            echo "• [{$recipe['id']}] {$recipe['title']} - {$recipe['category']} (criada em {$recipe['created_at']})\n";
        }
    }
    
    echo "\n--- RECEITAS PRIVADAS (is_draft = 1) ---\n";
    $stmt = $db->prepare("
        SELECT id, title, category, is_draft, visibility, created_at 
        FROM recipes 
        WHERE author_id = ? AND is_draft = 1
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $privateRecipes = $stmt->fetchAll();
    
    if (empty($privateRecipes)) {
        echo "Nenhuma receita privada encontrada.\n";
    } else {
        foreach ($privateRecipes as $recipe) {
            echo "• 🔒 [{$recipe['id']}] {$recipe['title']} - {$recipe['category']} (criada em {$recipe['created_at']})\n";
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "Total de receitas públicas: " . count($publicRecipes) . "\n";
    echo "Total de receitas privadas: " . count($privateRecipes) . "\n";
    echo "\n✅ Teste concluído!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
