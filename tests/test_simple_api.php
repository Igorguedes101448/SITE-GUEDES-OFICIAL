<?php
// Teste simples da API de avaliações
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Testando inclusão do db.php...\n";
require_once __DIR__ . '/../api/db.php';
echo "✓ db.php incluído com sucesso\n\n";

echo "2. Testando conexão à base de dados...\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Conexão estabelecida\n\n";
} catch (Exception $e) {
    die("✗ Erro na conexão: " . $e->getMessage() . "\n");
}

echo "3. Testando consulta de avaliações...\n";
try {
    $recipeId = 1;
    $stmt = $db->prepare("
        SELECT 
            AVG(rating) as average_rating,
            COUNT(*) as total_ratings,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
        FROM recipe_ratings 
        WHERE recipe_id = ?
    ");
    $stmt->execute([$recipeId]);
    $stats = $stmt->fetch();
    
    echo "✓ Consulta executada com sucesso\n";
    echo "   Resultado: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    die("✗ Erro na consulta: " . $e->getMessage() . "\n");
}

echo "4. Testando consulta de comentários...\n";
try {
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.comment,
            c.created_at,
            c.updated_at,
            u.id as user_id,
            u.username,
            u.profile_picture
        FROM recipe_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.recipe_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$recipeId]);
    $comments = $stmt->fetchAll();
    
    echo "✓ Consulta de comentários executada com sucesso\n";
    echo "   Total de comentários: " . count($comments) . "\n\n";
} catch (Exception $e) {
    die("✗ Erro na consulta: " . $e->getMessage() . "\n");
}

echo "=== TODOS OS TESTES PASSARAM ===\n";
?>
