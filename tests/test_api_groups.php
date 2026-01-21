<?php
// Teste direto da API groups.php
require_once 'api/db.php';

echo "<h1>Teste da API groups.php</h1>";

try {
    $db = getDB();
    
    // Pegar o primeiro usuário e criar uma sessão válida
    $stmt = $db->query("SELECT id, username FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color: red;'>ERRO: Não há utilizadores na base de dados. Por favor, registe um utilizador primeiro.</p>";
        exit;
    }
    
    echo "<p>Utilizador de teste: {$user['username']} (ID: {$user['id']})</p>";
    
    // Criar uma sessão válida para este usuário
    $sessionToken = bin2hex(random_bytes(32));
    $stmt = $db->prepare("INSERT INTO sessions (user_id, session_token) VALUES (?, ?)");
    $stmt->execute([$user['id'], $sessionToken]);
    
    echo "<p>Token de sessão criado: " . substr($sessionToken, 0, 20) . "...</p>";
    
    // Simular chamada POST para criar grupo
    echo "<h2>Simulando chamada à API...</h2>";
    
    $postData = [
        'action' => 'create',
        'sessionToken' => $sessionToken,
        'name' => 'Grupo Teste API ' . time(),
        'description' => 'Grupo criado via teste direto da API'
    ];
    
    echo "<pre>Dados enviados:\n" . json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    // Fazer requisição usando cURL
    $ch = curl_init('http://localhost/siteguedes/api/groups.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h2>Resposta da API:</h2>";
    echo "<p>Código HTTP: $httpCode</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $result = json_decode($response, true);
    if ($result) {
        echo "<h3>Resposta decodificada:</h3>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        if (isset($result['success']) && $result['success']) {
            echo "<p style='color: green; font-weight: bold;'>✓ SUCESSO! Grupo criado.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>✗ ERRO: " . ($result['message'] ?? 'Mensagem não disponível') . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>EXCEÇÃO: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
