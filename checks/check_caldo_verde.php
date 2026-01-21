<?php
// Script para verificar a receita Caldo Verde
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Buscar receita Caldo Verde
    $stmt = $db->prepare("SELECT id, title, ingredients, instructions FROM recipes WHERE title LIKE '%Caldo Verde%'");
    $stmt->execute();
    $recipe = $stmt->fetch();
    
    if ($recipe) {
        echo "Receita encontrada:\n";
        echo "ID: " . $recipe['id'] . "\n";
        echo "Título: " . $recipe['title'] . "\n";
        echo "\nIngredientes:\n";
        echo $recipe['ingredients'] . "\n";
        echo "\nInstruções:\n";
        echo $recipe['instructions'] . "\n";
    } else {
        echo "Receita Caldo Verde não encontrada.\n";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
