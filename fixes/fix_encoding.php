<?php
// ============================================
// Correção de Encoding UTF-8 nas Receitas
// Corrige caracteres ?? para acentos corretos
// ============================================

require_once 'api/db.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>
<html lang='pt'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Correção de Encoding UTF-8</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #2c3e50; border-bottom: 3px solid #e74c3c; padding-bottom: 10px; }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .recipe {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #3498db;
            background: #f9f9f9;
        }
        .fixed {
            border-left-color: #27ae60;
            background: #eafaf1;
        }
        .error {
            border-left-color: #e74c3c;
            background: #fadbd8;
        }
        .old { color: #e74c3c; text-decoration: line-through; }
        .new { color: #27ae60; font-weight: bold; }
        button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px 0;
        }
        button:hover { background: #c0392b; }
        .stats {
            background: #3498db;
            color: white;
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <h1>🔧 Correção de Encoding UTF-8</h1>";

try {
    $db = getDB();
    
    // Verificar receitas com problemas
    echo "<div class='section'>
        <h2>📋 Verificando Receitas...</h2>";
    
    $stmt = $db->query("SELECT id, title, description, ingredients, instructions FROM recipes");
    $recipes = $stmt->fetchAll();
    
    $problemRecipes = [];
    
    foreach ($recipes as $recipe) {
        $hasIssue = false;
        $issues = [];
        
        // Verificar se tem ?? no título
        if (strpos($recipe['title'], '?') !== false) {
            $hasIssue = true;
            $issues[] = 'title';
        }
        if (strpos($recipe['description'], '?') !== false) {
            $hasIssue = true;
            $issues[] = 'description';
        }
        if (strpos($recipe['ingredients'], '?') !== false) {
            $hasIssue = true;
            $issues[] = 'ingredients';
        }
        if (strpos($recipe['instructions'], '?') !== false) {
            $hasIssue = true;
            $issues[] = 'instructions';
        }
        
        if ($hasIssue) {
            $problemRecipes[] = [
                'id' => $recipe['id'],
                'title' => $recipe['title'],
                'description' => $recipe['description'],
                'ingredients' => $recipe['ingredients'],
                'instructions' => $recipe['instructions'],
                'issues' => $issues
            ];
            
            echo "<div class='recipe error'>
                <strong>ID {$recipe['id']}: {$recipe['title']}</strong><br>
                <small>Campos com problema: " . implode(', ', $issues) . "</small>
            </div>";
        }
    }
    
    echo "<p><strong>Total de receitas com problemas:</strong> " . count($problemRecipes) . "</p>";
    echo "</div>";
    
    // Botão para corrigir
    if (count($problemRecipes) > 0 && !isset($_GET['fix'])) {
        echo "<div class='section'>
            <h2>⚠️ Ação Necessária</h2>
            <p>Foram encontradas <strong>" . count($problemRecipes) . " receitas</strong> com problemas de encoding.</p>
            <a href='?fix=1'><button>🔧 Corrigir Todas as Receitas</button></a>
        </div>";
    }
    
    // Executar correção
    if (isset($_GET['fix'])) {
        echo "<div class='section'>
            <h2>🔧 Corrigindo Receitas...</h2>";
        
        $fixed = 0;
        $errors = 0;
        
        // Mapeamento de caracteres corrompidos para corretos
        $fixes = [
            'Ã¡' => 'á',
            'Ã ' => 'à',
            'Ã¢' => 'â',
            'Ã£' => 'ã',
            'Ã©' => 'é',
            'Ãª' => 'ê',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ã´' => 'ô',
            'Ãµ' => 'õ',
            'Ãº' => 'ú',
            'Ã§' => 'ç',
            'Ã‡' => 'Ç',
            'Ã' => 'Á',
            'Ã€' => 'À',
            'Ã‚' => 'Â',
            'Ãƒ' => 'Ã',
            'Ã‰' => 'É',
            'ÃŠ' => 'Ê',
            'Ã' => 'Í',
            'Ã"' => 'Ó',
            'Ã"' => 'Ô',
            'Ã•' => 'Õ',
            'Ãš' => 'Ú',
            '??' => 'à', // fallback comum
            '??' => 'á'  // fallback comum
        ];
        
        foreach ($problemRecipes as $recipe) {
            try {
                $newTitle = $recipe['title'];
                $newDesc = $recipe['description'];
                $newIngredients = $recipe['ingredients'];
                $newInstructions = $recipe['instructions'];
                
                // Aplicar correções
                foreach ($fixes as $wrong => $correct) {
                    $newTitle = str_replace($wrong, $correct, $newTitle);
                    $newDesc = str_replace($wrong, $correct, $newDesc);
                    $newIngredients = str_replace($wrong, $correct, $newIngredients);
                    $newInstructions = str_replace($wrong, $correct, $newInstructions);
                }
                
                // Atualizar na base de dados
                $stmt = $db->prepare("
                    UPDATE recipes 
                    SET title = ?, description = ?, ingredients = ?, instructions = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $newTitle,
                    $newDesc,
                    $newIngredients,
                    $newInstructions,
                    $recipe['id']
                ]);
                
                echo "<div class='recipe fixed'>
                    <strong>✅ ID {$recipe['id']} corrigido</strong><br>
                    <span class='old'>{$recipe['title']}</span><br>
                    <span class='new'>{$newTitle}</span>
                </div>";
                
                $fixed++;
                
            } catch (Exception $e) {
                echo "<div class='recipe error'>
                    <strong>❌ Erro ao corrigir ID {$recipe['id']}</strong><br>
                    {$e->getMessage()}
                </div>";
                $errors++;
            }
        }
        
        echo "<div class='stats'>
            <h3>📊 Resultado</h3>
            <p><strong>Receitas corrigidas:</strong> $fixed</p>
            <p><strong>Erros:</strong> $errors</p>
        </div>";
        
        echo "<a href='fix_encoding.php'><button>🔄 Verificar Novamente</button></a>";
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='section error'>
        <h2>❌ Erro</h2>
        <p>{$e->getMessage()}</p>
    </div>";
}

echo "</body></html>";
?>
