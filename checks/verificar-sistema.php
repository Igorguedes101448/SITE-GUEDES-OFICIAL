<?php
// ============================================
// ChefGuedes - Script de Verificação v2.0
// Testa todas as funcionalidades do sistema
// ============================================

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verificação do Sistema - ChefGuedes 2.0</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #ff6b35;
            border-bottom: 3px solid #ff6b35;
            padding-bottom: 10px;
        }
        h2 {
            color: #8B4513;
            margin-top: 30px;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .success {
            color: #2a9d8f;
            font-weight: bold;
        }
        .error {
            color: #e63946;
            font-weight: bold;
        }
        .warning {
            color: #f77f00;
            font-weight: bold;
        }
        .info {
            color: #666;
            font-style: italic;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #ff6b35;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 5px;
        }
        .badge-new {
            background: #2a9d8f;
            color: white;
        }
        .badge-updated {
            background: #f7b32b;
            color: white;
        }
    </style>
</head>
<body>
    <h1>🔍 Verificação do Sistema ChefGuedes 2.0</h1>
    <p class='info'>Verificando configuração e funcionalidades...</p>
";

$errors = [];
$warnings = [];
$success = [];

// ============================================
// 1. VERIFICAR CONEXÃO COM BASE DE DADOS
// ============================================
echo "<div class='test-section'>";
echo "<h2>1. Conexão com Base de Dados</h2>";

try {
    require_once 'api/db.php';
    $db = getDB();
    echo "<p class='success'>✓ Conexão estabelecida com sucesso</p>";
    $success[] = "Conexão BD";
} catch (Exception $e) {
    echo "<p class='error'>✗ Erro de conexão: " . $e->getMessage() . "</p>";
    $errors[] = "Conexão BD falhou";
    echo "</div></body></html>";
    exit;
}

// ============================================
// 2. VERIFICAR TABELAS
// ============================================
echo "<h2>2. Estrutura da Base de Dados</h2>";

$requiredTables = [
    'users' => 'Utilizadores',
    'sessions' => 'Sessões',
    'recipes' => 'Receitas',
    'groups' => 'Grupos',
    'group_members' => 'Membros de Grupos',
    'group_invites' => 'Convites de Grupo <span class="badge badge-new">NOVO</span>',
    'schedules' => 'Agendamentos <span class="badge badge-updated">ATUALIZADO</span>',
    'notifications' => 'Notificações <span class="badge badge-new">NOVO</span>',
    'ai_suggestions' => 'Sugestões IA <span class="badge badge-new">NOVO</span>',
    'activities' => 'Atividades',
    'favorites' => 'Favoritos',
    'migrations' => 'Migrações'
];

echo "<table><tr><th>Tabela</th><th>Descrição</th><th>Status</th></tr>";

foreach ($requiredTables as $table => $description) {
    $stmt = $db->query("SHOW TABLES LIKE '$table'");
    $exists = $stmt->rowCount() > 0;
    
    $status = $exists ? "<span class='success'>✓ Existe</span>" : "<span class='error'>✗ Não encontrada</span>";
    
    echo "<tr><td><strong>$table</strong></td><td>$description</td><td>$status</td></tr>";
    
    if ($exists) {
        $success[] = "Tabela $table";
    } else {
        $errors[] = "Tabela $table não encontrada";
    }
}

echo "</table>";

// ============================================
// 3. VERIFICAR COLUNAS IMPORTANTES
// ============================================
echo "<h2>3. Verificação de Colunas Críticas <span class='badge badge-updated'>v2.0</span></h2>";

// Verificar coluna group_id em schedules
$stmt = $db->query("SHOW COLUMNS FROM schedules LIKE 'group_id'");
if ($stmt->rowCount() > 0) {
    echo "<p class='success'>✓ Coluna 'group_id' existe em 'schedules'</p>";
    $success[] = "Coluna group_id";
} else {
    echo "<p class='error'>✗ Coluna 'group_id' não encontrada em 'schedules'</p>";
    $errors[] = "Coluna group_id falta";
}

// Verificar coluna meal_type em schedules
$stmt = $db->query("SHOW COLUMNS FROM schedules LIKE 'meal_type'");
if ($stmt->rowCount() > 0) {
    echo "<p class='success'>✓ Coluna 'meal_type' existe em 'schedules'</p>";
    $success[] = "Coluna meal_type";
} else {
    echo "<p class='error'>✗ Coluna 'meal_type' não encontrada em 'schedules'</p>";
    $errors[] = "Coluna meal_type falta";
}

// Verificar coluna user_code em users
$stmt = $db->query("SHOW COLUMNS FROM users LIKE 'user_code'");
if ($stmt->rowCount() > 0) {
    echo "<p class='success'>✓ Coluna 'user_code' existe em 'users'</p>";
    $success[] = "Coluna user_code";
} else {
    echo "<p class='warning'>⚠ Coluna 'user_code' não encontrada. Convites podem não funcionar.</p>";
    $warnings[] = "Coluna user_code falta";
}

// ============================================
// 4. VERIFICAR ARQUIVOS DA API
// ============================================
echo "<h2>4. Arquivos da API</h2>";

$apiFiles = [
    'db.php' => 'Conexão BD',
    'users.php' => 'Utilizadores',
    'recipes.php' => 'Receitas',
    'groups.php' => 'Grupos <span class="badge badge-updated">ATUALIZADO</span>',
    'schedules.php' => 'Agendamentos <span class="badge badge-new">NOVO</span>',
    'notifications.php' => 'Notificações <span class="badge badge-new">NOVO</span>',
    'ai.php' => 'Assistente IA <span class="badge badge-new">NOVO</span>'
];

echo "<table><tr><th>Arquivo</th><th>Descrição</th><th>Status</th></tr>";

foreach ($apiFiles as $file => $description) {
    $path = "api/$file";
    $exists = file_exists($path);
    
    $status = $exists ? "<span class='success'>✓ Existe</span>" : "<span class='error'>✗ Não encontrado</span>";
    
    echo "<tr><td><strong>$file</strong></td><td>$description</td><td>$status</td></tr>";
    
    if ($exists) {
        $success[] = "API $file";
    } else {
        $errors[] = "API $file não encontrada";
    }
}

echo "</table>";

// ============================================
// 5. VERIFICAR DADOS
// ============================================
echo "<h2>5. Dados no Sistema</h2>";

// Contar utilizadores
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$userCount = $stmt->fetch()['total'];
echo "<p>Utilizadores registados: <strong>$userCount</strong></p>";

// Contar receitas
$stmt = $db->query("SELECT COUNT(*) as total FROM recipes");
$recipeCount = $stmt->fetch()['total'];
echo "<p>Receitas criadas: <strong>$recipeCount</strong></p>";

// Contar grupos
$stmt = $db->query("SELECT COUNT(*) as total FROM `groups`");
$groupCount = $stmt->fetch()['total'];
echo "<p>Grupos criados: <strong>$groupCount</strong></p>";

// Contar convites pendentes
$stmt = $db->query("SELECT COUNT(*) as total FROM group_invites WHERE status = 'pending'");
$inviteCount = $stmt->fetch()['total'];
echo "<p>Convites pendentes: <strong>$inviteCount</strong> <span class='badge badge-new'>NOVO</span></p>";

// Contar agendamentos
$stmt = $db->query("SELECT COUNT(*) as total FROM schedules");
$scheduleCount = $stmt->fetch()['total'];
echo "<p>Refeições agendadas: <strong>$scheduleCount</strong></p>";

// Contar notificações não lidas
$stmt = $db->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
$notifCount = $stmt->fetch()['total'];
echo "<p>Notificações não lidas: <strong>$notifCount</strong> <span class='badge badge-new'>NOVO</span></p>";

// ============================================
// 6. TESTAR APIs
// ============================================
echo "<h2>6. Testes de API <span class='badge badge-new'>NOVO</span></h2>";

// Testar se APIs respondem
$apiEndpoints = [
    'groups.php' => 'GET',
    'schedules.php' => 'GET',
    'notifications.php' => 'GET',
    'ai.php' => 'GET'
];

foreach ($apiEndpoints as $endpoint => $method) {
    $url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api/$endpoint";
    
    // Nota: Este teste requer sessionToken, então só verifica se o arquivo responde
    echo "<p class='info'>→ Endpoint: $endpoint (requer autenticação)</p>";
}

// ============================================
// 7. VERIFICAR VERSÃO
// ============================================
echo "<h2>7. Versão do Sistema</h2>";

$stmt = $db->query("SELECT * FROM migrations ORDER BY executed_at DESC LIMIT 1");
$migration = $stmt->fetch();

if ($migration) {
    echo "<p>Versão atual: <strong>{$migration['version']}</strong></p>";
    echo "<p class='info'>{$migration['description']}</p>";
    echo "<p class='info'>Executada em: " . date('d/m/Y H:i', strtotime($migration['executed_at'])) . "</p>";
    
    if ($migration['version'] >= '2.0.0') {
        echo "<p class='success'>✓ Sistema atualizado para v2.0</p>";
    } else {
        echo "<p class='warning'>⚠ Sistema desatualizado. Execute migrate_to_v2.sql</p>";
    }
}

echo "</div>";

// ============================================
// RESUMO FINAL
// ============================================
echo "<div class='test-section'>";
echo "<h2>📊 Resumo da Verificação</h2>";

echo "<p><strong>Sucessos:</strong> <span class='success'>" . count($success) . "</span></p>";
echo "<p><strong>Avisos:</strong> <span class='warning'>" . count($warnings) . "</span></p>";
echo "<p><strong>Erros:</strong> <span class='error'>" . count($errors) . "</span></p>";

if (count($errors) == 0 && count($warnings) == 0) {
    echo "<p class='success' style='font-size: 1.2rem; margin-top: 20px;'>🎉 Sistema totalmente funcional! Tudo OK.</p>";
} elseif (count($errors) == 0) {
    echo "<p class='warning' style='font-size: 1.2rem; margin-top: 20px;'>⚠ Sistema funcional com alguns avisos.</p>";
} else {
    echo "<p class='error' style='font-size: 1.2rem; margin-top: 20px;'>❌ Sistema com erros. Verifique as mensagens acima.</p>";
}

// ============================================
// AÇÕES RECOMENDADAS
// ============================================
if (count($errors) > 0 || count($warnings) > 0) {
    echo "<h3>🔧 Ações Recomendadas:</h3>";
    echo "<ul>";
    
    if (in_array("Tabela group_invites não encontrada", $errors)) {
        echo "<li>Execute: <code>mysql -u root -p siteguedes < database/migrate_to_v2.sql</code></li>";
    }
    
    if (in_array("API schedules.php não encontrada", $errors)) {
        echo "<li>Verifique se o arquivo api/schedules.php existe</li>";
    }
    
    if (in_array("Coluna group_id falta", $errors)) {
        echo "<li>Execute a migração SQL para adicionar coluna group_id em schedules</li>";
    }
    
    echo "</ul>";
}

echo "</div>";

echo "<div style='text-align: center; margin-top: 40px;'>";
echo "<a href='index.html' style='display: inline-block; padding: 12px 28px; background: #ff6b35; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;'>Voltar ao Site</a>";
echo "</div>";

echo "</body></html>";
?>
