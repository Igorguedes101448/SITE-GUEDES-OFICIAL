-- ============================================
-- Script para adicionar a coluna invitee_id à tabela group_invites
-- ============================================

USE siteguedes;

-- Verificar se a coluna invitee_id já existe
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'siteguedes'
    AND TABLE_NAME = 'group_invites'
    AND COLUMN_NAME = 'invitee_id'
);

-- Se a coluna não existir, adicionar
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE group_invites ADD COLUMN invitee_id INT NOT NULL AFTER inviter_id',
    'SELECT "Coluna invitee_id já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar foreign key se não existir
SET @fk_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'siteguedes'
    AND TABLE_NAME = 'group_invites'
    AND COLUMN_NAME = 'invitee_id'
    AND REFERENCED_TABLE_NAME = 'users'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE group_invites ADD CONSTRAINT fk_invitee FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT "Foreign key para invitee_id já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice único se não existir
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'siteguedes'
    AND TABLE_NAME = 'group_invites'
    AND INDEX_NAME = 'unique_invite'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE group_invites ADD UNIQUE KEY unique_invite (group_id, invitee_id)',
    'SELECT "Índice unique_invite já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para performance
SET @idx2_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'siteguedes'
    AND TABLE_NAME = 'group_invites'
    AND INDEX_NAME = 'idx_invitee_status'
);

SET @sql = IF(@idx2_exists = 0,
    'ALTER TABLE group_invites ADD INDEX idx_invitee_status (invitee_id, status)',
    'SELECT "Índice idx_invitee_status já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Tabela group_invites atualizada com sucesso!' AS resultado;
