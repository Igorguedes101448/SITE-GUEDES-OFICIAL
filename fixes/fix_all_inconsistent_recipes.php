<?php
// Script para corrigir TODAS as receitas com inconsistência
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "=== Procurando receitas com inconsistência ===\n\n";
    
    // Buscar receitas com visibility='private' mas is_draft=0 (públicas)
    $stmt = $db->prepare("
        SELECT r.id, r.title, r.author_id, r.is_draft, r.visibility, u.username
        FROM recipes r
        LEFT JOIN users u ON r.author_id = u.id
        WHERE r.visibility = 'private' AND r.is_draft = 0
    ");
    $stmt->execute();
    $inconsistentRecipes = $stmt->fetchAll();
    
    if (empty($inconsistentRecipes)) {
        echo "✅ Nenhuma receita com inconsistência encontrada!\n";
        exit;
    }
    
    echo "Receitas com inconsistência (visibility=private mas is_draft=0):\n";
    echo "========================================\n";
    foreach ($inconsistentRecipes as $recipe) {
        echo "ID: {$recipe['id']}\n";
        echo "Título: {$recipe['title']}\n";
        echo "Autor: {$recipe['username']} (ID: {$recipe['author_id']})\n";
        echo "========================================\n";
    }
    
    echo "\n=== Corrigindo receitas ===\n\n";
    
    // Corrigir todas
    $stmt = $db->prepare("
        UPDATE recipes 
        SET is_draft = 1
        WHERE visibility = 'private' AND is_draft = 0
    ");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "✅ {$count} receita(s) corrigida(s)!\n";
    echo "Todas as receitas com visibility='private' agora têm is_draft=1\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
