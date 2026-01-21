<?php
// Script para corrigir a receita do Bife
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "=== Corrigindo receita 'Bife de Vaca' ===\n\n";
    
    // Procurar a receita
    $stmt = $db->prepare("
        SELECT id, title, author_id, is_draft, visibility 
        FROM recipes 
        WHERE title LIKE '%Bife%'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute();
    $recipe = $stmt->fetch();
    
    if (!$recipe) {
        echo "❌ Receita não encontrada!\n";
        exit;
    }
    
    echo "Receita encontrada:\n";
    echo "ID: {$recipe['id']}\n";
    echo "Título: {$recipe['title']}\n";
    echo "Status antes: " . ($recipe['is_draft'] ? 'PRIVADO' : 'PÚBLICO') . "\n";
    echo "Visibilidade antes: {$recipe['visibility']}\n\n";
    
    // Corrigir para privado
    $stmt = $db->prepare("
        UPDATE recipes 
        SET is_draft = 1, visibility = 'private'
        WHERE id = ?
    ");
    $stmt->execute([$recipe['id']]);
    
    echo "✅ Receita corrigida!\n";
    echo "Status agora: PRIVADO\n";
    echo "Visibilidade agora: private\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
