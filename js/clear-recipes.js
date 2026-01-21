/* ============================================
   Script para REMOVER TODAS AS RECEITAS
   Este script garante que todas as receitas sejam
   completamente removidas do localStorage
   ============================================ */

(function() {
    'use strict';
    
    // Remover todas as receitas ao carregar qualquer página
    localStorage.removeItem('chefguedes-recipes');
    
    // Garantir que o array de receitas está sempre vazio
    localStorage.setItem('chefguedes-recipes', JSON.stringify([]));
    
    // Interceptar qualquer tentativa de salvar receitas
    const originalSetItem = localStorage.setItem;
    localStorage.setItem = function(key, value) {
        if (key === 'chefguedes-recipes') {
            // Sempre forçar array vazio para receitas
            return originalSetItem.call(this, key, JSON.stringify([]));
        }
        return originalSetItem.call(this, key, value);
    };
    
    console.log('✓ Todas as receitas foram removidas do sistema.');
})();
