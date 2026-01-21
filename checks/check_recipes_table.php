<?php
// Verificar estrutura da tabela recipes
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Verificar colunas da tabela recipes
    $stmt = $db->query("SHOW COLUMNS FROM recipes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Estrutura da tabela 'recipes':</h2>";
    echo "<pre>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    echo "</pre>";
    
    // Verificar se a coluna quantities existe
    $hasQuantities = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'quantities') {
            $hasQuantities = true;
            break;
        }
    }
    
    if ($hasQuantities) {
        echo "<p style='color: green;'>✓ A coluna 'quantities' EXISTE na tabela</p>";
    } else {
        echo "<p style='color: red;'>✗ A coluna 'quantities' NÃO EXISTE na tabela</p>";
        echo "<p>Para adicionar a coluna, execute o SQL:</p>";
        echo "<pre>ALTER TABLE recipes ADD COLUMN quantities TEXT AFTER ingredients;</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
