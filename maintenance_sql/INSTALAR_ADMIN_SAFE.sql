-- ============================================
-- ChefGuedes - Instalação Admin (VERSÃO SEGURA)
-- Este script verifica se as colunas já existem
-- ============================================

USE siteguedes;

-- Criar procedimento temporário para adicionar colunas se não existirem
DELIMITER $$

CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(100),
    IN columnName VARCHAR(100),
    IN columnDefinition VARCHAR(500)
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = tableName
        AND COLUMN_NAME = columnName
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', columnName, ' ', columnDefinition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Adicionar colunas apenas se não existirem
CALL AddColumnIfNotExists('users', 'is_admin', 'BOOLEAN DEFAULT FALSE AFTER password');
CALL AddColumnIfNotExists('users', 'banned', 'BOOLEAN DEFAULT FALSE AFTER is_admin');
CALL AddColumnIfNotExists('users', 'banned_reason', 'TEXT NULL AFTER banned');
CALL AddColumnIfNotExists('users', 'suspended_until', 'TIMESTAMP NULL AFTER banned_reason');
CALL AddColumnIfNotExists('users', 'warning_count', 'INT DEFAULT 0 AFTER suspended_until');

-- Remover procedimento temporário
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Adicionar índice (ignorar erro se já existir)
ALTER TABLE users ADD INDEX idx_is_admin (is_admin);

-- Criar tabela de ações administrativas
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type ENUM('ban', 'unban', 'suspend', 'unsuspend', 'warn', 'delete_recipe', 'restore_user') NOT NULL,
    target_user_id INT NULL,
    target_recipe_id INT NULL,
    reason TEXT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_target_user (target_user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de denúncias
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT NULL,
    reported_recipe_id INT NULL,
    report_type ENUM('spam', 'inappropriate', 'harassment', 'copyright', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    resolution_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_recipe_id) REFERENCES recipes(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reporter (reporter_id),
    INDEX idx_reported_user (reported_user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir registo de migração
INSERT INTO migrations (version, description) VALUES 
('1.1.0', 'Sistema de Administração e Moderação')
ON DUPLICATE KEY UPDATE version=version;

-- Verificar instalação
SELECT 'Estrutura criada com sucesso!' AS Status;
SELECT COUNT(*) as 'Colunas de Admin na tabela users' FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME IN ('is_admin', 'banned', 'banned_reason', 'suspended_until', 'warning_count');

SELECT '✅ Se o número acima é 5, tudo está correto!' AS Info;
SELECT 'Próximo passo: Acesse setup-admin.html para criar a conta admin' AS Proxima_Acao;
