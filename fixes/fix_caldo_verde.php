<?php
// Script para corrigir a receita Caldo Verde
require_once 'api/db.php';

try {
    $db = getDB();
    
    // Ingredientes corretos
    $ingredients = "1kg de batatas
500g de couve galega
1 chouriço
1 cebola
2 dentes de alho
4 colheres de sopa de azeite
Sal q.b.
2 litros de água";

    // Instruções corretas
    $instructions = "1. Descasque e corte as batatas em pedaços
2. Numa panela grande, aqueça o azeite e refogue a cebola picada
3. Adicione o alho esmagado e refogue por 1 minuto
4. Junte as batatas e a água, tempere com sal
5. Deixe cozer até as batatas estarem muito macias (cerca de 20 min)
6. Triture as batatas com a varinha mágica até obter um puré
7. Lave a couve, retire os talos e corte em juliana muito fina
8. Adicione a couve ao caldo e coza por 5 minutos
9. Corte o chouriço em rodelas finas e frite à parte
10. Sirva o caldo bem quente com as rodelas de chouriço e um fio de azeite";
    
    // Atualizar a receita
    $stmt = $db->prepare("UPDATE recipes SET ingredients = ?, instructions = ? WHERE title LIKE '%Caldo Verde%'");
    $stmt->execute([$ingredients, $instructions]);
    
    echo "Receita Caldo Verde corrigida com sucesso!\n";
    echo "Linhas afetadas: " . $stmt->rowCount() . "\n";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
