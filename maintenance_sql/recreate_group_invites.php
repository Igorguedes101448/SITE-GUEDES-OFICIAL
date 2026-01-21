<?php
// Script para RECRIAR a tabela group_invites corretamente
require_once 'api/db.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Recriando tabela group_invites...</h2>";
    echo "<p style='color: orange;'><strong>⚠️ AVISO:</strong> Isto irá apagar todos os convites existentes!</p>";
    
    // Verificar se há dados na tabela
    $stmt = $db->query("SELECT COUNT(*) as total FROM group_invites");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        echo "<p style='color: orange;'>⚠️ A tabela tem {$result['total']} convite(s). Estes serão apagados.</p>";
    }
    
    // Remover tabela antiga
    echo "<p>1. Removendo tabela antiga...</p>";
    $db->exec("DROP TABLE IF EXISTS group_invites");
    echo "<p style='color: green;'>✓ Tabela antiga removida</p>";
    
    // Criar tabela nova com estrutura correta
    echo "<p>2. Criando tabela com estrutura correta...</p>";
    $db->exec("
        CREATE TABLE group_invites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            inviter_id INT NOT NULL COMMENT 'Quem está a enviar o convite',
            invitee_id INT NOT NULL COMMENT 'Quem está a receber o convite',
            invitee_user_code VARCHAR(6) NOT NULL COMMENT 'Código do utilizador convidado',
            status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
            FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE,
            
            UNIQUE KEY unique_invite (group_id, invitee_id),
            INDEX idx_invitee_status (invitee_id, status),
            INDEX idx_group_pending (group_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green;'>✓ Tabela criada com sucesso!</p>";
    
    // Mostrar estrutura final
    echo "<h3>Estrutura final da tabela:</h3>";
    $stmt = $db->query("DESCRIBE group_invites");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        $highlight = in_array($column['Field'], ['inviter_id', 'invitee_id', 'invitee_user_code']) 
            ? "style='background-color: #90EE90;'" : "";
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
    
    // Mostrar Foreign Keys
    echo "<h3>Foreign Keys criadas:</h3>";
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
    
    echo "<ul>";
    foreach ($fks as $fk) {
        echo "<li style='color: green;'>✓ {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}({$fk['REFERENCED_COLUMN_NAME']})</li>";
    }
    echo "</ul>";
    
    echo "<h2 style='color: green;'>✓ Tabela group_invites recriada com sucesso!</h2>";
    echo "<p>Agora pode testar o envio de convites para grupos.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Erro: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<h3>Solução alternativa:</h3>";
    echo "<p>Execute o ficheiro RECREATE_GROUP_INVITES.sql no phpMyAdmin</p>";
}
