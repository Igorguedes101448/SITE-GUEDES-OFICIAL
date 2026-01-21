<?php
// Teste de conexão simples
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API está a funcionar!',
    'php_version' => phpversion(),
    'time' => date('Y-m-d H:i:s')
]);
?>
