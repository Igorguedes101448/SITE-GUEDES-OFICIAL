<?php
// Teste de conexão
header('Content-Type: application/json; charset=utf-8');
require_once 'api/db.php';

try {
    $db = getDB();
    echo json_encode([
        'success' => true,
        'message' => 'Conexão com o banco de dados estabelecida com sucesso!'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
exit;
