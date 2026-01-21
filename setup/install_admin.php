<?php
// Script para instalar sistema de administração
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "🔧 Instalando sistema de administração...\n\n";
    
    // 1. Adicionar campo is_admin
    try {
        $db->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
        echo "✅ Campo 'is_admin' adicionado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  Campo 'is_admin' já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Adicionar campo banned
    try {
        $db->exec("ALTER TABLE users ADD COLUMN banned BOOLEAN DEFAULT FALSE");
        echo "✅ Campo 'banned' adicionado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  Campo 'banned' já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Adicionar campo banned_at
    try {
        $db->exec("ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL");
        echo "✅ Campo 'banned_at' adicionado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  Campo 'banned_at' já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Adicionar campo banned_reason
    try {
        $db->exec("ALTER TABLE users ADD COLUMN banned_reason TEXT NULL");
        echo "✅ Campo 'banned_reason' adicionado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  Campo 'banned_reason' já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 5. Adicionar índice
    try {
        $db->exec("ALTER TABLE users ADD INDEX idx_is_admin (is_admin)");
        echo "✅ Índice 'idx_is_admin' criado\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "ℹ️  Índice 'idx_is_admin' já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 6. Criar conta admin
    echo "\n🔐 Criando conta de administrador...\n";
    
    // Verificar se admin já existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@chefguedes.pt']);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        // Atualizar admin existente
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ?, is_admin = 1, user_code = 'ADMIN1' WHERE email = ?");
        $stmt->execute([$hashedPassword, 'admin@chefguedes.pt']);
        echo "ℹ️  Conta admin atualizada\n";
    } else {
        // Criar novo admin
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin, user_code, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', 'admin@chefguedes.pt', $hashedPassword, 1, 'ADMIN1']);
        echo "✅ Conta admin criada\n";
    }
    
    echo "\n";
    echo "════════════════════════════════════════\n";
    echo "✅ Sistema de administração instalado!\n";
    echo "════════════════════════════════════════\n\n";
    echo "📧 Email: admin@chefguedes.pt\n";
    echo "🔑 Password: admin123\n\n";
    echo "⚠️  IMPORTANTE: Altere a password após o primeiro login!\n\n";
    echo "🌐 Aceda a: http://localhost/siteguedes/login.php\n";
    echo "════════════════════════════════════════\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
