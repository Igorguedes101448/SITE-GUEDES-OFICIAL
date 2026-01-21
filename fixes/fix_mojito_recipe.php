<?php
// Script para corrigir a receita Mojito do utilizador teste
// Tornar a receita privada (is_draft = 1)

require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "=== Procurando receita Mojito do utilizador teste ===\n\n";
    
    // Encontrar utilizador teste
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username = 'teste'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Utilizador 'teste' não encontrado!\n";
        exit;
    }
    
    echo "✓ Utilizador encontrado: ID {$user['id']} - {$user['username']}\n\n";
    
    // Procurar receita Mojito
    $stmt = $db->prepare("
        SELECT id, title, category, author_id, is_draft, visibility, created_at 
        FROM recipes 
        WHERE author_id = ? AND title LIKE '%Mojito%'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $recipes = $stmt->fetchAll();
    
    if (empty($recipes)) {
        echo "❌ Nenhuma receita Mojito encontrada para o utilizador teste!\n";
        exit;
    }
    
    echo "Receitas encontradas:\n";
    echo "----------------------------------------\n";
    foreach ($recipes as $recipe) {
        echo "ID: {$recipe['id']}\n";
        echo "Título: {$recipe['title']}\n";
        echo "Categoria: {$recipe['category']}\n";
        echo "Status: " . ($recipe['is_draft'] ? 'RASCUNHO (Privado)' : 'PUBLICADO') . "\n";
        echo "Visibilidade: {$recipe['visibility']}\n";
        echo "Criado em: {$recipe['created_at']}\n";
        echo "----------------------------------------\n";
    }
    
    echo "\n=== Tornando receitas PRIVADAS ===\n\n";
    
    // Tornar todas as receitas Mojito privadas
    foreach ($recipes as $recipe) {
        if ($recipe['is_draft'] == 0) {
            $stmt = $db->prepare("
                UPDATE recipes 
                SET is_draft = 1, visibility = 'private'
                WHERE id = ?
            ");
            $stmt->execute([$recipe['id']]);
            
            echo "✓ Receita '{$recipe['title']}' (ID: {$recipe['id']}) agora está PRIVADA\n";
        } else {
            echo "→ Receita '{$recipe['title']}' (ID: {$recipe['id']}) já estava privada\n";
        }
    }
    
    echo "\n✅ Problema resolvido! A receita Mojito agora está privada.\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
