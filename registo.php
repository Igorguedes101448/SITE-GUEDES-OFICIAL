<?php
session_start();
require_once 'api/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Validações
    if (strlen($username) < 3) {
        $error = 'O nome de utilizador deve ter pelo menos 3 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } elseif (strlen($password) < 6) {
        $error = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As palavras-passe não coincidem.';
    } else {
        try {
            $db = getDB();
            
            // Verificar se email já existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Este email já está registado.';
            } else {
                // Verificar se username já existe
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Este nome de utilizador já está em uso.';
                } else {
                    // Criar utilizador
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword]);
                    
                    $userId = $db->lastInsertId();
                    
                    // Criar preferências padrão
                    $stmt = $db->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
                    $stmt->execute([$userId]);
                    
                    $success = 'Conta criada com sucesso! A redirecionar para o login...';
                    header("refresh:2;url=login.php");
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao criar conta. Certifique-se de que a base de dados foi criada.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo - ChefGuedes</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        .auth-card {
            background-color: var(--bg-card);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
        }
        .auth-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-align: center;
        }
        .error-message {
            background-color: rgba(230, 57, 70, 0.1);
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
            padding: 12px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        .success-message {
            background-color: rgba(42, 157, 143, 0.1);
            border: 2px solid var(--success-color);
            color: var(--success-color);
            padding: 12px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>ChefGuedes</h1>
                <p>Cria a tua conta e junta-te à nossa comunidade!</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Nome de utilizador:</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Palavra-passe:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirmar palavra-passe:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Criar Conta</button>
                </div>
            </form>

            <div class="auth-link" style="text-align: center; margin-top: 20px;">
                Já tens conta? <a href="login.php">Inicia sessão aqui</a>
            </div>

            <div class="back-home" style="text-align: center; margin-top: 15px;">
                <a href="index.html">← Voltar à página inicial</a>
            </div>
        </div>
    </div>
</body>
</html>
