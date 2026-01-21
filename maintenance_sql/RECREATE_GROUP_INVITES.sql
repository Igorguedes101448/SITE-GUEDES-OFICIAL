-- ============================================
-- RECRIAR tabela group_invites do zero
-- Remove a tabela antiga e cria corretamente
-- ============================================

USE siteguedes;

-- AVISO: Isto vai apagar todos os convites existentes!
-- Se tiver dados importantes, faça backup primeiro

-- 1. Remover a tabela antiga completamente
DROP TABLE IF EXISTS group_invites;

-- 2. Criar a tabela com a estrutura correta
CREATE TABLE group_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    inviter_id INT NOT NULL COMMENT 'Quem está a enviar o convite',
    invitee_id INT NOT NULL COMMENT 'Quem está a receber o convite',
    invitee_user_code VARCHAR(6) NOT NULL COMMENT 'Código do utilizador convidado',
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
    FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Índices
    UNIQUE KEY unique_invite (group_id, invitee_id),
    INDEX idx_invitee_status (invitee_id, status),
    INDEX idx_group_pending (group_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✓ Tabela group_invites recriada com sucesso!' AS resultado;
DESCRIBE group_invites;
