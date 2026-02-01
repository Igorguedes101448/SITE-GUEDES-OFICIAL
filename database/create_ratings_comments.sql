-- ============================================
-- ChefGuedes - Tabelas para Avaliações e Comentários
-- Sistema de avaliação por estrelas e comentários
-- ============================================

-- Tabela de Avaliações
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Comentários
CREATE TABLE IF NOT EXISTS recipe_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar colunas na tabela recipes para armazenar média e total de avaliações
-- Nota: Se as colunas já existirem, este comando dará erro (pode ignorar)
ALTER TABLE recipes 
ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN total_ratings INT DEFAULT 0;

-- Índices para melhor performance
CREATE INDEX idx_recipe_ratings_recipe_id ON recipe_ratings(recipe_id);
CREATE INDEX idx_recipe_ratings_user_id ON recipe_ratings(user_id);
CREATE INDEX idx_recipe_comments_recipe_id ON recipe_comments(recipe_id);
CREATE INDEX idx_recipe_comments_user_id ON recipe_comments(user_id);
CREATE INDEX idx_recipes_rating ON recipes(average_rating);

-- Trigger para atualizar a média de avaliações automaticamente ao inserir
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_rating_after_insert
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
END//
DELIMITER ;

-- Trigger para atualizar a média de avaliações automaticamente ao atualizar
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_rating_after_update
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
END//
DELIMITER ;

-- Trigger para atualizar a média de avaliações automaticamente ao deletar
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_rating_after_delete
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
END//
DELIMITER ;

-- Tabela para rastrear infrações de utilizadores
CREATE TABLE IF NOT EXISTS user_infractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    infraction_type ENUM('profanity_comment', 'profanity_recipe', 'spam', 'harassment', 'other') DEFAULT 'profanity_comment',
    infraction_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_user_infractions_user_id ON user_infractions(user_id);
CREATE INDEX idx_user_infractions_created_at ON user_infractions(created_at);
