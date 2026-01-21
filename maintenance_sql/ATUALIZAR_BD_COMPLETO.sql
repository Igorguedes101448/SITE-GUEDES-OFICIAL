-- ============================================
-- ChefGuedes - Script de Atualização Completo
-- Execute este ficheiro no phpMyAdmin ou MySQL
-- ============================================

USE siteguedes;

-- Mostrar progresso
SELECT '1/5 - Atualizando tabela users...' AS Progresso;

-- Adicionar user_code se não existir (ignorar erro se já existir)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'siteguedes' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'user_code') = 0,
    'ALTER TABLE users ADD COLUMN user_code VARCHAR(6) UNIQUE AFTER id',
    'SELECT "user_code já existe" AS info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Gerar códigos para utilizadores existentes
UPDATE users 
SET user_code = UPPER(CONCAT(
    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(1 + RAND() * 32), 1),
    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(1 + RAND() * 32), 1),
    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(1 + RAND() * 32), 1),
    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(1 + RAND() * 32), 1),
    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(1 + RAND() * 32), 1),
    SUBSTRING('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', FLOOR(1 + RAND() * 32), 1)
))
WHERE user_code IS NULL OR user_code = '';

SELECT '2/5 - Atualizando tabela recipes...' AS Progresso;

-- Adicionar novos campos à tabela recipes (se não existirem)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'siteguedes' 
     AND TABLE_NAME = 'recipes' 
     AND COLUMN_NAME = 'is_draft') = 0,
    'ALTER TABLE recipes ADD COLUMN is_draft BOOLEAN DEFAULT FALSE AFTER author_id',
    'SELECT "is_draft já existe" AS info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'siteguedes' 
     AND TABLE_NAME = 'recipes' 
     AND COLUMN_NAME = 'visibility') = 0,
    'ALTER TABLE recipes ADD COLUMN visibility ENUM("public", "private", "friends") DEFAULT "public" AFTER is_draft',
    'SELECT "visibility já existe" AS info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'siteguedes' 
     AND TABLE_NAME = 'recipes' 
     AND COLUMN_NAME = 'subcategory') = 0,
    'ALTER TABLE recipes ADD COLUMN subcategory VARCHAR(50) AFTER category',
    'SELECT "subcategory já existe" AS info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índices para melhor performance (se não existirem)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'siteguedes' 
     AND TABLE_NAME = 'recipes' 
     AND INDEX_NAME = 'idx_draft') = 0,
    'CREATE INDEX idx_draft ON recipes(is_draft)',
    'SELECT "idx_draft já existe" AS info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'siteguedes' 
     AND TABLE_NAME = 'recipes' 
     AND INDEX_NAME = 'idx_visibility') = 0,
    'CREATE INDEX idx_visibility ON recipes(visibility)',
    'SELECT "idx_visibility já existe" AS info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'siteguedes' 
     AND TABLE_NAME = 'recipes' 
     AND INDEX_NAME = 'idx_subcategory') = 0,
    'CREATE INDEX idx_subcategory ON recipes(subcategory)',
    'SELECT "idx_subcategory já existe" AS info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT '3/5 - Criando tabela de notificações...' AS Progresso;

-- Criar tabela de notificações
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('friend_request', 'group_invite', 'recipe_share', 'comment', 'like', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(500),
    sender_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '4/5 - Criando sistema de amizades...' AS Progresso;

-- Criar tabela de pedidos de amizade
CREATE TABLE IF NOT EXISTS friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_request (sender_id, receiver_id),
    INDEX idx_receiver_status (receiver_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de amizades
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user1_id, user2_id),
    INDEX idx_user1 (user1_id),
    INDEX idx_user2 (user2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '5/5 - Criando sistema de partilhas...' AS Progresso;

-- Criar tabela de partilhas de receitas
CREATE TABLE IF NOT EXISTS recipe_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_id INT NOT NULL,
    shared_by INT NOT NULL,
    shared_with INT NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id),
    INDEX idx_shared_with (shared_with)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensagem final
SELECT '✅ Base de dados atualizada com sucesso!' AS Status;
SELECT 'Todas as novas funcionalidades foram instaladas.' AS Info;
