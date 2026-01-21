<?php
// Teste direto da API de grupos
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Teste API Grupos - Direto</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .success { color: green; }
        .error { color: red; }
        h2 { color: #ff6b35; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Teste API de Grupos</h1>
    
    <?php
    require_once 'api/db.php';
    require_once 'api/profanity-filter.php';
    
    echo "<div class='section'>";
    echo "<h2>1. Teste de Conexão com BD</h2>";
    try {
        $db = getDB();
        echo "<p class='success'>✅ Conexão com banco de dados estabelecida</p>";
        
        // Verificar se a tabela groups existe
        $stmt = $db->query("SHOW TABLES LIKE 'groups'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabela 'groups' existe</p>";
            
            // Ver estrutura da tabela
            $stmt = $db->query("DESCRIBE `groups`");
            $columns = $stmt->fetchAll();
            echo "<p><strong>Estrutura da tabela groups:</strong></p>";
            echo "<pre>";
            foreach ($columns as $col) {
                echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']}\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='error'>❌ Tabela 'groups' não existe</p>";
        }
        
        // Verificar tabela group_members
        $stmt = $db->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabela 'group_members' existe</p>";
        } else {
            echo "<p class='error'>❌ Tabela 'group_members' não existe</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Teste de Filtro de Profanidade</h2>";
    $testName = "familia guedes";
    $testDesc = "receitas tops";
    
    echo "<p>Testando nome: <strong>$testName</strong></p>";
    $nameValidation = validateGroupName($testName);
    if ($nameValidation['isValid']) {
        echo "<p class='success'>✅ Nome válido</p>";
    } else {
        echo "<p class='error'>❌ Nome inválido: " . implode(', ', $nameValidation['errors']) . "</p>";
    }
    
    echo "<p>Testando descrição: <strong>$testDesc</strong></p>";
    $descCheck = checkProfanity($testDesc);
    if ($descCheck['isClean']) {
        echo "<p class='success'>✅ Descrição válida</p>";
    } else {
        echo "<p class='error'>❌ Descrição inválida: contém palavras inadequadas</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Verificar Sessões Ativas</h2>";
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT s.session_token, s.user_id, u.username, s.created_at, s.expires_at
            FROM sessions s
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.expires_at IS NULL OR s.expires_at > NOW()
            ORDER BY s.created_at DESC
            LIMIT 5
        ");
        $sessions = $stmt->fetchAll();
        
        if (count($sessions) > 0) {
            echo "<p class='success'>✅ Encontradas " . count($sessions) . " sessões ativas</p>";
            echo "<pre>";
            foreach ($sessions as $sess) {
                echo "User: {$sess['username']} (ID: {$sess['user_id']})\n";
                echo "Token: " . substr($sess['session_token'], 0, 20) . "...\n";
                echo "Criada em: {$sess['created_at']}\n";
                echo "Expira em: " . ($sess['expires_at'] ?? 'Nunca') . "\n";
                echo "---\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='error'>❌ Nenhuma sessão ativa encontrada</p>";
            echo "<p>⚠️ Você precisa fazer login primeiro em: <a href='login.html'>login.html</a></p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>4. Listar Grupos Existentes</h2>";
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT g.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
            FROM `groups` g
            LEFT JOIN users u ON g.created_by = u.id
            ORDER BY g.created_at DESC
            LIMIT 10
        ");
        $groups = $stmt->fetchAll();
        
        if (count($groups) > 0) {
            echo "<p class='success'>✅ Encontrados " . count($groups) . " grupos</p>";
            echo "<pre>";
            foreach ($groups as $group) {
                echo "ID: {$group['id']}\n";
                echo "Nome: {$group['name']}\n";
                echo "Descrição: {$group['description']}\n";
                echo "Criado por: {$group['created_by_name']} (ID: {$group['created_by']})\n";
                echo "Membros: {$group['member_count']}\n";
                echo "Criado em: {$group['created_at']}\n";
                echo "---\n";
            }
            echo "</pre>";
        } else {
            echo "<p>ℹ️ Nenhum grupo criado ainda</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>5. Teste Manual de Criação de Grupo</h2>";
    echo "<p>Para testar manualmente, use a página de teste interativa:</p>";
    echo "<p><a href='test-criar-grupo-simples.html' style='color: #ff6b35; font-weight: bold;'>Abrir Página de Teste</a></p>";
    echo "</div>";
    ?>
    
    <div class='section'>
        <h2>6. Logs de Erro do PHP</h2>
        <p>Verifique também os logs do WAMP/Apache para erros detalhados.</p>
        <p>Localização típica: <code>c:\wamp64\logs\php_error.log</code></p>
    </div>
</body>
</html>
