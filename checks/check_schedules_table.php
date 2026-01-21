<?php
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Verificar estrutura da tabela schedules
    $stmt = $db->query("DESCRIBE schedules");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Estrutura da tabela 'schedules':</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Verificar se a coluna group_id existe
    $hasGroupId = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'group_id') {
            $hasGroupId = true;
            break;
        }
    }
    
    if ($hasGroupId) {
        echo "<p style='color: green;'>✓ Coluna 'group_id' existe!</p>";
    } else {
        echo "<p style='color: red;'>✗ Coluna 'group_id' NÃO existe!</p>";
        echo "<p>Executando ALTER TABLE para adicionar a coluna...</p>";
        
        // Adicionar a coluna group_id
        $db->exec("
            ALTER TABLE schedules 
            ADD COLUMN group_id INT NULL AFTER user_id,
            ADD FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
            ADD INDEX idx_group_schedules (group_id)
        ");
        
        echo "<p style='color: green;'>✓ Coluna 'group_id' adicionada com sucesso!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>
