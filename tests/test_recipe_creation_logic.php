<?php
// Script de teste para simular criação de receita privada
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "=== Teste de criação de receita privada ===\n\n";
    
    // Simular dados de uma receita privada
    $testData = [
        'title' => 'Teste Receita Privada',
        'category' => 'Teste',
        'description' => 'Receita de teste privada',
        'ingredients' => 'Teste',
        'instructions' => 'Teste',
        'isDraft' => true,  // Privada
        'visibility' => 'private'
    ];
    
    echo "Dados da receita:\n";
    echo "- Título: {$testData['title']}\n";
    echo "- isDraft: " . ($testData['isDraft'] ? 'true (PRIVADO)' : 'false (PÚBLICO)') . "\n";
    echo "- visibility: {$testData['visibility']}\n\n";
    
    // Verificar o que seria inserido
    $isDraftValue = $testData['isDraft'] ?? true;
    $visibilityValue = $testData['visibility'] ?? 'private';
    
    echo "Valores que seriam inseridos na BD:\n";
    echo "- is_draft: " . ($isDraftValue ? '1 (PRIVADO)' : '0 (PÚBLICO)') . "\n";
    echo "- visibility: {$visibilityValue}\n\n";
    
    // Testar cenário onde não é enviado isDraft
    echo "=== Teste sem isDraft (padrão) ===\n";
    $testData2 = [
        'title' => 'Teste sem isDraft',
        'visibility' => 'private'
    ];
    
    $isDraftValue2 = $testData2['isDraft'] ?? true;
    $visibilityValue2 = $testData2['visibility'] ?? 'private';
    
    echo "isDraft não enviado, valor padrão: " . ($isDraftValue2 ? 'true (PRIVADO)' : 'false (PÚBLICO)') . "\n";
    echo "visibility: {$visibilityValue2}\n\n";
    
    // Testar cenário pública
    echo "=== Teste receita PÚBLICA ===\n";
    $testData3 = [
        'title' => 'Teste Pública',
        'isDraft' => false,
        'visibility' => 'public'
    ];
    
    $isDraftValue3 = $testData3['isDraft'] ?? true;
    $visibilityValue3 = $testData3['visibility'] ?? 'private';
    
    echo "isDraft: " . ($isDraftValue3 ? 'true (PRIVADO)' : 'false (PÚBLICO)') . "\n";
    echo "visibility: {$visibilityValue3}\n\n";
    
    echo "✅ Testes concluídos!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
