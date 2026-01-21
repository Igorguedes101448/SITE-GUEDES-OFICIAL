<?php
// Script para verificar a estrutura da tabela group_invites
require_once 'api/db.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Estrutura da tabela group_invites:</h2>";
    
    // Verificar se a tabela existe
    $stmt = $db->query("SHOW TABLES LIKE 'group_invites'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'><strong>ERRO: A tabela group_invites não existe!</strong></p>";
        echo "<p>Execute o script de migração ou o schema.sql para criar a tabela.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ A tabela group_invites existe</p>";
    
    // Obter estrutura da tabela
    $stmt = $db->query("DESCRIBE group_invites");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Colunas da tabela:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasInviteeId = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'invitee_id') {
            $hasInviteeId = true;
        }
    }
    echo "</table>";
    
    // Verificar se a coluna invitee_id existe
    echo "<h3>Verificação de colunas essenciais:</h3>";
    if ($hasInviteeId) {
        echo "<p style='color: green;'>✓ Coluna invitee_id existe</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ ERRO: Coluna invitee_id NÃO existe!</strong></p>";
        echo "<p>Esta coluna é necessária para o sistema de convites funcionar.</p>";
        echo "<h3>Solução: Execute o seguinte comando SQL:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>";
        echo "ALTER TABLE group_invites ADD COLUMN invitee_id INT NOT NULL AFTER inviter_id;";
        echo "\nALTER TABLE group_invites ADD FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE;";
        echo "\nALTER TABLE group_invites ADD UNIQUE KEY unique_invite (group_id, invitee_id);";
        echo "\nALTER TABLE group_invites ADD INDEX idx_invitee_status (invitee_id, status);";
        echo "</pre>";
    }
    
    // Verificar índices
    echo "<h3>Índices da tabela:</h3>";
    $stmt = $db->query("SHOW INDEX FROM group_invites");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Key Name</th><th>Column</th><th>Non Unique</th></tr>";
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
        echo "<td>" . htmlspecialchars($index['Non_unique']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar dados
    echo "<h3>Dados na tabela:</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM group_invites");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de registos: " . $result['total'] . "</p>";
    
    if ($result['total'] > 0) {
        $stmt = $db->query("SELECT * FROM group_invites ORDER BY created_at DESC LIMIT 5");
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Últimos 5 convites:</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>";
        foreach (array_keys($invites[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        foreach ($invites as $invite) {
            echo "<tr>";
            foreach ($invite as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Erro ao verificar a tabela: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
}
