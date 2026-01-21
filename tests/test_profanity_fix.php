<?php
// ============================================
// Teste do Filtro de Profanidade Corrigido
// Verifica que nomes legítimos não são bloqueados
// ============================================

require_once 'api/profanity-filter.php';

echo "<!DOCTYPE html>
<html lang='pt'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste Filtro de Profanidade</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .test-section {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-case {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #ccc;
            background: #f9f9f9;
        }
        .pass {
            border-left-color: #27ae60;
            background: #eafaf1;
        }
        .fail {
            border-left-color: #e74c3c;
            background: #fadbd8;
        }
        .result {
            font-weight: bold;
            margin-top: 5px;
        }
        .pass .result { color: #27ae60; }
        .fail .result { color: #e74c3c; }
        .found-words {
            color: #e74c3c;
            font-style: italic;
            margin-top: 5px;
        }
        .stats {
            background: #3498db;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>🔍 Teste do Filtro de Profanidade Corrigido</h1>
    <p>Verificando que nomes legítimos de receitas não são bloqueados...</p>";

// ============================================
// TESTES DE NOMES LEGÍTIMOS (DEVEM PASSAR)
// ============================================
echo "<div class='test-section'>
    <h2>✅ Nomes Legítimos de Receitas (Devem Passar)</h2>";

$legitimateNames = [
    'Açorda Alentejana',
    'Bacalhau à Brás',
    'Caldo Verde',
    'Arroz de Pato',
    'Francesinha',
    'Pastel de Nata',
    'Feijoada à Portuguesa',
    'Cozido à Portuguesa',
    'Sardinhas Assadas',
    'Bolo de Arroz',
    'Bife à Portuguesa',
    'Sopa da Pedra',
    'Alheira de Mirandela',
    'Queijadas de Sintra',
    'Ovos Moles de Aveiro',
    'Pão de Ló',
    'Arroz Doce',
    'Leite Creme',
    'Polvo à Lagareiro',
    'Carapau Assado',
    'Salada Russa',
    'Batatas Assadas com Alecrim',
    'Pudim Flan',
    'Tarte de Maçã',
    'Biscoitos de Manteiga',
    'Compota de Morango',
    'Jardineira de Legumes',
    'Pizza Margherita',
    'Lasanha Bolonhesa',
    'Massa Carbonara'
];

$passed = 0;
$failed = 0;

foreach ($legitimateNames as $name) {
    $result = checkProfanity($name);
    $isPass = $result['isClean'];
    
    if ($isPass) {
        $passed++;
        echo "<div class='test-case pass'>";
        echo "<strong>$name</strong>";
        echo "<div class='result'>✅ PASSOU - Nome aceite</div>";
        echo "</div>";
    } else {
        $failed++;
        echo "<div class='test-case fail'>";
        echo "<strong>$name</strong>";
        echo "<div class='result'>❌ FALHOU - Nome bloqueado incorretamente</div>";
        echo "<div class='found-words'>Palavras detetadas: " . implode(', ', $result['foundWords']) . "</div>";
        echo "</div>";
    }
}

echo "</div>";

// ============================================
// TESTES DE NOMES INADEQUADOS (DEVEM FALHAR)
// ============================================
echo "<div class='test-section'>
    <h2>❌ Nomes Inadequados (Devem Ser Bloqueados)</h2>";

$inappropriateNames = [
    'Receita do caralho',
    'Bolo da puta',
    'Massa foda',
    'Merda de frango',
    'Sopa filho da puta',
    'Pizza fucking delicious',
    'Shit on toast',
    'Bloody bitch cake'
];

$blocked = 0;
$notBlocked = 0;

foreach ($inappropriateNames as $name) {
    $result = checkProfanity($name);
    $isBlocked = !$result['isClean'];
    
    if ($isBlocked) {
        $blocked++;
        echo "<div class='test-case pass'>";
        echo "<strong>$name</strong>";
        echo "<div class='result'>✅ BLOQUEADO - Conteúdo inadequado detetado</div>";
        echo "<div class='found-words'>Palavras detetadas: " . implode(', ', $result['foundWords']) . "</div>";
        echo "</div>";
    } else {
        $notBlocked++;
        echo "<div class='test-case fail'>";
        echo "<strong>$name</strong>";
        echo "<div class='result'>❌ NÃO BLOQUEADO - Deveria ter sido bloqueado</div>";
        echo "</div>";
    }
}

echo "</div>";

// ============================================
// ESTATÍSTICAS
// ============================================
$totalLegitimate = count($legitimateNames);
$totalInappropriate = count($inappropriateNames);
$legitimateRate = round(($passed / $totalLegitimate) * 100, 1);
$blockRate = round(($blocked / $totalInappropriate) * 100, 1);

echo "<div class='stats'>
    <h2>📊 Estatísticas do Teste</h2>
    <p><strong>Nomes Legítimos:</strong> $passed/$totalLegitimate aceites ($legitimateRate%)</p>
    <p><strong>Nomes Inadequados:</strong> $blocked/$totalInappropriate bloqueados ($blockRate%)</p>
    <hr style='border: 1px solid rgba(255,255,255,0.3); margin: 15px 0;'>
    <p><strong>Resultado:</strong> ";

if ($passed === $totalLegitimate && $blocked === $totalInappropriate) {
    echo "🎉 <span style='font-size: 1.2em;'>TODOS OS TESTES PASSARAM!</span>";
} else {
    echo "⚠️ Alguns testes falharam. Reveja o filtro.";
}

echo "</p>
</div>

<div class='test-section'>
    <h2>📝 Resumo das Correções</h2>
    <ul>
        <li><strong>Removidas palavras curtas:</strong> 'cu', 'ass', 'pisa', 'pissa', 'rabo', 'puto' que causavam falsos positivos</li>
        <li><strong>Removidas palavras ambíguas:</strong> 'burro', 'burra', 'negro', 'cigano', 'deficiente', 'aleijado' (contextos legítimos)</li>
        <li><strong>Removidos insultos genéricos:</strong> 'idiot', 'stupid', 'dumb', 'jerk', 'loser' (muito comuns em inglês)</li>
        <li><strong>Mantidos apenas termos inequivocamente ofensivos:</strong> Palavrões graves e insultos claros</li>
        <li><strong>Regex melhorada:</strong> Detecta apenas palavras completas com limites adequados</li>
    </ul>
</div>

</body>
</html>";
?>
