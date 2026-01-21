-- ============================================
-- Script para Corrigir Visibilidade de Receitas
-- Data: 20-Jan-2026
-- ============================================

USE siteguedes;

-- Ver estado atual das receitas
SELECT id, title, is_draft, visibility, author_id 
FROM recipes 
ORDER BY id;

-- ============================================
-- OPÇÃO 1: Se quiser que receitas existentes sejam públicas
-- ============================================

-- Tornar todas as receitas existentes públicas e não-rascunho
-- (exceto a receita ID 23 que é um rascunho real)
UPDATE recipes 
SET is_draft = 0, visibility = 'public' 
WHERE id IN (1, 2, 3, 4, 5, 6, 7, 8, 15);

-- ============================================
-- OPÇÃO 2: Se quiser manter a receita 23 como está (rascunho privado)
-- ============================================
-- Não é necessário fazer nada, ela já está correta

-- ============================================
-- Verificar resultado
-- ============================================
SELECT id, title, is_draft, visibility, author_id 
FROM recipes 
ORDER BY id;

-- ============================================
-- Resumo do que significa cada estado:
-- ============================================
-- is_draft = 0, visibility = 'public'  -> Receita pública (aparece em explorar-receitas.html)
-- is_draft = 0, visibility = 'private' -> Receita privada completa (aparece em receitas-privadas.html com badge "PRIVADA")
-- is_draft = 1, visibility = qualquer  -> Rascunho (aparece em receitas-privadas.html com badge "RASCUNHO")
