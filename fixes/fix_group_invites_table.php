    <?php
// Script PHP para executar a correção da tabela group_invites
require_once 'api/db.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Corrigindo tabela group_invites...</h2>";
    
    // Verificar e adicionar coluna inviter_id
    $stmt = $db->query("SHOW COLUMNS FROM group_invites LIKE 'inviter_id'");
    $inviterExists = $stmt->rowCount() > 0;
    
    if ($inviterExists) {
        echo "<p style='color: green;'>✓ A coluna inviter_id já existe!</p>";
    } else {
        echo "<p style='color: orange;'>⚠ A coluna inviter_id não existe. Adicionando...</p>";
        
        // Adicionar coluna inviter_id
        $db->exec("ALTER TABLE group_invites ADD COLUMN inviter_id INT NOT NULL AFTER group_id");
        echo "<p style='color: green;'>✓ Coluna inviter_id adicionada com sucesso!</p>";
        
        // Adicionar foreign key para inviter
        try {
            $db->exec("ALTER TABLE group_invites ADD CONSTRAINT fk_inviter FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE");
            echo "<p style='color: green;'>✓ Foreign key para inviter_id adicionada!</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                echo "<p style='color: orange;'>⚠ Aviso: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    // Verificar e adicionar coluna invitee_id
    $stmt = $db->query("SHOW COLUMNS FROM group_invites LIKE 'invitee_id'");
    $inviteeExists = $stmt->rowCount() > 0;
    
    if ($inviteeExists) {
        echo "<p style='color: green;'>✓ A coluna invitee_id já existe!</p>";
    } else {
        echo "<p style='color: orange;'>⚠ A coluna invitee_id não existe. Adicionando...</p>";
        
        // Adicionar coluna invitee_id
        $db->exec("ALTER TABLE group_invites ADD COLUMN invitee_id INT NOT NULL AFTER inviter_id");
        echo "<p style='color: green;'>✓ Coluna invitee_id adicionada com sucesso!</p>";
    }
    
    // Verificar e adicionar coluna invitee_user_code
    $stmt = $db->query("SHOW COLUMNS FROM group_invites LIKE 'invitee_user_code'");
    $userCodeExists = $stmt->rowCount() > 0;
    
    if ($userCodeExists) {
        echo "<p style='color: green;'>✓ A coluna invitee_user_code já existe!</p>";
    } else {
        echo "<p style='color: orange;'>⚠ A coluna invitee_user_code não existe. Adicionando...</p>";
        
        // Adicionar coluna invitee_user_code
        $db->exec("ALTER TABLE group_invites ADD COLUMN invitee_user_code VARCHAR(6) NOT NULL AFTER invitee_id");
        echo "<p style='color: green;'>✓ Coluna invitee_user_code adicionada com sucesso!</p>";
    }
    
    // Agora adicionar as foreign keys e índices
    if (!$inviteeExists) {
        
        // Adicionar foreign key
        try {
            $db->exec("ALTER TABLE group_invites ADD CONSTRAINT fk_invitee FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE");
            echo "<p style='color: green;'>✓ Foreign key adicionada com sucesso!</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
            echo "<p style='color: orange;'>⚠ Foreign key já existe</p>";
        }
        
        // Adicionar índice único
        try {
            $db->exec("ALTER TABLE group_invites ADD UNIQUE KEY unique_invite (group_id, invitee_id)");
            echo "<p style='color: green;'>✓ Índice único adicionado com sucesso!</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
            echo "<p style='color: orange;'>⚠ Índice único já existe</p>";
        }
        
        // Adicionar índice de performance
        try {
            $db->exec("ALTER TABLE group_invites ADD INDEX idx_invitee_status (invitee_id, status)");
            echo "<p style='color: green;'>✓ Índice de performance adicionado com sucesso!</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
            echo "<p style='color: orange;'>⚠ Índice de performance já existe</p>";
        }
    }
    
    // Mostrar estrutura final
    echo "<h3>Estrutura final da tabela group_invites:</h3>";
    $stmt = $db->query("DESCRIBE group_invites");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        $highlight = (in_array($column['Field'], ['inviter_id', 'invitee_id', 'invitee_user_code'])) ? "style='background-color: #90EE90;'" : "";
        echo "<tr $highlight>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3 style='color: green;'>✓ Tabela corrigida com sucesso!</h3>";
    echo "<p>Agora pode testar novamente o envio de convites.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Erro ao corrigir a tabela: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<h3>Solução alternativa:</h3>";
    echo "<p>Execute manualmente os seguintes comandos SQL no phpMyAdmin:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>";
    echo "ALTER TABLE group_invites ADD COLUMN inviter_id INT NOT NULL AFTER group_id;\n";
    echo "ALTER TABLE group_invites ADD CONSTRAINT fk_inviter FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE;\n";
    echo "ALTER TABLE group_invites ADD COLUMN invitee_id INT NOT NULL AFTER inviter_id;\n";
    echo "ALTER TABLE group_invites ADD COLUMN invitee_user_code VARCHAR(6) NOT NULL AFTER invitee_id;\n";
    echo "ALTER TABLE group_invites ADD CONSTRAINT fk_invitee FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE;\n";
    echo "ALTER TABLE group_invites ADD UNIQUE KEY unique_invite (group_id, invitee_id);\n";
    echo "ALTER TABLE group_invites ADD INDEX idx_invitee_status (invitee_id, status);";
    echo "</pre>";
}
