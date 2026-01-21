<?php
// Verificar estrutura da base de dados para grupos
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "=== Verificando tabelas de grupos ===\n\n";
    
    // Verificar tabela groups
    echo "1. Tabela 'groups':\n";
    $stmt = $db->query("SHOW TABLES LIKE 'groups'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "   ✓ Existe\n";
        $stmt = $db->query("DESCRIBE `groups`");
        $columns = $stmt->fetchAll();
        echo "   Colunas:\n";
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "   ✗ NÃO EXISTE\n";
    }
    
    echo "\n2. Tabela 'group_members':\n";
    $stmt = $db->query("SHOW TABLES LIKE 'group_members'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "   ✓ Existe\n";
        $stmt = $db->query("DESCRIBE `group_members`");
        $columns = $stmt->fetchAll();
        echo "   Colunas:\n";
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "   ✗ NÃO EXISTE\n";
    }
    
    echo "\n3. Tabela 'group_schedule':\n";
    $stmt = $db->query("SHOW TABLES LIKE 'group_schedule'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "   ✓ Existe\n";
    } else {
        echo "   ✗ NÃO EXISTE\n";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
