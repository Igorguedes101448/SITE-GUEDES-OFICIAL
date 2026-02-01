<?php
// ============================================
// ChefGuedes - Teste do Sistema de Ratings
// Verifica se todas as tabelas e triggers foram criados
// ============================================

require_once '../api/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Sistema de Ratings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #ff6b35, #f7b32b);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .test-section {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #2a9d8f;
            font-weight: bold;
        }
        .success::before {
            content: "✓ ";
        }
        .error {
            color: #e63946;
            font-weight: bold;
        }
        .error::before {
            content: "✗ ";
        }
        .warning {
            color: #f77f00;
            font-weight: bold;
        }
        .warning::before {
            content: "⚠ ";
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .info-box {
            background: #e3f2fd;
            padding: 1rem;
            border-left: 4px solid #2196f3;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 Teste do Sistema de Avaliações e Comentários</h1>
        <p>Verificação completa da instalação</p>
    </div>

    <?php
    try {
        $db = Database::getInstance()->getConnection();
        $allPassed = true;

        // ============================================
        // TESTE 1: Verificar Tabelas
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>1. Verificação de Tabelas</h2>';
        
        $requiredTables = ['recipe_ratings', 'recipe_comments', 'user_infractions'];
        
        foreach ($requiredTables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>Tabela '$table' existe</p>";
            } else {
                echo "<p class='error'>Tabela '$table' NÃO existe</p>";
                $allPassed = false;
            }
        }
        echo '</div>';

        // ============================================
        // TESTE 2: Verificar Colunas na Tabela Recipes
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>2. Colunas Adicionadas à Tabela Recipes</h2>';
        
        $stmt = $db->query("SHOW COLUMNS FROM recipes LIKE 'average_rating'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>Coluna 'average_rating' existe na tabela recipes</p>";
        } else {
            echo "<p class='error'>Coluna 'average_rating' NÃO existe</p>";
            $allPassed = false;
        }
        
        $stmt = $db->query("SHOW COLUMNS FROM recipes LIKE 'total_ratings'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>Coluna 'total_ratings' existe na tabela recipes</p>";
        } else {
            echo "<p class='error'>Coluna 'total_ratings' NÃO existe</p>";
            $allPassed = false;
        }
        echo '</div>';

        // ============================================
        // TESTE 3: Verificar Índices
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>3. Índices para Performance</h2>';
        
        $indices = [
            'recipe_ratings' => ['idx_recipe_ratings_recipe_id', 'idx_recipe_ratings_user_id'],
            'recipe_comments' => ['idx_recipe_comments_recipe_id', 'idx_recipe_comments_user_id'],
            'user_infractions' => ['idx_user_infractions_user_id', 'idx_user_infractions_created_at']
        ];
        
        foreach ($indices as $table => $indexList) {
            $stmt = $db->query("SHOW INDEX FROM $table");
            $existingIndices = $stmt->fetchAll(PDO::FETCH_COLUMN, 2);
            
            foreach ($indexList as $index) {
                if (in_array($index, $existingIndices)) {
                    echo "<p class='success'>Índice '$index' existe em '$table'</p>";
                } else {
                    echo "<p class='warning'>Índice '$index' não existe em '$table' (opcional)</p>";
                }
            }
        }
        echo '</div>';

        // ============================================
        // TESTE 4: Verificar Triggers
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>4. Triggers Automáticos</h2>';
        
        $triggers = [
            'update_rating_after_insert',
            'update_rating_after_update',
            'update_rating_after_delete'
        ];
        
        $stmt = $db->query("SHOW TRIGGERS WHERE `Table` = 'recipe_ratings'");
        $existingTriggers = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        foreach ($triggers as $trigger) {
            if (in_array($trigger, $existingTriggers)) {
                echo "<p class='success'>Trigger '$trigger' existe</p>";
            } else {
                echo "<p class='error'>Trigger '$trigger' NÃO existe</p>";
                $allPassed = false;
            }
        }
        echo '</div>';

        // ============================================
        // TESTE 5: Estrutura das Tabelas
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>5. Estrutura das Tabelas</h2>';
        
        // recipe_ratings
        echo '<h3>recipe_ratings</h3>';
        echo '<table>';
        echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Extra</th></tr>';
        $stmt = $db->query("DESCRIBE recipe_ratings");
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo '</table>';
        
        // recipe_comments
        echo '<h3>recipe_comments</h3>';
        echo '<table>';
        echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Extra</th></tr>';
        $stmt = $db->query("DESCRIBE recipe_comments");
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo '</table>';
        
        // user_infractions
        echo '<h3>user_infractions</h3>';
        echo '<table>';
        echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Extra</th></tr>';
        $stmt = $db->query("DESCRIBE user_infractions");
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo '</table>';
        echo '</div>';

        // ============================================
        // TESTE 6: Contar Registros Existentes
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>6. Estatísticas de Dados</h2>';
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM recipe_ratings");
        $count = $stmt->fetch()['count'];
        echo "<p>Total de avaliações: <strong>$count</strong></p>";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM recipe_comments");
        $count = $stmt->fetch()['count'];
        echo "<p>Total de comentários: <strong>$count</strong></p>";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM user_infractions");
        $count = $stmt->fetch()['count'];
        echo "<p>Total de infrações: <strong>$count</strong></p>";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM recipes WHERE average_rating > 0");
        $count = $stmt->fetch()['count'];
        echo "<p>Receitas com avaliações: <strong>$count</strong></p>";
        echo '</div>';

        // ============================================
        // TESTE 7: Verificar API
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>7. Ficheiros da API</h2>';
        
        $apiFiles = [
            '../api/ratings.php' => 'API de Ratings e Comentários',
            '../js/ratings.js' => 'Script JavaScript',
            '../setup/install_ratings.php' => 'Script de Instalação'
        ];
        
        foreach ($apiFiles as $file => $description) {
            if (file_exists($file)) {
                $size = filesize($file);
                echo "<p class='success'>$description ($file) - " . number_format($size) . " bytes</p>";
            } else {
                echo "<p class='error'>$description ($file) NÃO existe</p>";
                $allPassed = false;
            }
        }
        echo '</div>';

        // ============================================
        // RESULTADO FINAL
        // ============================================
        echo '<div class="test-section">';
        if ($allPassed) {
            echo '<h2 style="color: #2a9d8f;">✓ TODOS OS TESTES PASSARAM!</h2>';
            echo '<div class="info-box">';
            echo '<p><strong>Sistema pronto para uso!</strong></p>';
            echo '<p>Próximos passos:</p>';
            echo '<ol>';
            echo '<li>Integrar o sistema nas páginas de receitas</li>';
            echo '<li>Adicionar Font Awesome para os ícones de estrelas</li>';
            echo '<li>Incluir o script ratings.js</li>';
            echo '<li>Inicializar com RatingsUI</li>';
            echo '</ol>';
            echo '<p>Consultar: <a href="../docs/INSTALACAO_RAPIDA_RATINGS.md">INSTALACAO_RAPIDA_RATINGS.md</a></p>';
            echo '</div>';
        } else {
            echo '<h2 style="color: #e63946;">✗ ALGUNS TESTES FALHARAM</h2>';
            echo '<div class="info-box">';
            echo '<p><strong>Execute o instalador:</strong></p>';
            echo '<p><a href="../setup/install_ratings.php">install_ratings.php</a></p>';
            echo '</div>';
        }
        echo '</div>';

    } catch (PDOException $e) {
        echo '<div class="test-section">';
        echo '<h2 style="color: #e63946;">Erro de Conexão</h2>';
        echo '<p class="error">Não foi possível conectar à base de dados</p>';
        echo '<p>Erro: ' . $e->getMessage() . '</p>';
        echo '</div>';
    }
    ?>

</body>
</html>
