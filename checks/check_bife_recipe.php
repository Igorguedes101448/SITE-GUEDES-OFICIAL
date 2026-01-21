<?php
// Script para verificar a receita Bife de Vaca
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "=== Procurando receita 'Bife de Vaca' ===\n\n";
    
    // Procurar a receita
    $stmt = $db->prepare("
        SELECT r.*, u.username as author_name 
        FROM recipes r
        LEFT JOIN users u ON r.author_id = u.id
        WHERE r.title LIKE '%Bife%' OR r.title LIKE '%bife%'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $recipes = $stmt->fetchAll();
    
    if (empty($recipes)) {
        echo "❌ Nenhuma receita de bife encontrada!\n";
        exit;
    }
    
    echo "Receitas encontradas:\n";
    echo "========================================\n";
    foreach ($recipes as $recipe) {
        echo "ID: {$recipe['id']}\n";
        echo "Título: {$recipe['title']}\n";
        echo "Autor: {$recipe['author_name']} (ID: {$recipe['author_id']})\n";
        echo "Status: " . ($recipe['is_draft'] ? '🔒 PRIVADO' : '🌐 PÚBLICO') . "\n";
        echo "Visibilidade: {$recipe['visibility']}\n";
        echo "Criado em: {$recipe['created_at']}\n";
        echo "========================================\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
