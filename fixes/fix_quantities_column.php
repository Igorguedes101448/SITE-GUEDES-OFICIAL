<?php
// Adicionar coluna 'quantities' à tabela recipes
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "<h2>Adicionando coluna 'quantities' à tabela recipes...</h2>";
    
    // Verificar se a coluna já existe
    $stmt = $db->query("SHOW COLUMNS FROM recipes LIKE 'quantities'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<p style='color: orange;'>⚠ A coluna 'quantities' já existe na tabela.</p>";
    } else {
        // Adicionar a coluna
        $db->exec("ALTER TABLE recipes ADD COLUMN quantities TEXT AFTER ingredients");
        echo "<p style='color: green;'>✓ Coluna 'quantities' adicionada com sucesso!</p>";
    }
    
    // Verificar a estrutura final
    echo "<h3>Estrutura atual da tabela recipes:</h3>";
    $stmt = $db->query("SHOW COLUMNS FROM recipes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($columns as $column) {
        $highlight = $column['Field'] === 'quantities' ? ' <-- ADICIONADA' : '';
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")" . $highlight . "\n";
    }
    echo "</pre>";
    
    echo "<p style='color: green;'><strong>✓ Correção concluída! Agora pode publicar receitas.</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
