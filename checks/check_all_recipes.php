<?php
// Script para verificar todas as receitas
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Buscar todas as receitas
    $stmt = $db->prepare("SELECT id, title, image FROM recipes ORDER BY id");
    $stmt->execute();
    $recipes = $stmt->fetchAll();
    
    echo "Receitas na base de dados:\n";
    echo "Total: " . count($recipes) . "\n\n";
    
    foreach ($recipes as $recipe) {
        $hasImage = !empty($recipe['image']) ? 'SIM' : 'NÃO';
        echo "ID: {$recipe['id']} | Título: {$recipe['title']} | Imagem: {$hasImage}\n";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
