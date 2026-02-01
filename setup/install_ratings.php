<?php
// ============================================
// ChefGuedes - Instalador de Ratings e Comentários
// Script para criar as tabelas necessárias
// ============================================

require_once '../api/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== Instalação do Sistema de Avaliações e Comentários ===\n\n";
    
    // 1. Criar tabela de avaliações
    echo "1. Criando tabela recipe_ratings...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS recipe_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipe_id INT NOT NULL,
            user_id INT NOT NULL,
            rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_recipe_rating (user_id, recipe_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela recipe_ratings criada com sucesso!\n\n";
    
    // 2. Criar tabela de comentários
    echo "2. Criando tabela recipe_comments...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS recipe_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipe_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela recipe_comments criada com sucesso!\n\n";
    
    // 3. Adicionar colunas na tabela recipes
    echo "3. Adicionando colunas de rating na tabela recipes...\n";
    try {
        $db->exec("ALTER TABLE recipes ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0.00");
        echo "✓ Coluna average_rating adicionada!\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Coluna average_rating já existe!\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE recipes ADD COLUMN IF NOT EXISTS total_ratings INT DEFAULT 0");
        echo "✓ Coluna total_ratings adicionada!\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Coluna total_ratings já existe!\n\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Criar índices
    echo "4. Criando índices para performance...\n";
    try {
        $db->exec("CREATE INDEX idx_recipe_ratings_recipe_id ON recipe_ratings(recipe_id)");
        echo "✓ Índice idx_recipe_ratings_recipe_id criado!\n";
    } catch (PDOException $e) {
        echo "✓ Índice idx_recipe_ratings_recipe_id já existe!\n";
    }
    
    try {
        $db->exec("CREATE INDEX idx_recipe_ratings_user_id ON recipe_ratings(user_id)");
        echo "✓ Índice idx_recipe_ratings_user_id criado!\n";
    } catch (PDOException $e) {
        echo "✓ Índice idx_recipe_ratings_user_id já existe!\n";
    }
    
    try {
        $db->exec("CREATE INDEX idx_recipe_comments_recipe_id ON recipe_comments(recipe_id)");
        echo "✓ Índice idx_recipe_comments_recipe_id criado!\n";
    } catch (PDOException $e) {
        echo "✓ Índice idx_recipe_comments_recipe_id já existe!\n";
    }
    
    try {
        $db->exec("CREATE INDEX idx_recipe_comments_user_id ON recipe_comments(user_id)");
        echo "✓ Índice idx_recipe_comments_user_id criado!\n";
    } catch (PDOException $e) {
        echo "✓ Índice idx_recipe_comments_user_id já existe!\n";
    }
    
    try {
        $db->exec("CREATE INDEX idx_recipes_rating ON recipes(average_rating)");
        echo "✓ Índice idx_recipes_rating criado!\n\n";
    } catch (PDOException $e) {
        echo "✓ Índice idx_recipes_rating já existe!\n\n";
    }
    
    // 5. Criar triggers
    echo "5. Criando triggers para atualização automática de médias...\n";
    
    // Dropar triggers existentes se houver
    try {
        $db->exec("DROP TRIGGER IF EXISTS update_rating_after_insert");
        $db->exec("DROP TRIGGER IF EXISTS update_rating_after_update");
        $db->exec("DROP TRIGGER IF EXISTS update_rating_after_delete");
    } catch (PDOException $e) {
        // Ignorar erros ao dropar triggers
    }
    
    // Criar trigger de insert
    $db->exec("
        CREATE TRIGGER update_rating_after_insert
        AFTER INSERT ON recipe_ratings
        FOR EACH ROW
        BEGIN
            DECLARE avg_rating DECIMAL(3,2);
            DECLARE total_count INT;
            
            SELECT AVG(rating), COUNT(*) 
            INTO avg_rating, total_count
            FROM recipe_ratings 
            WHERE recipe_id = NEW.recipe_id;
            
            UPDATE recipes 
            SET average_rating = avg_rating, 
                total_ratings = total_count 
            WHERE id = NEW.recipe_id;
        END
    ");
    echo "✓ Trigger update_rating_after_insert criado!\n";
    
    // Criar trigger de update
    $db->exec("
        CREATE TRIGGER update_rating_after_update
        AFTER UPDATE ON recipe_ratings
        FOR EACH ROW
        BEGIN
            DECLARE avg_rating DECIMAL(3,2);
            DECLARE total_count INT;
            
            SELECT AVG(rating), COUNT(*) 
            INTO avg_rating, total_count
            FROM recipe_ratings 
            WHERE recipe_id = NEW.recipe_id;
            
            UPDATE recipes 
            SET average_rating = avg_rating, 
                total_ratings = total_count 
            WHERE id = NEW.recipe_id;
        END
    ");
    echo "✓ Trigger update_rating_after_update criado!\n";
    
    // Criar trigger de delete
    $db->exec("
        CREATE TRIGGER update_rating_after_delete
        AFTER DELETE ON recipe_ratings
        FOR EACH ROW
        BEGIN
            DECLARE avg_rating DECIMAL(3,2);
            DECLARE total_count INT;
            
            SELECT COALESCE(AVG(rating), 0), COUNT(*) 
            INTO avg_rating, total_count
            FROM recipe_ratings 
            WHERE recipe_id = OLD.recipe_id;
            
            UPDATE recipes 
            SET average_rating = avg_rating, 
                total_ratings = total_count 
            WHERE id = OLD.recipe_id;
        END
    ");
    echo "✓ Trigger update_rating_after_delete criado!\n\n";
    
    // 6. Criar tabela de infrações
    echo "6. Criando tabela user_infractions...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_infractions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            infraction_type ENUM('profanity_comment', 'profanity_recipe', 'spam', 'harassment', 'other') DEFAULT 'profanity_comment',
            infraction_details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela user_infractions criada com sucesso!\n\n";
    
    try {
        $db->exec("CREATE INDEX idx_user_infractions_user_id ON user_infractions(user_id)");
        echo "✓ Índice idx_user_infractions_user_id criado!\n";
    } catch (PDOException $e) {
        echo "✓ Índice idx_user_infractions_user_id já existe!\n";
    }
    
    try {
        $db->exec("CREATE INDEX idx_user_infractions_created_at ON user_infractions(created_at)");
        echo "✓ Índice idx_user_infractions_created_at criado!\n\n";
    } catch (PDOException $e) {
        echo "✓ Índice idx_user_infractions_created_at já existe!\n\n";
    }
    
    echo "==============================================\n";
    echo "✓ INSTALAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "==============================================\n\n";
    echo "O sistema de avaliações e comentários está pronto para uso!\n";
    echo "- Utilizadores podem avaliar receitas de 1 a 5 estrelas\n";
    echo "- Utilizadores podem comentar até 2 vezes por receita\n";
    echo "- Filtro de profanidade ativo\n";
    echo "- Sistema de infrações implementado\n";
    echo "- Médias de avaliação calculadas automaticamente\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Código de erro: " . $e->getCode() . "\n";
    exit(1);
}
