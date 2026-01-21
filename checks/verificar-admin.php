<?php
// ============================================
// ChefGuedes - Verificador de Admin
// Verifica se existe conta admin e mostra opções
// ============================================

require_once 'api/db.php';

try {
    $db = getDB();
    
    // Verificar se existe admin
    $stmt = $db->prepare("SELECT id, username, email, created_at FROM users WHERE is_admin = 1");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verificador Admin - ChefGuedes</title>
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
                max-width: 600px;
                width: 100%;
            }
            h1 {
                color: #667eea;
                margin-bottom: 20px;
                text-align: center;
            }
            .info-box {
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .success { background: #d4edda; border-left: 4px solid #28a745; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
            .error { background: #f8d7da; border-left: 4px solid #dc3545; }
            .admin-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 15px;
                border-left: 4px solid #667eea;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                margin: 5px;
                text-align: center;
            }
            .btn:hover { opacity: 0.9; }
            .btn-danger { background: #dc3545; }
            .btn-secondary { background: #6c757d; }
            table { width: 100%; margin-top: 10px; }
            td { padding: 8px; border-bottom: 1px solid #e0e0e0; }
            td:first-child { font-weight: 600; color: #666; }
            .actions { text-align: center; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Verificador de Admin</h1>
            
            <?php if (count($admins) > 0): ?>
                <div class="info-box success">
                    <strong>✅ Contas de Administrador Encontradas:</strong>
                    <p>Já existe<?php echo count($admins) > 1 ? 'm' : ''; ?> <?php echo count($admins); ?> conta<?php echo count($admins) > 1 ? 's' : ''; ?> de administrador no sistema.</p>
                </div>
                
                <?php foreach ($admins as $admin): ?>
                    <div class="admin-card">
                        <table>
                            <tr>
                                <td style="width: 120px;">ID:</td>
                                <td><?php echo htmlspecialchars($admin['id']); ?></td>
                            </tr>
                            <tr>
                                <td>Username:</td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            </tr>
                            <tr>
                                <td>Email:</td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            </tr>
                            <tr>
                                <td>Criado em:</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
                
                <div class="info-box warning">
                    <strong>💡 O que fazer agora?</strong>
                    <ul style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                        <li>Se você <strong>conhece a password</strong>, faça login normalmente</li>
                        <li>Se <strong>esqueceu a password</strong>, use a opção "Resetar Password" abaixo</li>
                        <li>Se esta conta <strong>não é sua</strong>, contacte o administrador do sistema</li>
                    </ul>
                </div>
                
                <div class="actions">
                    <a href="login.html" class="btn">🔐 Fazer Login</a>
                    <a href="resetar-password-admin.php" class="btn btn-secondary">🔑 Resetar Password</a>
                    <a href="apagar-admin.php" class="btn btn-danger" onclick="return confirm('⚠️ ATENÇÃO: Isto vai apagar TODAS as contas admin! Tem certeza?')">🗑️ Apagar Admin</a>
                </div>
                
            <?php else: ?>
                <div class="info-box error">
                    <strong>❌ Nenhuma Conta Admin Encontrada</strong>
                    <p>Não existe nenhuma conta de administrador no sistema.</p>
                </div>
                
                <div class="info-box warning">
                    <strong>🔍 Possíveis causas:</strong>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>O script SQL não foi executado corretamente</li>
                        <li>A coluna is_admin não existe</li>
                        <li>A conta foi apagada acidentalmente</li>
                    </ul>
                </div>
                
                <div class="actions">
                    <a href="setup-admin.html" class="btn">🚀 Criar Nova Conta Admin</a>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <a href="admin-inicio.html" style="color: #667eea; text-decoration: none;">← Voltar ao Início</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Erro - ChefGuedes</title>
        <style>
            body { font-family: Arial; background: #f8f9fa; padding: 40px; }
            .error { background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Erro de Conexão</h2>
            <p><strong>Não foi possível conectar à base de dados.</strong></p>
            <p>Erro: <?php echo htmlspecialchars($e->getMessage()); ?></p>
            <hr>
            <p><strong>Possíveis soluções:</strong></p>
            <ul>
                <li>Verifique se o WAMP/XAMPP está a correr</li>
                <li>Verifique se a base de dados 'siteguedes' existe</li>
                <li>Execute o script SQL de instalação</li>
            </ul>
            <p style="margin-top: 20px;">
                <a href="admin-inicio.html" style="color: #667eea;">← Voltar</a>
            </p>
        </div>
    </body>
    </html>
    <?php
}
?>
