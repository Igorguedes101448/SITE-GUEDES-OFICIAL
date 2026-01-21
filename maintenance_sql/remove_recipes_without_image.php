<?php
// Script para remover receitas sem imagem
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Deletar todas as receitas sem imagem
    $stmt = $db->prepare("DELETE FROM recipes WHERE image IS NULL OR image = ''");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "Receitas removidas: $count\n";
    echo "Operação concluída com sucesso!\n";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
