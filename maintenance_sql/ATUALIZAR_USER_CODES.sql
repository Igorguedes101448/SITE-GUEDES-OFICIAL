-- ============================================
-- ATUALIZAÇÃO: Sistema de Códigos de Utilizador
-- Execute este script para adicionar/atualizar os códigos
-- ============================================

USE siteguedes;

-- Passo 1: Adicionar coluna user_code se não existir
ALTER TABLE users ADD COLUMN user_code VARCHAR(6) UNIQUE COMMENT 'Código único para convites de grupo' AFTER id;

-- Passo 2: Adicionar índice se não existir
CREATE INDEX idx_user_code ON users(user_code);

-- Passo 3: Verificar situação atual
SELECT 
    'Estado Atual' as info,
    COUNT(*) as total_users,
    COUNT(user_code) as com_codigo,
    COUNT(*) - COUNT(user_code) as sem_codigo
FROM users;

-- Passo 4: Mostrar utilizadores sem código
SELECT id, username, email, user_code 
FROM users 
WHERE user_code IS NULL OR user_code = ''
LIMIT 10;

-- ============================================
-- NOTA: Para gerar códigos para utilizadores existentes,
-- aceda a: http://localhost/siteguedes/generate_user_codes.php
-- ============================================
