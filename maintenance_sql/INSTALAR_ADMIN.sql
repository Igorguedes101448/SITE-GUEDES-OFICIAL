-- ============================================
-- ChefGuedes - Script de Instalação Admin
-- VERSÃO SIMPLIFICADA
-- Execute este ficheiro no phpMyAdmin
-- ============================================
-- NOTA: Se alguma coluna já existir, verá um erro.
--       Pode ignorar esses erros com segurança.
-- ============================================

USE siteguedes;

-- Adicionar colunas de administração à tabela users
ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE AFTER password;
ALTER TABLE users ADD COLUMN banned BOOLEAN DEFAULT FALSE AFTER is_admin;
ALTER TABLE users ADD COLUMN banned_reason TEXT NULL AFTER banned;
ALTER TABLE users ADD COLUMN suspended_until TIMESTAMP NULL AFTER banned_reason;
ALTER TABLE users ADD COLUMN warning_count INT DEFAULT 0 AFTER suspended_until;

-- Adicionar índice
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

-- Mensagens de sucesso
SELECT '✅ Base de dados atualizada com sucesso!' AS Status;
SELECT 'Próximo passo: Acesse setup-admin.html para criar a conta admin' AS Proxima_Acao;
