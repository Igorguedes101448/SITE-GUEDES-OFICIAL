<?php
// ============================================
// Gerar códigos únicos para utilizadores sem user_code
// ============================================

require_once 'api/db.php';

try {
    $db = getDB();
    
    // Buscar utilizadores sem user_code
    $stmt = $db->query("SELECT id, username FROM users WHERE user_code IS NULL OR user_code = ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "✓ Todos os utilizadores já têm código!\n";
        exit(0);
    }
    
    echo "Encontrados " . count($users) . " utilizadores sem código.\n\n";
    
    $updated = 0;
    foreach ($users as $user) {
        // Gerar código único
        $userCode = '';
        do {
            $userCode = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
            $checkStmt = $db->prepare("SELECT id FROM users WHERE user_code = ?");
            $checkStmt->execute([$userCode]);
        } while ($checkStmt->fetch());
        
        // Atualizar utilizador
        $updateStmt = $db->prepare("UPDATE users SET user_code = ? WHERE id = ?");
        $updateStmt->execute([$userCode, $user['id']]);
        
        echo "✓ Utilizador '{$user['username']}' (ID: {$user['id']}) → Código: {$userCode}\n";
        $updated++;
    }
    
    echo "\n✓ Total atualizado: {$updated} utilizadores\n";
    echo "✓ Processo concluído com sucesso!\n";
    
} catch (PDOException $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
