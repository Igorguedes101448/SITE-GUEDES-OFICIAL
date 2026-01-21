<?php
require_once 'api/db.php';

echo "<h2>Migração: Atualizar tabela schedules</h2>";

try {
    $db = getDB();
    
    // Verificar e adicionar coluna group_id
    $stmt = $db->query("SHOW COLUMNS FROM schedules LIKE 'group_id'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<p style='color: green;'>✓ Coluna 'group_id' já existe!</p>";
    } else {
        echo "<p style='color: orange;'>→ Adicionando coluna 'group_id'...</p>";
        $db->exec("ALTER TABLE schedules ADD COLUMN group_id INT NULL AFTER user_id");
        echo "<p style='color: green;'>✓ Coluna 'group_id' adicionada com sucesso!</p>";
    }
    
    // Verificar e adicionar coluna meal_type
    $stmt = $db->query("SHOW COLUMNS FROM schedules LIKE 'meal_type'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<p style='color: green;'>✓ Coluna 'meal_type' já existe!</p>";
    } else {
        echo "<p style='color: orange;'>→ Adicionando coluna 'meal_type'...</p>";
        $db->exec("ALTER TABLE schedules ADD COLUMN meal_type ENUM('Pequeno-almoço', 'Almoço', 'Jantar', 'Lanche') DEFAULT 'Almoço' AFTER description");
        echo "<p style='color: green;'>✓ Coluna 'meal_type' adicionada com sucesso!</p>";
    }
    
    // Verificar e adicionar coluna notes (se não existir description)
    $stmt = $db->query("SHOW COLUMNS FROM schedules LIKE 'notes'");
    $notesExists = $stmt->fetch();
    
    if (!$notesExists) {
        echo "<p style='color: orange;'>→ Adicionando coluna 'notes'...</p>";
        $db->exec("ALTER TABLE schedules ADD COLUMN notes TEXT AFTER meal_type");
        echo "<p style='color: green;'>✓ Coluna 'notes' adicionada com sucesso!</p>";
    } else {
        echo "<p style='color: green;'>✓ Coluna 'notes' já existe!</p>";
    }
    
    // Verificar se a foreign key existe
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'schedules' 
        AND COLUMN_NAME = 'group_id' 
        AND REFERENCED_TABLE_NAME = 'groups'
    ");
    $fkExists = $stmt->fetch()['count'] > 0;
    
    if ($fkExists) {
        echo "<p style='color: green;'>✓ Foreign key já existe!</p>";
    } else {
        echo "<p style='color: orange;'>→ Adicionando foreign key...</p>";
        
        try {
            $db->exec("ALTER TABLE schedules ADD CONSTRAINT fk_schedules_group FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE");
            echo "<p style='color: green;'>✓ Foreign key adicionada com sucesso!</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠ Foreign key já pode existir ou houve um erro: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar se o índice existe
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'schedules' 
        AND INDEX_NAME = 'idx_group_schedules'
    ");
    $indexExists = $stmt->fetch()['count'] > 0;
    
    if ($indexExists) {
        echo "<p style='color: green;'>✓ Índice 'idx_group_schedules' já existe!</p>";
    } else {
        echo "<p style='color: orange;'>→ Adicionando índice...</p>";
        
        try {
            $db->exec("ALTER TABLE schedules ADD INDEX idx_group_schedules (group_id)");
            echo "<p style='color: green;'>✓ Índice adicionado com sucesso!</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠ Índice já pode existir ou houve um erro: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Estrutura atualizada da tabela schedules:</h3>";
    $stmt = $db->query("DESCRIBE schedules");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Migração concluída com sucesso!</h2>";
    echo "<p><a href='pages/grupos.html'>← Voltar para Grupos</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
