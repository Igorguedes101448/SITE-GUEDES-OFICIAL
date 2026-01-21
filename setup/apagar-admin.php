<?php
// ============================================
// ChefGuedes - Apagar Contas Admin
// Remove todas as contas de administrador
// ⚠️ USE COM CUIDADO!
// ============================================

require_once 'api/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmacao = $_POST['confirmacao'] ?? '';
    
    if ($confirmacao !== 'APAGAR TUDO') {
        $message = 'Confirmação incorreta. Digite exatamente: APAGAR TUDO';
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            
            // Contar admins
            $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
            $count = $stmt->fetch()['count'];
            
            // Apagar todos os admins
            $stmt = $db->prepare("DELETE FROM users WHERE is_admin = 1");
            $stmt->execute();
            
            $message = "✅ {$count} conta(s) de administrador apagada(s) com sucesso! Pode criar uma nova agora.";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Erro ao apagar: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apagar Admin - ChefGuedes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
            color: #dc3545;
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
        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
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
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #dc3545;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover {
            background: #c82333;
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
        <h1>⚠️ ZONA DE PERIGO</h1>
        <p class="subtitle">Apagar TODAS as Contas Admin</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
                <?php if ($messageType === 'success'): ?>
                    <p style="margin-top: 10px;">
                        <a href="setup-admin.html" style="color: #155724; font-weight: 600;">→ Criar Nova Conta Admin</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠️ ATENÇÃO!</strong>
                <p>Esta ação irá apagar <strong>TODAS</strong> as contas de administrador do sistema.</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Não pode ser desfeita</li>
                    <li>Todas as contas admin serão removidas</li>
                    <li>Terá que criar uma nova conta depois</li>
                </ul>
            </div>
            
            <form method="POST" onsubmit="return confirm('⚠️ TEM CERTEZA ABSOLUTA? Esta ação não pode ser desfeita!');">
                <div class="form-group">
                    <label>Para confirmar, digite exatamente:</label>
                    <p style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; text-align: center; font-weight: 600; margin-bottom: 10px;">
                        APAGAR TUDO
                    </p>
                    <input type="text" name="confirmacao" required placeholder="Digite: APAGAR TUDO">
                </div>
                
                <button type="submit" class="btn">🗑️ Apagar Todas as Contas Admin</button>
            </form>
        <?php endif; ?>
        
        <div class="link">
            <a href="verificar-admin.php">← Cancelar e Voltar</a>
        </div>
    </div>
</body>
</html>
