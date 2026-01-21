-- ============================================
-- Script SEGURO para corrigir a tabela group_invites
-- Verifica se as colunas existem antes de adicionar
-- ============================================

USE siteguedes;

-- Desabilitar verificações temporariamente para melhor performance
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

-- 1. Adicionar inviter_id se não existir
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND COLUMN_NAME = 'inviter_id');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE group_invites ADD COLUMN inviter_id INT NOT NULL AFTER group_id',
    'SELECT "inviter_id já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Adicionar invitee_id se não existir
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND COLUMN_NAME = 'invitee_id');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE group_invites ADD COLUMN invitee_id INT NOT NULL AFTER inviter_id',
    'SELECT "invitee_id já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.1 Adicionar invitee_user_code se não existir
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND COLUMN_NAME = 'invitee_user_code');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE group_invites ADD COLUMN invitee_user_code VARCHAR(6) NOT NULL AFTER invitee_id',
    'SELECT "invitee_user_code já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Adicionar foreign key inviter_id se não existir
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND COLUMN_NAME = 'inviter_id'
    AND REFERENCED_TABLE_NAME = 'users');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE group_invites ADD CONSTRAINT fk_inviter FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT "FK inviter_id já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Adicionar foreign key invitee_id se não existir
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND COLUMN_NAME = 'invitee_id'
    AND REFERENCED_TABLE_NAME = 'users');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE group_invites ADD CONSTRAINT fk_invitee FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT "FK invitee_id já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Adicionar índice único se não existir
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND INDEX_NAME = 'unique_invite');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE group_invites ADD UNIQUE KEY unique_invite (group_id, invitee_id)',
    'SELECT "Índice unique_invite já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Adicionar índice idx_invitee_status se não existir
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND INDEX_NAME = 'idx_invitee_status');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE group_invites ADD INDEX idx_invitee_status (invitee_id, status)',
    'SELECT "Índice idx_invitee_status já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. Adicionar índice idx_group_pending se não existir
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'siteguedes' 
    AND TABLE_NAME = 'group_invites' 
    AND INDEX_NAME = 'idx_group_pending');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE group_invites ADD INDEX idx_group_pending (group_id, status)',
    'SELECT "Índice idx_group_pending já existe" AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Restaurar configurações
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET SQL_MODE=@OLD_SQL_MODE;

-- Mostrar resultado final
SELECT '✓ Tabela group_invites corrigida com sucesso!' AS resultado;
DESCRIBE group_invites;
