<?php
// Script de teste para criar grupo diretamente
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Verificar se há usuários na base de dados
    $stmt = $db->query("SELECT id, username, email FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== UTILIZADORES NA BD ===\n";
    if (empty($users)) {
        echo "ERRO: Não há utilizadores na base de dados!\n";
        echo "Por favor, registe um utilizador primeiro.\n";
        exit;
    }
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}\n";
    }
    
    // Selecionar o primeiro usuário para teste
    $testUserId = $users[0]['id'];
    echo "\n=== UTILIZADOR DE TESTE ===\n";
    echo "Vamos criar um grupo com o utilizador ID: $testUserId ({$users[0]['username']})\n";
    
    // Tentar criar um grupo
    $groupName = "Grupo Teste " . time();
    $groupDescription = "Grupo de teste criado em " . date('Y-m-d H:i:s');
    
    echo "\n=== CRIANDO GRUPO ===\n";
    echo "Nome: $groupName\n";
    echo "Descrição: $groupDescription\n";
    
    $stmt = $db->prepare("
        INSERT INTO `groups` (name, description, image, created_by) 
        VALUES (?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $groupName,
        $groupDescription,
        '',
        $testUserId
    ]);
    
    if ($success) {
        $groupId = $db->lastInsertId();
        echo "\n✓ Grupo criado com sucesso! ID: $groupId\n";
        
        // Adicionar o usuário como membro admin
        echo "\n=== ADICIONANDO MEMBRO ADMIN ===\n";
        $stmt = $db->prepare("
            INSERT INTO `group_members` (group_id, user_id, role) 
            VALUES (?, ?, 'admin')
        ");
        $stmt->execute([$groupId, $testUserId]);
        echo "✓ Utilizador adicionado como admin do grupo\n";
        
        // Verificar o grupo criado
        $stmt = $db->prepare("
            SELECT g.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM `group_members` WHERE group_id = g.id) as member_count
            FROM `groups` g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.id = ?
        ");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n=== DETALHES DO GRUPO ===\n";
        print_r($group);
        
    } else {
        echo "\n✗ ERRO ao criar grupo\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>
