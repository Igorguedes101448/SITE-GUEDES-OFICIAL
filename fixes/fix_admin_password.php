<?php
// Script para verificar e corrigir a password do admin
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "🔍 Verificando conta admin...\n\n";
    
    // Buscar utilizador admin
    $stmt = $db->prepare("SELECT id, username, email, password, is_admin FROM users WHERE email = ?");
    $stmt->execute(['admin@chefguedes.pt']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Utilizador admin não encontrado!\n";
        exit(1);
    }
    
    echo "✅ Utilizador encontrado:\n";
    echo "   ID: " . $user['id'] . "\n";
    echo "   Username: " . $user['username'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   Is Admin: " . ($user['is_admin'] ? 'Sim' : 'Não') . "\n\n";
    
    // Testar password atual
    echo "🔐 Testando password 'admin123'...\n";
    $passwordHash = $user['password'];
    $isValid = password_verify('admin123', $passwordHash);
    
    echo "   Hash na BD: " . substr($passwordHash, 0, 50) . "...\n";
    echo "   Password válida: " . ($isValid ? "✅ SIM" : "❌ NÃO") . "\n\n";
    
    if (!$isValid) {
        echo "⚠️  Password incorreta! Gerando novo hash...\n\n";
        
        // Criar novo hash
        $newHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Atualizar na base de dados
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$newHash, 'admin@chefguedes.pt']);
        
        echo "✅ Password atualizada com sucesso!\n";
        echo "   Novo hash: " . substr($newHash, 0, 50) . "...\n\n";
        
        // Verificar novamente
        $testAgain = password_verify('admin123', $newHash);
        echo "   Verificação: " . ($testAgain ? "✅ SUCESSO" : "❌ ERRO") . "\n\n";
    }
    
    echo "════════════════════════════════════════\n";
    echo "✅ Conta admin configurada corretamente!\n";
    echo "════════════════════════════════════════\n\n";
    echo "📧 Email: admin@chefguedes.pt\n";
    echo "🔑 Password: admin123\n\n";
    echo "🌐 Teste agora: http://localhost/siteguedes/login.php\n";
    echo "════════════════════════════════════════\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
