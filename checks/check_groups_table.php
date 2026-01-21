<?php
// Verificar se a tabela groups existe
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Verificar se a tabela groups existe
    $stmt = $db->query("SHOW TABLES LIKE 'groups'");
    $groupsExists = $stmt->rowCount() > 0;
    
    echo "Tabela 'groups' existe: " . ($groupsExists ? "SIM" : "NÃO") . "\n";
    
    if ($groupsExists) {
        // Mostrar estrutura da tabela
        echo "\nEstrutura da tabela 'groups':\n";
        $stmt = $db->query("DESCRIBE `groups`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
        
        // Contar grupos existentes
        $stmt = $db->query("SELECT COUNT(*) as total FROM `groups`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nTotal de grupos na BD: {$count['total']}\n";
    }
    
    // Verificar se a tabela group_members existe
    $stmt = $db->query("SHOW TABLES LIKE 'group_members'");
    $groupMembersExists = $stmt->rowCount() > 0;
    
    echo "\nTabela 'group_members' existe: " . ($groupMembersExists ? "SIM" : "NÃO") . "\n";
    
    if ($groupMembersExists) {
        // Mostrar estrutura da tabela
        echo "\nEstrutura da tabela 'group_members':\n";
        $stmt = $db->query("DESCRIBE `group_members`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>
