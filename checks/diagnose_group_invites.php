<?php
// Verificar estrutura completa da tabela group_invites
require_once 'api/db.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Estrutura COMPLETA da tabela group_invites:</h2>";
    
    // Colunas
    $stmt = $db->query("DESCRIBE group_invites");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Colunas:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Foreign Keys
    echo "<h3>Foreign Keys:</h3>";
    $stmt = $db->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'siteguedes'
        AND TABLE_NAME = 'group_invites'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Constraint</th><th>Coluna</th><th>Referencia Tabela</th><th>Referencia Coluna</th></tr>";
    foreach ($fks as $fk) {
        $isWrong = (strpos($fk['COLUMN_NAME'], 'invited') !== false) ? "style='background-color: #ffcccc;'" : "";
        echo "<tr $isWrong>";
        echo "<td>" . htmlspecialchars($fk['CONSTRAINT_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($fk['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Índices
    echo "<h3>Todos os Índices:</h3>";
    $stmt = $db->query("SHOW INDEX FROM group_invites");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th><th>Type</th></tr>";
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . ($index['Non_unique'] == 0 ? 'Sim' : 'Não') . "</td>";
        echo "<td>" . htmlspecialchars($index['Index_type']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Diagnóstico:</h3>";
    echo "<ul>";
    
    $hasInvitedBy = false;
    foreach ($columns as $col) {
        if (strpos($col['Field'], 'invited') !== false) {
            echo "<li style='color: red;'>❌ Encontrada coluna problemática: <strong>" . htmlspecialchars($col['Field']) . "</strong></li>";
            $hasInvitedBy = true;
        }
    }
    
    if ($hasInvitedBy) {
        echo "<li style='color: orange;'>⚠️ A tabela tem colunas com nomenclatura antiga que precisam ser removidas ou renomeadas</li>";
    }
    
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
