<?php
// ============================================
// Debug do Sistema de Ratings
// Verifica o que pode estar a causar problemas
// ============================================

require_once '../api/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt'>
<head>
    <meta charset='UTF-8'>
    <title>Debug - Sistema de Ratings</title>
    <style>
        body { font-family: Arial; max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #f5f5f5; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .section { background: white; padding: 1.5rem; margin: 1rem 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        pre { background: #f0f0f0; padding: 1rem; border-radius: 4px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #ff6b35; padding-bottom: 0.5rem; }
    </style>
</head>
<body>
    <h1>🔍 Debug do Sistema de Ratings</h1>
";

try {
    $db = Database::getInstance()->getConnection();
    
    // ===== TESTE 1: Verificar Tabelas =====
    echo "<div class='section'>";
    echo "<h2>1. Verificar Tabelas</h2>";
    
    $tables = ['recipe_ratings', 'recipe_comments', 'user_infractions'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='ok'>✓ Tabela '$table' existe</p>";
        } else {
            echo "<p class='error'>✗ Tabela '$table' NÃO existe</p>";
            echo "<p>Execute: <a href='../setup/install_ratings.php'>install_ratings.php</a></p>";
        }
    }
    echo "</div>";
    
    // ===== TESTE 2: Verificar Receitas =====
    echo "<div class='section'>";
    echo "<h2>2. Receitas na Base de Dados</h2>";
    
    $stmt = $db->query("SELECT id, title, author_id FROM recipes ORDER BY id LIMIT 10");
    $recipes = $stmt->fetchAll();
    
    if (count($recipes) > 0) {
        echo "<p class='ok'>✓ Encontradas " . count($recipes) . " receitas</p>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th style='padding: 8px; text-align: left;'>ID</th><th style='padding: 8px; text-align: left;'>Título</th><th style='padding: 8px; text-align: left;'>URL para Testar</th></tr>";
        foreach ($recipes as $recipe) {
            echo "<tr style='border-bottom: 1px solid #ddd;'>";
            echo "<td style='padding: 8px;'>{$recipe['id']}</td>";
            echo "<td style='padding: 8px;'>{$recipe['title']}</td>";
            echo "<td style='padding: 8px;'><a href='../pages/receita-detalhes.html?id={$recipe['id']}' target='_blank'>Ver receita</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ Nenhuma receita encontrada!</p>";
        echo "<p>Crie uma receita primeiro em: <a href='../pages/explorar-receitas.html'>Explorar Receitas</a></p>";
    }
    echo "</div>";
    
    // ===== TESTE 3: Testar API Diretamente =====
    echo "<div class='section'>";
    echo "<h2>3. Testar API</h2>";
    
    if (count($recipes) > 0) {
        $testRecipeId = $recipes[0]['id'];
        echo "<p>Testando API com receita ID: <strong>$testRecipeId</strong></p>";
        
        // Fazer request à API
        $apiUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../api/ratings.php?recipe_id=$testRecipeId";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json',
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && isset($data['success']) && $data['success']) {
                echo "<p class='ok'>✓ API responde corretamente!</p>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            } else {
                echo "<p class='error'>✗ API retornou erro:</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<p class='error'>✗ Não foi possível contactar a API</p>";
            echo "<p>URL tentado: <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";
        }
    } else {
        echo "<p class='warning'>⚠ Crie uma receita primeiro para testar a API</p>";
    }
    echo "</div>";
    
    // ===== TESTE 4: Verificar Sessões =====
    echo "<div class='section'>";
    echo "<h2>4. Sessões Ativas</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM sessions WHERE expires_at IS NULL OR expires_at > NOW()");
    $sessionsCount = $stmt->fetch()['total'];
    
    if ($sessionsCount > 0) {
        echo "<p class='ok'>✓ Existem $sessionsCount sessões ativas</p>";
    } else {
        echo "<p class='warning'>⚠ Nenhuma sessão ativa. Faça login primeiro!</p>";
        echo "<p><a href='../login.html'>Ir para Login</a></p>";
    }
    echo "</div>";
    
    // ===== TESTE 5: Verificar Dados Existentes =====
    echo "<div class='section'>";
    echo "<h2>5. Dados Existentes</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM recipe_ratings");
    $ratingsCount = $stmt->fetch()['count'];
    echo "<p>Avaliações: <strong>$ratingsCount</strong></p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM recipe_comments");
    $commentsCount = $stmt->fetch()['count'];
    echo "<p>Comentários: <strong>$commentsCount</strong></p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM user_infractions");
    $infractionsCount = $stmt->fetch()['count'];
    echo "<p>Infrações: <strong>$infractionsCount</strong></p>";
    echo "</div>";
    
    // ===== INSTRUÇÕES =====
    echo "<div class='section'>";
    echo "<h2>📋 Próximos Passos</h2>";
    
    if (count($recipes) > 0) {
        $firstRecipeId = $recipes[0]['id'];
        echo "<ol>";
        echo "<li>✅ Tabelas criadas</li>";
        echo "<li>✅ Receitas encontradas</li>";
        echo "<li><strong>Faça login:</strong> <a href='../login.html'>login.html</a></li>";
        echo "<li><strong>Abra uma receita:</strong> <a href='../pages/receita-detalhes.html?id=$firstRecipeId' target='_blank'>receita-detalhes.html?id=$firstRecipeId</a></li>";
        echo "<li><strong>Verás o sistema de ratings no final da página!</strong></li>";
        echo "</ol>";
    } else {
        echo "<ol>";
        echo "<li>Crie uma receita: <a href='../pages/explorar-receitas.html'>Explorar Receitas</a></li>";
        echo "<li>Depois volte aqui e execute este debug novamente</li>";
        echo "</ol>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Erro de Conexão</h2>";
    echo "<p>Não foi possível conectar à base de dados:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
