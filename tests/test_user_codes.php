<?php
// Teste rápido para verificar códigos de utilizador
require_once 'api/db.php';

try {
    $db = getDB();
    
    echo "<h2>Verificação de Códigos de Utilizador</h2>";
    
    // Buscar todos os utilizadores
    $stmt = $db->query("SELECT id, username, email, user_code, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<p>Total de utilizadores: " . count($users) . "</p>";
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Código</th><th>Data</th></tr>";
        
        $comCodigo = 0;
        $semCodigo = 0;
        
        foreach ($users as $user) {
            $hasCodigo = !empty($user['user_code']);
            if ($hasCodigo) {
                $comCodigo++;
            } else {
                $semCodigo++;
            }
            
            $codigoDisplay = $hasCodigo ? "<strong style='color: green;'>{$user['user_code']}</strong>" : "<span style='color: red;'>SEM CÓDIGO</span>";
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$codigoDisplay}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>Resumo:</h3>";
        echo "<p>✅ Com código: <strong>{$comCodigo}</strong></p>";
        echo "<p>❌ Sem código: <strong>{$semCodigo}</strong></p>";
        
        if ($semCodigo > 0) {
            echo "<p style='color: orange;'><strong>⚠️ Execute generate_user_codes.php para gerar códigos para os utilizadores que não têm.</strong></p>";
        }
    } else {
        echo "<p>Nenhum utilizador encontrado na base de dados.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>
