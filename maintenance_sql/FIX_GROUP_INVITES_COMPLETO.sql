-- ============================================
-- Script para corrigir a tabela group_invites
-- Adiciona todas as colunas necessárias
-- Execute linha por linha ou ignore os erros de duplicação
-- ============================================

USE siteguedes;

-- 1. Adicionar coluna inviter_id (quem está a convidar)
-- Se der erro "Duplicate column", ignore - significa que já existe
ALTER TABLE group_invites 
ADD COLUMN inviter_id INT NOT NULL AFTER group_id;

-- 2. Adicionar coluna invitee_id (quem está a ser convidado)
ALTER TABLE group_invites 
ADD COLUMN invitee_id INT NOT NULL AFTER inviter_id;

-- 2.1 Adicionar coluna invitee_user_code (código do utilizador convidado)
ALTER TABLE group_invites 
ADD COLUMN invitee_user_code VARCHAR(6) NOT NULL AFTER invitee_id;

-- 3. Adicionar foreign key para inviter_id
ALTER TABLE group_invites 
ADD CONSTRAINT fk_inviter 
FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE;

-- 4. Adicionar foreign key para invitee_id
ALTER TABLE group_invites 
ADD CONSTRAINT fk_invitee 
FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE;

-- 5. Adicionar índice único para prevenir convites duplicados
ALTER TABLE group_invites 
ADD UNIQUE KEY unique_invite (group_id, invitee_id);

-- 6. Adicionar índice para melhorar performance das consultas
ALTER TABLE group_invites 
ADD INDEX idx_invitee_status (invitee_id, status);

-- 7. Adicionar índice para consultas por grupo e status
ALTER TABLE group_invites 
ADD INDEX idx_group_pending (group_id, status);

-- Verificar estrutura final
SELECT 'Estrutura da tabela group_invites corrigida com sucesso!' AS resultado;
DESCRIBE group_invites;
