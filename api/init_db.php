<?php
// ============================================
// ChefGuedes - Inicialização da Base de Dados
// Este script cria automaticamente a base de dados
// ============================================

// Configurações
define('DB_HOST', 'localhost');
define('DB_NAME', 'siteguedes');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Conectar sem especificar base de dados
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Ler o ficheiro SQL
    $sqlFile = __DIR__ . '/../database/schema.sql';
    
    if (!file_exists($sqlFile)) {
        die("Erro: Ficheiro schema.sql não encontrado!");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Executar o SQL
    $pdo->exec($sql);
    
    echo "✅ Base de dados criada/atualizada com sucesso!\n";
    echo "✅ Todas as tabelas foram criadas.\n";
    echo "✅ Sistema pronto para usar!\n";
    
} catch (PDOException $e) {
    die("❌ Erro: " . $e->getMessage() . "\n");
}
?>
