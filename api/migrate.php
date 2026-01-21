<?php
// ============================================
// ChefGuedes - Sistema de Migrações Automáticas
// Atualiza a base de dados automaticamente
// ============================================

require_once 'db.php';

class Migration {
    private $db;
    private $migrations = [];
    
    public function __construct() {
        $this->db = getDB();
        $this->defineMigrations();
    }
    
    // Definir todas as migrações
    private function defineMigrations() {
        // Migração 1.0.1 - Adicionar campos extras (exemplo)
        $this->migrations['1.0.1'] = [
            'description' => 'Adicionar campos de tempo de preparação às receitas',
            'up' => function($db) {
                // Estas colunas já existem no schema.sql inicial
                // Este é apenas um exemplo de como funciona
                return true;
            }
        ];
        
        // Migração 1.0.2 - Índices adicionais (exemplo)
        $this->migrations['1.0.2'] = [
            'description' => 'Adicionar índices para melhor performance',
            'up' => function($db) {
                // Exemplo de migração
                return true;
            }
        ];
    }
    
    // Obter versão atual da base de dados
    private function getCurrentVersion() {
        try {
            $stmt = $this->db->query("SELECT version FROM migrations ORDER BY id DESC LIMIT 1");
            $result = $stmt->fetch();
            return $result ? $result['version'] : '0.0.0';
        } catch (PDOException $e) {
            return '0.0.0';
        }
    }
    
    // Verificar se migração já foi executada
    private function isMigrationExecuted($version) {
        $stmt = $this->db->prepare("SELECT id FROM migrations WHERE version = ?");
        $stmt->execute([$version]);
        return $stmt->fetch() !== false;
    }
    
    // Executar migração
    private function executeMigration($version, $migration) {
        try {
            $this->db->beginTransaction();
            
            // Executar a migração
            $result = $migration['up']($this->db);
            
            if ($result !== false) {
                // Registar migração
                $stmt = $this->db->prepare("
                    INSERT INTO migrations (version, description) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$version, $migration['description']]);
                
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // Executar todas as migrações pendentes
    public function migrate() {
        $executed = [];
        $failed = [];
        
        foreach ($this->migrations as $version => $migration) {
            if (!$this->isMigrationExecuted($version)) {
                try {
                    if ($this->executeMigration($version, $migration)) {
                        $executed[] = $version;
                    } else {
                        $failed[] = $version;
                    }
                } catch (Exception $e) {
                    $failed[] = $version . ' - ' . $e->getMessage();
                }
            }
        }
        
        return [
            'executed' => $executed,
            'failed' => $failed,
            'current_version' => $this->getCurrentVersion()
        ];
    }
    
    // Verificar integridade da base de dados
    public function checkIntegrity() {
        $issues = [];
        
        try {
            // Verificar se todas as tabelas existem
            $requiredTables = [
                'users', 'user_preferences', 'sessions', 'recipes', 
                'favorites', 'groups', 'group_members', 'schedules', 
                'activities', 'migrations'
            ];
            
            $stmt = $this->db->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $existingTables)) {
                    $issues[] = "Tabela '$table' não existe";
                }
            }
            
            return [
                'valid' => empty($issues),
                'issues' => $issues
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'issues' => [$e->getMessage()]
            ];
        }
    }
}

// Se chamado diretamente, executar migrações
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    try {
        $migration = new Migration();
        
        // Verificar integridade
        $integrity = $migration->checkIntegrity();
        
        if (!$integrity['valid']) {
            echo "❌ Problemas encontrados na base de dados:\n";
            foreach ($integrity['issues'] as $issue) {
                echo "  - $issue\n";
            }
            echo "\n";
        }
        
        // Executar migrações
        $result = $migration->migrate();
        
        if (!empty($result['executed'])) {
            echo "✅ Migrações executadas:\n";
            foreach ($result['executed'] as $version) {
                echo "  - Versão $version\n";
            }
            echo "\n";
        }
        
        if (!empty($result['failed'])) {
            echo "❌ Migrações falhadas:\n";
            foreach ($result['failed'] as $version) {
                echo "  - $version\n";
            }
            echo "\n";
        }
        
        if (empty($result['executed']) && empty($result['failed'])) {
            echo "✅ Base de dados está atualizada (versão {$result['current_version']})\n";
        }
        
        echo "✅ Versão atual: {$result['current_version']}\n";
        
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    // Se chamado via HTTP, retornar JSON
    try {
        $migration = new Migration();
        $result = $migration->migrate();
        $integrity = $migration->checkIntegrity();
        
        jsonSuccess('Migração concluída.', [
            'result' => $result,
            'integrity' => $integrity
        ]);
    } catch (Exception $e) {
        jsonError('Erro na migração: ' . $e->getMessage(), 500);
    }
}
?>
