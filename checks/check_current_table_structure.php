<?php
// Verificar estrutura atual da tabela group_invites
require_once 'api/db.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Estrutura atual da tabela group_invites:</h2>";
    
    $stmt = $db->query("DESCRIBE group_invites");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Colunas necessárias:</h3>";
    $requiredColumns = ['id', 'group_id', 'inviter_id', 'invitee_id', 'invitee_user_code', 'status', 'created_at', 'updated_at'];
    $existingColumns = array_column($columns, 'Field');
    
    echo "<ul>";
    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "<li style='color: green;'>✓ $col</li>";
        } else {
            echo "<li style='color: red;'>✗ $col (em falta)</li>";
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
