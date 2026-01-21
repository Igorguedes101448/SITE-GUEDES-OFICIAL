<?php
// ============================================
// ChefGuedes - Resetar Password Admin
// Permite alterar a password de uma conta admin
// ============================================

require_once 'api/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = $_POST['admin_id'] ?? null;
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!$adminId) {
        $message = 'Selecione um administrador.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'A password deve ter pelo menos 6 caracteres.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'As passwords não coincidem.';
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND is_admin = 1");
            $stmt->execute([$hashedPassword, $adminId]);
            
            if ($stmt->rowCount() > 0) {
                $message = 'Password alterada com sucesso! Pode fazer login agora.';
                $messageType = 'success';
            } else {
                $message = 'Erro ao alterar password. Verifique se o administrador existe.';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Erro: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Buscar admins
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE is_admin = 1");
    $stmt->execute();
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    $admins = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetar Password Admin - ChefGuedes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        select, input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .link {
            text-align: center;
            margin-top: 20px;
        }
        .link a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Resetar Password Admin</h1>
        <p class="subtitle">Altere a password de uma conta de administrador</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($admins) === 0): ?>
            <div class="alert alert-error">
                <strong>❌ Nenhum administrador encontrado!</strong>
                <p>Crie primeiro uma conta admin em <a href="setup-admin.html">setup-admin.html</a></p>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>👤 Selecione o Administrador:</label>
                    <select name="admin_id" required>
                        <option value="">-- Escolha --</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>">
                                <?php echo htmlspecialchars($admin['username']); ?> 
                                (<?php echo htmlspecialchars($admin['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>🔒 Nova Password:</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label>🔒 Confirmar Nova Password:</label>
                    <input type="password" name="confirm_password" required minlength="6" placeholder="Repita a password">
                </div>
                
                <button type="submit" class="btn">Alterar Password</button>
            </form>
        <?php endif; ?>
        
        <div class="link">
            <a href="verificar-admin.php">← Voltar</a>
        </div>
    </div>
</body>
</html>
